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

            // Fetch all approved leave day counts grouped by user in a single query to eliminate N+1
            $approvedCounts = DB::table('leave_request_dates')
                ->join('leave_requests', 'leave_request_dates.leave_request_id', '=', 'leave_requests.id')
                ->where('leave_requests.status', 'Approved')
                ->where('leave_request_dates.year', $currentYear)
                ->selectRaw('leave_requests.user_id, count(*) as count')
                ->groupBy('leave_requests.user_id')
                ->pluck('count', 'user_id')
                ->toArray();

            return User::with([
                'department',
                'leaveBalances' => function ($query) use ($currentYear) {
                    $query->where('year', $currentYear)->with('leaveType');
                }
            ])
            ->get()
            ->map(function ($user) use ($approvedCounts) {
                $user->approved_leaves = $approvedCounts[$user->id] ?? 0;
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

            // Fetch all leave day counts grouped by department_id and status in a single query to eliminate N+1
            $leaveStats = DB::table('leave_request_dates')
                ->join('leave_requests', 'leave_request_dates.leave_request_id', '=', 'leave_requests.id')
                ->join('users', 'leave_requests.user_id', '=', 'users.id')
                ->where('leave_request_dates.year', $currentYear)
                ->whereIn('leave_requests.status', ['Approved', 'Rejected'])
                ->selectRaw('users.department_id, leave_requests.status, count(*) as count')
                ->groupBy('users.department_id', 'leave_requests.status')
                ->get();

            $statsMap = [];
            foreach ($leaveStats as $row) {
                $deptKey = $row->department_id ?? 'unassigned';
                $statsMap[$deptKey][$row->status] = (int) $row->count;
            }

            $departments = Department::with('users')->get();

            $report = $departments->map(function ($dept) use ($statsMap) {
                $totalEmployees = $dept->users->count();
                $approvedLeaves = $statsMap[$dept->id]['Approved'] ?? 0;
                $rejectedLeaves = $statsMap[$dept->id]['Rejected'] ?? 0;

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
            $unassignedCount = User::whereNull('department_id')->count();

            if ($unassignedCount > 0) {
                $approvedLeaves = $statsMap['unassigned']['Approved'] ?? 0;
                $rejectedLeaves = $statsMap['unassigned']['Rejected'] ?? 0;

                $obj = new \stdClass();
                $obj->id = null;
                $obj->name = 'Unassigned';
                $obj->total_employees = $unassignedCount;
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
