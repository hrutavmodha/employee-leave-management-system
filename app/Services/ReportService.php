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
    }

    /**
     * Get leave statistics grouped by department.
     */
    public function getDepartmentReport()
    {
        return Department::withCount(['users as total_employees'])
            ->get()
            ->map(function ($dept) {
                $stats = LeaveRequest::whereHas('user', function ($q) use ($dept) {
                    $q->where('department_id', $dept->id);
                })
                ->whereYear('start_date', date('Y'))
                ->selectRaw("
                    SUM(days_requested) as total,
                    SUM(CASE WHEN status = 'Approved' THEN days_requested ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'Rejected' THEN days_requested ELSE 0 END) as rejected
                ")
                ->first();

                $dept->total_leaves = $stats->total ?? 0;
                $dept->approved_leaves = $stats->approved ?? 0;
                $dept->rejected_leaves = $stats->rejected ?? 0;

                return $dept;
            });
    }

    /**
     * Get monthly leave statistics for the current year.
     */
    public function getMonthlyStats()
    {
        return LeaveRequest::selectRaw("strftime('%m', start_date) as month, SUM(days_requested) as count")
            ->whereYear('start_date', date('Y'))
            ->where('status', 'Approved')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }
}
