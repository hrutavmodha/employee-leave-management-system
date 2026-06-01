<?php

namespace App\Services;

use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Exception;

class LeaveCalculationService
{
    /**
     * Initialize balances for a new user based on current leave types.
     */
    public function initializeBalances(User $user)
    {
        $leaveTypes = LeaveType::all();
        $currentYear = date('Y');

        foreach ($leaveTypes as $type) {
            LeaveBalance::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'leave_type_id' => $type->id,
                    'year' => $currentYear,
                ],
                [
                    'allocated_days' => $type->allowed_days,
                    'used_days' => 0,
                    'remaining_days' => $type->allowed_days,
                ]
            );
        }
    }

    /**
     * Deduct days from user balance when leave is approved.
     */
    public function deductBalance(LeaveRequest $request)
    {
        $balance = LeaveBalance::where('user_id', $request->user_id)
            ->where('leave_type_id', $request->leave_type_id)
            ->where('year', date('Y', strtotime($request->start_date)))
            ->first();

        if (!$balance) {
            throw new Exception("Leave balance record not found for this user and leave type.");
        }

        if ($balance->remaining_days < $request->days_requested) {
            throw new Exception("Insufficient leave balance.");
        }

        $balance->used_days += $request->days_requested;
        $balance->remaining_days -= $request->days_requested;
        $balance->save();
    }
}
