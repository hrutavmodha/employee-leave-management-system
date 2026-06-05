<?php

namespace App\Services;

use App\Models\User;
use App\Models\Department;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getEmployeeReport($perPage = 15)
    {
        $page = request()->get('page', 1);
        $hasCache = \Illuminate\Support\Facades\Cache::has('reports.employees');
        if (!$hasCache) {
            \Illuminate\Support\Facades\Cache::forget('reports.employees.version');
            \Illuminate\Support\Facades\Cache::put('reports.employees', true, 3600);
        }

        $version = \Illuminate\Support\Facades\Cache::remember('reports.employees.version', 3600, function () {
            return time();
        });

        return \Illuminate\Support\Facades\Cache::remember("reports.employees.v{$version}.page.{$page}", 3600, function () use ($perPage) {
            $currentYear = (int) date('Y');

            $users = User::select('id', 'first_name', 'last_name', 'department_id')
                ->with([
                    'department:id,name',
                    'leaveBalances' => function ($query) use ($currentYear) {
                        $query->select('id', 'user_id', 'leave_type_id', 'remaining_days', 'year')
                            ->where('year', $currentYear)
                            ->with('leaveType:id,name');
                    }
                ])
                ->paginate($perPage);

            $userIds = $users->pluck('id')->toArray();

            // Fetch approved leave day counts ONLY for the paginated subset of users
            $approvedCounts = DB::table('leave_request_dates')
                ->join('leave_requests', 'leave_request_dates.leave_request_id', '=', 'leave_requests.id')
                ->whereIn('leave_requests.user_id', $userIds)
                ->where('leave_requests.status', 'Approved')
                ->where('leave_request_dates.year', $currentYear)
                ->selectRaw('leave_requests.user_id, count(*) as count')
                ->groupBy('leave_requests.user_id')
                ->pluck('count', 'user_id')
                ->toArray();

            $users->through(function ($user) use ($approvedCounts) {
                $user->approved_leaves = $approvedCounts[$user->id] ?? 0;
                return $user;
            });

            return new CustomPaginator(
                $users->items(),
                $users->total(),
                $users->perPage(),
                $users->currentPage(),
                $users->getOptions()
            );
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

            // Use withCount to fetch department employee count without hydrating user models
            $departments = Department::withCount('users')->get();

            $report = $departments->map(function ($dept) use ($statsMap) {
                $totalEmployees = $dept->users_count;
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

class CustomPaginator extends \Illuminate\Pagination\LengthAwarePaginator
{
    public function firstWhere(...$args)
    {
        return $this->getCollection()->firstWhere(...$args);
    }
}
