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

        $committedRequest = null;

        DB::beginTransaction();
        try {
            // Pessimistic lock to serialize concurrent status updates
            $lockedRequest = LeaveRequest::where('id', $leaveRequest->id)->lockForUpdate()->firstOrFail();

            if ($lockedRequest->status !== 'Pending') {
                DB::rollBack();
                return redirect()->route('approvals.index')->with('error', 'Only pending leave requests can be approved.');
            }

            // Deduct balance
            $this->calculationService->deductBalance($lockedRequest);

            // Update request status
            $lockedRequest->update([
                'status' => 'Approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'manager_comment' => $request->manager_comment,
            ]);

            $committedRequest = $lockedRequest;

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->route('approvals.index')->with('error', 'Approval failed: ' . $e->getMessage());
        }

        // Notify employee only after a successful commit so that
        // (a) a mail-server failure cannot roll back the DB write, and
        // (b) a rolled-back transaction cannot produce a phantom email.
        $committedRequest->user->notify(new LeaveStatusUpdated($committedRequest));

        return redirect()->route('approvals.index')->with('success', 'Leave request approved and employee notified.');
    }

    /**
     * Reject the specified leave request.
     */
    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorizeAction($leaveRequest);

        $request->validate([
            'manager_comment' => 'nullable|string|max:1000',
        ]);

        $committedRequest = null;

        DB::beginTransaction();
        try {
            // Pessimistic lock to serialize concurrent status updates
            $lockedRequest = LeaveRequest::where('id', $leaveRequest->id)->lockForUpdate()->firstOrFail();

            if ($lockedRequest->status !== 'Pending') {
                DB::rollBack();
                return redirect()->route('approvals.index')->with('error', 'Only pending leave requests can be rejected.');
            }

            $lockedRequest->update([
                'status' => 'Rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'manager_comment' => $request->manager_comment,
            ]);

            $committedRequest = $lockedRequest;

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->route('approvals.index')->with('error', 'Rejection failed: ' . $e->getMessage());
        }

        // Notify employee only after a successful commit so that
        // (a) a mail-server failure cannot roll back the DB write, and
        // (b) a rolled-back transaction cannot produce a phantom email.
        $committedRequest->user->notify(new LeaveStatusUpdated($committedRequest));

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
