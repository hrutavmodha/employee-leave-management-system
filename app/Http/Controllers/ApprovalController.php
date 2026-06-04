<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Services\LeaveCalculationService;
use App\Notifications\LeaveStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class ApprovalController extends Controller
{
    protected $calculationService;

    public function __construct(LeaveCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Display a listing of pending leave requests for approval.
     */
    public function index()
    {
        $user = Auth::user();
        
        if ($user->isAdmin()) {
            $pendingRequests = LeaveRequest::with(['user', 'leaveType', 'attachments'])
                ->where('status', 'Pending')
                ->latest()
                ->paginate(15);
        } else {
            $pendingRequests = LeaveRequest::with(['user', 'leaveType', 'attachments'])
                ->whereHas('user', function ($query) use ($user) {
                    $query->where('manager_id', $user->id);
                })
                ->where('status', 'Pending')
                ->latest()
                ->paginate(15);
        }

        return view('approvals.index', compact('pendingRequests'));
    }

    /**
     * Approve the specified leave request.
     */
    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorizeAction($leaveRequest);

        if ($leaveRequest->status !== 'Pending') {
            return redirect()->route('approvals.index')->with('error', 'Only pending leave requests can be approved.');
        }

        DB::beginTransaction();
        try {
            // Deduct balance
            $this->calculationService->deductBalance($leaveRequest);

            // Update request status
            $leaveRequest->update([
                'status' => 'Approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'manager_comment' => $request->manager_comment,
            ]);

            // Notify Employee
            $leaveRequest->user->notify(new LeaveStatusUpdated($leaveRequest));

            DB::commit();
            return redirect()->route('approvals.index')->with('success', 'Leave request approved and employee notified.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->route('approvals.index')->with('error', 'Approval failed: ' . $e->getMessage());
        }
    }

    /**
     * Reject the specified leave request.
     */
    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorizeAction($leaveRequest);

        if ($leaveRequest->status !== 'Pending') {
            return redirect()->route('approvals.index')->with('error', 'Only pending leave requests can be rejected.');
        }

        $request->validate([
            'manager_comment' => 'nullable|string|max:1000',
        ]);

        $leaveRequest->update([
            'status' => 'Rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'manager_comment' => $request->manager_comment,
        ]);

        // Notify Employee
        $leaveRequest->user->notify(new LeaveStatusUpdated($leaveRequest));

        return redirect()->route('approvals.index')->with('success', 'Leave request rejected and employee notified.');
    }

    protected function authorizeAction(LeaveRequest $request)
    {
        $user = Auth::user();
        
        if ($user->isAdmin()) return true;
        if ($user->isManager() && $request->user->manager_id === $user->id) return true;

        abort(403, 'You are not authorized to approve this request.');
    }
}
