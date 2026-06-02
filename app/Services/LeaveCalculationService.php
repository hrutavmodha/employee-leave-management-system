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
     * Initialize balances for a user for a specific year.
     */
    public function initializeBalances(User $user, $year = null)
    {
        $leaveTypes = LeaveType::all();
        $year = $year ?: date('Y');

        foreach ($leaveTypes as $type) {
            $carriedOver = 0;
            if ($type->carry_forward) {
                $previousBalance = LeaveBalance::where('user_id', $user->id)
                    ->where('leave_type_id', $type->id)
                    ->where('year', $year - 1)
                    ->first();
                if ($previousBalance) {
                    $carriedOver = $previousBalance->remaining_days;
                }
            }

            LeaveBalance::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'leave_type_id' => $type->id,
                    'year' => $year,
                ],
                [
                    'allocated_days' => $type->allowed_days + $carriedOver,
                    'used_days' => 0,
                    'remaining_days' => $type->allowed_days + $carriedOver,
                ]
            );
        }
    }

    /**
     * Get balance for a specific type and year, auto-initializing if missing.
     */
    public function getOrCreateBalance(User $user, $leaveTypeId, $year)
    {
        $balance = LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();

        if (!$balance) {
            // Auto-initialize for the requested year
            $this->initializeBalances($user, $year);
            
            return LeaveBalance::where('user_id', $user->id)
                ->where('leave_type_id', $leaveTypeId)
                ->where('year', $year)
                ->first();
        }

        return $balance;
    }

    /**
     * Deduct days from user balance when leave is approved.
     */
    public function deductBalance(LeaveRequest $request)
    {
        $year = $request->start_date->year;
        $user = $request->user;

        $balance = $this->getOrCreateBalance($user, $request->leave_type_id, $year);

        if ($balance->remaining_days < $request->days_requested) {
            throw new Exception("Insufficient balance. User has {$balance->remaining_days} days, but requested {$request->days_requested}.");
        }

        $balance->used_days += $request->days_requested;
        $balance->remaining_days -= $request->days_requested;
        $balance->save();
    }

    /**
     * Refund days to user balance when an approved leave is cancelled.
     */
    public function refundBalance(LeaveRequest $request)
    {
        $year = $request->start_date->year;
        $user = $request->user;

        $balance = $this->getOrCreateBalance($user, $request->leave_type_id, $year);

        $balance->used_days = max(0, $balance->used_days - $request->days_requested);
        $balance->remaining_days = min($balance->allocated_days, $balance->remaining_days + $request->days_requested);
        $balance->save();
    }
}
