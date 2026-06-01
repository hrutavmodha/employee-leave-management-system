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
        $requests = Auth::user()->leaveRequests()->with('leaveType')->latest()->get();
        return view('leaves.index', compact('requests'));
    }

    public function create()
    {
        $leaveTypes = LeaveType::all();
        // Only show current year balances to avoid confusion
        $balances = Auth::user()->leaveBalances()->where('year', date('Y'))->with('leaveType')->get();
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
        $year = $start->year;

        // Use the Service to get or create the balance record
        $balance = $this->calculationService->getOrCreateBalance(Auth::user(), $request->leave_type_id, $year);

        if ($balance->remaining_days < $daysRequested) {
            return back()->withErrors(['end_date' => "Insufficient balance: You only have {$balance->remaining_days} days left for the year {$year}, but you requested {$daysRequested} days."])->withInput();
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
            $path = $request->file('attachment')->store('attachments', 'public');
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
        $leaveRequest->update(['status' => 'Cancelled']);
        return redirect()->route('leaves.index')->with('success', 'Leave request cancelled.');
    }
}
