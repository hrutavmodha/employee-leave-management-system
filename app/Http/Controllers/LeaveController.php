<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    /**
     * Display a listing of the user's leave requests.
     */
    public function index()
    {
        $requests = Auth::user()->leaveRequests()->with('leaveType')->latest()->get();
        return view('leaves.index', compact('requests'));
    }

    /**
     * Show the form for creating a new leave request.
     */
    public function create()
    {
        $leaveTypes = LeaveType::all();
        return view('leaves.create', compact('leaveTypes'));
    }

    /**
     * Store a newly created leave request in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,png|max:2048',
        ]);

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        
        // Calculate total days (including both start and end date)
        $daysRequested = $start->diffInDays($end) + 1;

        $leaveRequest = LeaveRequest::create([
            'user_id' => Auth::id(),
            'leave_type_id' => $request->leave_type_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'days_requested' => $daysRequested,
            'reason' => $request->reason,
            'status' => 'Pending',
        ]);

        // Handle Attachment if exists
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('attachments', 'public');
            $leaveRequest->attachments()->create([
                'file_name' => $request->file('attachment')->getClientOriginalName(),
                'file_path' => $path,
            ]);
        }

        return redirect()->route('leaves.index')->with('success', "Leave request for {$daysRequested} days submitted successfully.");
    }

    /**
     * Cancel a pending leave request.
     */
    public function cancel(LeaveRequest $leaveRequest)
    {
        // Ensure the request belongs to the user and is still pending
        if ($leaveRequest->user_id !== Auth::id()) {
            abort(403);
        }

        if ($leaveRequest->status !== 'Pending') {
            return redirect()->route('leaves.index')->with('error', 'Only pending requests can be cancelled.');
        }

        $leaveRequest->update(['status' => 'Cancelled']);

        return redirect()->route('leaves.index')->with('success', 'Leave request cancelled successfully.');
    }
}
