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
            $yearStart = "{$currentYear}-01-01";
            $yearEnd = "{$currentYear}-12-31";

            return User::with([
                'department',
                'leaveBalances' => function ($query) use ($currentYear) {
                    $query->where('year', $currentYear)->with('leaveType');
                },
                'leaveRequests' => function ($query) use ($yearStart, $yearEnd) {
                    $query->where('status', 'Approved')
                          ->where('start_date', '<=', $yearEnd)
                          ->where('end_date', '>=', $yearStart);
                }
            ])
            ->get()
            ->map(function ($user) use ($currentYear) {
                $approvedLeaves = 0;
                foreach ($user->leaveRequests as $req) {
                    $dist = $this->getWorkingDaysDistribution($req);
                    $approvedLeaves += $dist['years'][$currentYear] ?? 0;
                }
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
            $yearStart = "{$currentYear}-01-01";
            $yearEnd = "{$currentYear}-12-31";

            $departments = Department::with([
                'users.leaveRequests' => function ($query) use ($yearStart, $yearEnd) {
                    $query->whereIn('status', ['Approved', 'Rejected'])
                          ->where('start_date', '<=', $yearEnd)
                          ->where('end_date', '>=', $yearStart);
                }
            ])->get();

            $report = $departments->map(function ($dept) use ($currentYear) {
                $totalEmployees = $dept->users->count();
                $approvedLeaves = 0;
                $rejectedLeaves = 0;

                foreach ($dept->users as $user) {
                    foreach ($user->leaveRequests as $req) {
                        $dist = $this->getWorkingDaysDistribution($req);
                        $daysInYear = $dist['years'][$currentYear] ?? 0;
                        if ($req->status === 'Approved') {
                            $approvedLeaves += $daysInYear;
                        } elseif ($req->status === 'Rejected') {
                            $rejectedLeaves += $daysInYear;
                        }
                    }
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
            $unassignedUsers = User::whereNull('department_id')
                ->with(['leaveRequests' => function ($query) use ($yearStart, $yearEnd) {
                    $query->whereIn('status', ['Approved', 'Rejected'])
                          ->where('start_date', '<=', $yearEnd)
                          ->where('end_date', '>=', $yearStart);
                }])
                ->get();

            if ($unassignedUsers->isNotEmpty()) {
                $totalEmployees = $unassignedUsers->count();
                $approvedLeaves = 0;
                $rejectedLeaves = 0;

                foreach ($unassignedUsers as $user) {
                    foreach ($user->leaveRequests as $req) {
                        $dist = $this->getWorkingDaysDistribution($req);
                        $daysInYear = $dist['years'][$currentYear] ?? 0;
                        if ($req->status === 'Approved') {
                            $approvedLeaves += $daysInYear;
                        } elseif ($req->status === 'Rejected') {
                            $rejectedLeaves += $daysInYear;
                        }
                    }
                }

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
            $yearStart = "{$currentYear}-01-01";
            $yearEnd = "{$currentYear}-12-31";

            $requests = LeaveRequest::where('status', 'Approved')
                ->where('start_date', '<=', $yearEnd)
                ->where('end_date', '>=', $yearStart)
                ->get();

            $monthCounts = [];

            foreach ($requests as $req) {
                $dist = $this->getWorkingDaysDistribution($req);
                foreach ($dist['months'] as $monthKey => $days) {
                    list($yr, $mo) = explode('-', $monthKey);
                    if ((int)$yr === $currentYear) {
                        $monthCounts[$mo] = ($monthCounts[$mo] ?? 0) + $days;
                    }
                }
            }

            $stats = [];
            foreach ($monthCounts as $mo => $count) {
                $obj = new \stdClass();
                $obj->month = $mo;
                $obj->count = $count;
                $stats[] = $obj;
            }

            usort($stats, function ($a, $b) {
                return strcmp($a->month, $b->month);
            });

            return collect($stats);
        });
    }

    /**
     * Compute the distribution of working days per month/year for a given LeaveRequest.
     *
     * @param LeaveRequest $req
     * @return array
     */
    private function getWorkingDaysDistribution(LeaveRequest $req): array
    {
        $start = \Carbon\Carbon::parse($req->start_date)->startOfDay();
        $end = \Carbon\Carbon::parse($req->end_date)->startOfDay();

        $weekHolidays = array_map('intval', \App\Models\Setting::getVal('week_holidays', [0, 6]));
        $publicHolidays = \App\Models\PublicHoliday::whereBetween('date', [
            $start->toDateString(),
            $end->toDateString()
        ])->pluck('date')->map(function ($date) {
            return $date instanceof \Carbon\Carbon
                ? $date->format('Y-m-d')
                : \Carbon\Carbon::parse($date)->format('Y-m-d');
        })->toArray();

        $distribution = [
            'years' => [],
            'months' => [],
        ];

        $current = $start->copy();
        while ($current->lte($end)) {
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

            $year = $current->year;
            $ym = $current->format('Y-m');

            $distribution['years'][$year] = ($distribution['years'][$year] ?? 0) + 1;
            $distribution['months'][$ym] = ($distribution['months'][$ym] ?? 0) + 1;

            $current->addDay();
        }

        return $distribution;
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
