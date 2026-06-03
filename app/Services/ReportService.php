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
            return User::with([
                'department',
                'leaveBalances' => function ($query) {
                    $query->where('year', date('Y'))->with('leaveType');
                }
            ])
            ->withSum(['leaveRequests as approved_leaves' => function ($query) {
                $query->where('status', 'Approved')->whereYear('start_date', date('Y'));
            }], 'days_requested')
            ->get()
            ->map(function ($user) {
                $user->approved_leaves = $user->approved_leaves ?? 0;
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
            return Department::leftJoin('users', 'departments.id', '=', 'users.department_id')
                ->leftJoin('leave_requests', function ($join) {
                    $join->on('users.id', '=', 'leave_requests.user_id')
                         ->whereYear('leave_requests.start_date', date('Y'));
                })
                ->select('departments.id', 'departments.name')
                ->selectRaw('COUNT(DISTINCT users.id) as total_employees')
                ->selectRaw('COALESCE(SUM(leave_requests.days_requested), 0) as total_leaves')
                ->selectRaw("COALESCE(SUM(CASE WHEN leave_requests.status = 'Approved' THEN leave_requests.days_requested ELSE 0 END), 0) as approved_leaves")
                ->selectRaw("COALESCE(SUM(CASE WHEN leave_requests.status = 'Rejected' THEN leave_requests.days_requested ELSE 0 END), 0) as rejected_leaves")
                ->groupBy('departments.id', 'departments.name')
                ->get();
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
            $monthExpression = $this->monthExtractionSql('start_date');

            return LeaveRequest::selectRaw("{$monthExpression} as month, SUM(days_requested) as count")
                ->whereYear('start_date', date('Y'))
                ->where('status', 'Approved')
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        });
    }

    /**
     * Return a raw SQL expression that extracts the month as a zero-padded
     * two-digit string from the given date column.
     *
     * Supports SQLite, MySQL, MariaDB, and PostgreSQL. Throws for any
     * unsupported driver so a misconfiguration is caught immediately
     * rather than producing silent incorrect results.
     *
     * @param string $column The date/datetime column name.
     * @return string Raw SQL fragment (e.g. "strftime('%m', start_date)").
     */
    private function monthExtractionSql(string $column): string
    {
        $driver = DB::getDriverName();

        return match ($driver) {
            'sqlite' => "strftime('%m', {$column})",
            'mysql', 'mariadb' => "LPAD(MONTH({$column}), 2, '0')",
            'pgsql' => "LPAD(CAST(EXTRACT(MONTH FROM {$column}) AS TEXT), 2, '0')",
            default => throw new \RuntimeException(
                "Unsupported database driver '{$driver}' for month extraction. " .
                "Supported drivers: sqlite, mysql, mariadb, pgsql."
            ),
        };
    }
}
