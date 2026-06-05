<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Services\LeaveCalculationService;
use App\Notifications\LeaveRequestSubmitted;
use App\Notifications\LeaveRequestCancelled;
use App\Exceptions\InsufficientLeaveBalanceException;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    protected $calculationService;

    public function __construct(LeaveCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    public function index()
    {
        $requests = Auth::user()->leaveRequests()
            ->with(['leaveType', 'attachments'])
            ->latest()
            ->paginate(15);
        return view('leaves.index', compact('requests'));
    }

    public function create()
    {
        $leaveTypes = \Illuminate\Support\Facades\Cache::remember('leave_types.all', 3600, function () {
            return LeaveType::all();
        });
        
        $userId = Auth::id();
        $year = date('Y');
        $balances = \Illuminate\Support\Facades\Cache::remember("user.balances.{$userId}.{$year}", 3600, function () {
            return Auth::user()->leaveBalances()->where('year', date('Y'))->with('leaveType')->get();
        });
        
        return view('leaves.create', compact('leaveTypes', 'balances'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
        ]);

        $start = Carbon::parse($request->start_date)->startOfDay();
        $end = Carbon::parse($request->end_date)->startOfDay();
        $daysPerYear = $this->calculationService->calculateDaysPerYear($start, $end);
        $daysRequested = array_sum($daysPerYear);

        if ($daysRequested === 0) {
            return back()->withErrors([
                'end_date' => 'The requested leave period does not contain any working days.'
            ])->withInput();
        }

        try {
            $leaveRequest = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $daysPerYear, $daysRequested) {
                // Acquire lock on user row to serialize submissions for the user
                $user = \App\Models\User::where('id', Auth::id())->lockForUpdate()->first();

                $overlapExists = LeaveRequest::where('user_id', $user->id)
                    ->whereIn('status', ['Pending', 'Approved'])
                    ->where('start_date', '<=', $request->end_date)
                    ->where('end_date', '>=', $request->start_date)
                    ->lockForUpdate() // also lock any matching leave requests
                    ->exists();

                if ($overlapExists) {
                    throw new \Exception('OVERLAP_EXISTS');
                }

                foreach ($daysPerYear as $year => $days) {
                    $balance = $this->calculationService->getOrCreateBalance(
                        $user,
                        $request->leave_type_id,
                        $year
                    );

                    if ($balance->remaining_days < $days) {
                        throw new InsufficientLeaveBalanceException($year, $balance->remaining_days, $days);
                    }
                }

                $leaveReq = LeaveRequest::create([
                    'user_id' => $user->id,
                    'leave_type_id' => $request->leave_type_id,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'days_requested' => $daysRequested,
                    'reason' => $request->reason,
                    'status' => 'Pending',
                ]);

                if ($request->hasFile('attachment')) {
                    $path = $request->file('attachment')->store('attachments', 'local');
                    $leaveReq->attachments()->create([
                        'file_name' => $request->file('attachment')->getClientOriginalName(),
                        'file_path' => $path,
                    ]);
                }

                return $leaveReq;
            });
        } catch (InsufficientLeaveBalanceException $e) {
            return back()->withErrors([
                'end_date' => "Insufficient balance: You only have {$e->getRemainingDays()} days left for the year {$e->getYear()}, but you requested {$e->getRequestedDays()} days."
            ])->withInput();
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if ($msg === 'OVERLAP_EXISTS') {
                return back()->withErrors([
                    'start_date' => 'You already have a pending or approved leave request overlapping with these dates.'
                ])->withInput();
            }

            throw $e;
        }

        // Notify direct manager if assigned
        $user = Auth::user();
        if ($user->manager) {
            $user->manager->notify(new LeaveRequestSubmitted($leaveRequest));
        }

        return redirect()->route('leaves.index')->with('success', "Leave application submitted! Duration: {$daysRequested} days.");
    }

    public function cancel(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->user_id !== Auth::id()) abort(403);

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($leaveRequest) {
                // Pessimistic lock to serialize concurrent updates.
                // All validations run on the locked row to prevent a
                // TOCTOU race where concurrent cancellations could
                // double-refund the leave balance.
                $lockedRequest = LeaveRequest::where('id', $leaveRequest->id)->lockForUpdate()->firstOrFail();

                if ($lockedRequest->start_date->lte(Carbon::today())) {
                    throw new \Exception('CANCEL_DATE_PAST');
                }

                if ($lockedRequest->status === 'Approved') {
                    $this->calculationService->refundBalance($lockedRequest);
                } elseif ($lockedRequest->status !== 'Pending') {
                    throw new \Exception('Only pending or approved requests can be cancelled.');
                }

                $lockedRequest->update(['status' => 'Cancelled']);
            });
        } catch (\Exception $e) {
            if ($e->getMessage() === 'CANCEL_DATE_PAST') {
                return back()->withErrors([
                    'start_date' => 'You cannot cancel a leave request once the start date has started or passed.',
                ]);
            }

            return redirect()->route('leaves.index')->with('error', 'Cancellation failed: ' . $e->getMessage());
        }

        // Notify direct manager if assigned
        $employee = $leaveRequest->user;
        if ($employee->manager) {
            $employee->manager->notify(new LeaveRequestCancelled($leaveRequest));
        }

        return redirect()->route('leaves.index')->with('success', 'Leave request cancelled.');
    }

    /**
     * Download or view a leave request attachment securely with authorization checks.
     */
    public function viewAttachment(LeaveRequest $leaveRequest, \App\Models\Attachment $attachment)
    {
        $user = Auth::user();

        // 1. Employee who created it can view it
        // 2. Manager of the employee who created it can view it
        // 3. HR/Admin can view it
        $isOwner = $leaveRequest->user_id === $user->id;
        $isManager = $user->isManager() && $leaveRequest->user->manager_id === $user->id;
        $isAdmin = $user->isAdmin();

        if (!$isOwner && !$isManager && !$isAdmin) {
            abort(403, 'Unauthorized action.');
        }

        // Verify attachment belongs to this leave request
        if ($attachment->leave_request_id !== $leaveRequest->id) {
            abort(404, 'Attachment not found.');
        }

        $path = $attachment->file_path;
        
        // Verify file exists on local storage disk
        if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
            abort(404, 'File not found.');
        }

        return \Illuminate\Support\Facades\Storage::disk('local')->response($path, $attachment->file_name);
    }
}
