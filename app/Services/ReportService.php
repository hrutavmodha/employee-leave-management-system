<?php

namespace App\Services;

use App\Models\User;
use App\Models\Department;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Get summary of leaves for all employees.
     */
    public function getEmployeeReport()
    {
        return User::with(['department', 'leaveBalances.leaveType'])
            ->withCount(['leaveRequests as approved_leaves' => function ($query) {
                $query->where('status', 'Approved');
            }])
            ->get();
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
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
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
        return LeaveRequest::selectRaw("strftime('%m', start_date) as month, COUNT(*) as count")
            ->whereYear('start_date', date('Y'))
            ->where('status', 'Approved')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }
}
