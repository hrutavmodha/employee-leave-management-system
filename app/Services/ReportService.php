<?php

namespace App\Services;

use App\Models\User;
use App\Models\Department;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getEmployeeReport()
    {
        return \Illuminate\Support\Facades\Cache::remember('reports.employees', 3600, function () {
            $currentYear = (int) date('Y');

            return User::with([
                'department',
                'leaveBalances' => function ($query) use ($currentYear) {
                    $query->where('year', $currentYear)->with('leaveType');
                }
            ])
            ->get()
            ->map(function ($user) use ($currentYear) {
                $approvedLeaves = DB::table('leave_request_dates')
                    ->join('leave_requests', 'leave_request_dates.leave_request_id', '=', 'leave_requests.id')
                    ->where('leave_requests.user_id', $user->id)
                    ->where('leave_requests.status', 'Approved')
                    ->where('leave_request_dates.year', $currentYear)
                    ->count();

                $user->approved_leaves = $approvedLeaves;
                return $user;
            });
        });
    }

    /**
     * Get leave statistics grouped by department.
     */
    public function getDepartmentReport()
    {
        return \Illuminate\Support\Facades\Cache::remember('reports.departments', 3600, function () {
            $currentYear = (int) date('Y');

            $departments = Department::with('users')->get();

            $report = $departments->map(function ($dept) use ($currentYear) {
                $userIds = $dept->users->pluck('id')->toArray();
                $totalEmployees = count($userIds);
                $approvedLeaves = 0;
                $rejectedLeaves = 0;

                if (!empty($userIds)) {
                    $approvedLeaves = DB::table('leave_request_dates')
                        ->join('leave_requests', 'leave_request_dates.leave_request_id', '=', 'leave_requests.id')
                        ->whereIn('leave_requests.user_id', $userIds)
                        ->where('leave_requests.status', 'Approved')
                        ->where('leave_request_dates.year', $currentYear)
                        ->count();

                    $rejectedLeaves = DB::table('leave_request_dates')
                        ->join('leave_requests', 'leave_request_dates.leave_request_id', '=', 'leave_requests.id')
                        ->whereIn('leave_requests.user_id', $userIds)
                        ->where('leave_requests.status', 'Rejected')
                        ->where('leave_request_dates.year', $currentYear)
                        ->count();
                }

                $obj = new \stdClass();
                $obj->id = $dept->id;
                $obj->name = $dept->name;
                $obj->total_employees = $totalEmployees;
                $obj->total_leaves = $approvedLeaves;
                $obj->approved_leaves = $approvedLeaves;
                $obj->rejected_leaves = $rejectedLeaves;

                return $obj;
            });

            // Aggregate employees without a department (department_id = null)
            $unassignedUserIds = User::whereNull('department_id')->pluck('id')->toArray();

            if (!empty($unassignedUserIds)) {
                $totalEmployees = count($unassignedUserIds);
                $approvedLeaves = DB::table('leave_request_dates')
                    ->join('leave_requests', 'leave_request_dates.leave_request_id', '=', 'leave_requests.id')
                    ->whereIn('leave_requests.user_id', $unassignedUserIds)
                    ->where('leave_requests.status', 'Approved')
                    ->where('leave_request_dates.year', $currentYear)
                    ->count();

                $rejectedLeaves = DB::table('leave_request_dates')
                    ->join('leave_requests', 'leave_request_dates.leave_request_id', '=', 'leave_requests.id')
                    ->whereIn('leave_requests.user_id', $unassignedUserIds)
                    ->where('leave_requests.status', 'Rejected')
                    ->where('leave_request_dates.year', $currentYear)
                    ->count();

                $obj = new \stdClass();
                $obj->id = null;
                $obj->name = 'Unassigned';
                $obj->total_employees = $totalEmployees;
                $obj->total_leaves = $approvedLeaves;
                $obj->approved_leaves = $approvedLeaves;
                $obj->rejected_leaves = $rejectedLeaves;

                $report->push($obj);
            }

            return $report;
        });
    }

    /**
     * Get monthly leave statistics for the current year.
     *
     * Returns a collection of objects with `month` (two-digit string)
     * and `count` (total approved days) for each month that has data.
     */
    public function getMonthlyStats()
    {
        return \Illuminate\Support\Facades\Cache::remember('reports.monthly', 3600, function () {
            $currentYear = (int) date('Y');

            $monthCounts = DB::table('leave_request_dates')
                ->join('leave_requests', 'leave_request_dates.leave_request_id', '=', 'leave_requests.id')
                ->where('leave_requests.status', 'Approved')
                ->where('leave_request_dates.year', $currentYear)
                ->selectRaw('leave_request_dates.month, count(*) as count')
                ->groupBy('leave_request_dates.month')
                ->get();

            $stats = [];
            foreach ($monthCounts as $row) {
                $obj = new \stdClass();
                $obj->month = str_pad($row->month, 2, '0', STR_PAD_LEFT);
                $obj->count = $row->count;
                $stats[] = $obj;
            }

            usort($stats, function ($a, $b) {
                return strcmp($a->month, $b->month);
            });

            return collect($stats);
        });
    }
}
