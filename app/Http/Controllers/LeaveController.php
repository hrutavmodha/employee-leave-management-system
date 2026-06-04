<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Services\LeaveCalculationService;
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
        $userId = Auth::id();
        $requests = \Illuminate\Support\Facades\Cache::remember('user.leaves.' . $userId, 3600, function () {
            return Auth::user()->leaveRequests()->with('leaveType')->latest()->get();
        });
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
        $daysRequested = $start->diffInDays($end) + 1;

        $daysPerYear = $this->calculationService->calculateDaysPerYear($start, $end);

        foreach ($daysPerYear as $year => $days) {
            $balance = $this->calculationService->getOrCreateBalance(
                Auth::user(),
                $request->leave_type_id,
                $year
            );

            if ($balance->remaining_days < $days) {
                $msg = "Insufficient balance: You only have {$balance->remaining_days} " .
                    "days left for the year {$year}, but you requested {$days} days.";
                return back()->withErrors(['end_date' => $msg])->withInput();
            }
        }

        $leaveRequest = LeaveRequest::create([
            'user_id' => Auth::id(),
            'leave_type_id' => $request->leave_type_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'days_requested' => $daysRequested,
            'reason' => $request->reason,
            'status' => 'Pending',
        ]);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('attachments', 'local');
            $leaveRequest->attachments()->create([
                'file_name' => $request->file('attachment')->getClientOriginalName(),
                'file_path' => $path,
            ]);
        }

        return redirect()->route('leaves.index')->with('success', "Leave application submitted! Duration: {$daysRequested} days.");
    }

    public function cancel(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->user_id !== Auth::id()) abort(403);

        \Illuminate\Support\Facades\DB::transaction(function () use ($leaveRequest) {
            if ($leaveRequest->status === 'Approved') {
                $this->calculationService->refundBalance($leaveRequest);
            }
            $leaveRequest->update(['status' => 'Cancelled']);
        });

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
