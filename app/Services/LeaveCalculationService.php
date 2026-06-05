<?php

namespace App\Services;

use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveCalculationService
{
    /**
     * Initialize balances for a user for a specific year.
     */
    public function initializeBalances(User $user, $year = null)
    {
        $leaveTypes = LeaveType::all();
        $year = $year ?: date('Y');

        $previousBalances = LeaveBalance::where('user_id', $user->id)
            ->where('year', $year - 1)
            ->get()
            ->keyBy('leave_type_id');

        $currentBalances = LeaveBalance::where('user_id', $user->id)
            ->where('year', $year)
            ->get()
            ->keyBy('leave_type_id');

        foreach ($leaveTypes as $type) {
            if ($currentBalances->has($type->id)) {
                continue;
            }

            $carriedOver = 0;
            if ($type->carry_forward) {
                $previousBalance = $previousBalances->get($type->id);
                if ($previousBalance) {
                    $carriedOver = $previousBalance->remaining_days;
                }
            }

            LeaveBalance::create([
                'user_id' => $user->id,
                'leave_type_id' => $type->id,
                'year' => $year,
                'allocated_days' => $type->allowed_days + $carriedOver,
                'used_days' => 0,
                'remaining_days' => $type->allowed_days + $carriedOver,
            ]);
        }
    }

    /**
     * Get balance for a specific type and year, auto-initializing if missing.
     */
    public function getOrCreateBalance(User $user, $leaveTypeId, $year)
    {
        $joiningDate = $user->joining_date ? Carbon::parse($user->joining_date) : Carbon::today();
        $joiningYear = $joiningDate->year;

        // Chronologically initialize missing balance records to preserve carry-forward chains
        for ($y = $joiningYear; $y <= $year; $y++) {
            $balance = LeaveBalance::where('user_id', $user->id)
                ->where('leave_type_id', $leaveTypeId)
                ->where('year', $y)
                ->first();

            if (!$balance) {
                $this->initializeBalances($user, $y);
            }
        }

        return LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();
    }

    /**
     * Get the exact list of working dates (non-weekend, non-public holiday) in a date range.
     *
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     * @return array<\Carbon\Carbon>
     */
    public function getWorkingDays(Carbon $start, Carbon $end): array
    {
        $workingDays = [];
        $current = $start->copy()->startOfDay();
        $endLimit = $end->copy()->startOfDay();

        $weekHolidays = array_map('intval', \App\Models\Setting::getVal('week_holidays', [0, 6]));
        $publicHolidays = \App\Models\PublicHoliday::whereBetween('date', [
            $start->toDateString(),
            $end->toDateString()
        ])->pluck('date')->map(function ($date) {
            return $date instanceof \Carbon\Carbon
                ? $date->format('Y-m-d')
                : \Carbon\Carbon::parse($date)->format('Y-m-d');
        })->toArray();

        while ($current->lte($endLimit)) {
            // Check if it is a week holiday
            if (in_array($current->dayOfWeek, $weekHolidays, true)) {
                $current->addDay();
                continue;
            }

            // Check if it is a public/company holiday
            if (in_array($current->format('Y-m-d'), $publicHolidays, true)) {
                $current->addDay();
                continue;
            }

            $workingDays[] = $current->copy();
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Calculate the distribution of leave days per year for a given date range.
     *
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     * @return array<int, int> An array mapping year => days requested in that year
     */
    public function calculateDaysPerYear(Carbon $start, Carbon $end): array
    {
        $daysPerYear = [];
        $workingDays = $this->getWorkingDays($start, $end);
        foreach ($workingDays as $date) {
            $year = $date->year;
            $daysPerYear[$year] = ($daysPerYear[$year] ?? 0) + 1;
        }
        return $daysPerYear;
    }

    /**
     * Deduct days from user balance when leave is approved.
     */
    public function deductBalance(LeaveRequest $request)
    {
        $user = $request->user;
        
        $hasStaticDates = $request->id && DB::table('leave_request_dates')
            ->where('leave_request_id', $request->id)
            ->exists();

        if ($hasStaticDates) {
            $daysPerYear = DB::table('leave_request_dates')
                ->where('leave_request_id', $request->id)
                ->selectRaw('year, count(*) as count')
                ->groupBy('year')
                ->pluck('count', 'year')
                ->toArray();
        } else {
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->startOfDay();
            $daysPerYear = $this->calculateDaysPerYear($start, $end);
        }

        foreach ($daysPerYear as $year => $days) {
            // Ensure the balance record is initialized
            $this->getOrCreateBalance($user, $request->leave_type_id, $year);

            // Fetch with pessimistic locking to serialize concurrent transactions
            $balance = LeaveBalance::where('user_id', $user->id)
                ->where('leave_type_id', $request->leave_type_id)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (!$balance) {
                throw new Exception("Leave balance record not found for year {$year}.");
            }

            if ($balance->remaining_days < $days) {
                throw new Exception(
                    "Insufficient balance. User has {$balance->remaining_days} " .
                    "days for year {$year}, but requested {$days}."
                );
            }

            $balance->used_days += $days;
            $balance->remaining_days -= $days;
            $balance->save();
        }
    }

    /**
     * Refund days to user balance when an approved leave is cancelled.
     */
    public function refundBalance(LeaveRequest $request)
    {
        $user = $request->user;
        
        $hasStaticDates = $request->id && DB::table('leave_request_dates')
            ->where('leave_request_id', $request->id)
            ->exists();

        if ($hasStaticDates) {
            $daysPerYear = DB::table('leave_request_dates')
                ->where('leave_request_id', $request->id)
                ->selectRaw('year, count(*) as count')
                ->groupBy('year')
                ->pluck('count', 'year')
                ->toArray();
        } else {
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->startOfDay();
            $daysPerYear = $this->calculateDaysPerYear($start, $end);
        }

        foreach ($daysPerYear as $year => $days) {
            // Ensure the balance record is initialized
            $this->getOrCreateBalance($user, $request->leave_type_id, $year);

            // Fetch with pessimistic locking to serialize concurrent transactions
            $balance = LeaveBalance::where('user_id', $user->id)
                ->where('leave_type_id', $request->leave_type_id)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (!$balance) {
                throw new Exception("Leave balance record not found for year {$year}.");
            }

            $balance->used_days = max(0, $balance->used_days - $days);
            $balance->remaining_days = min(
                $balance->allocated_days,
                $balance->remaining_days + $days
            );
            $balance->save();
        }
    }
}
