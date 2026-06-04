<?php

namespace Tests\Unit;

use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MonthlyStatsPortabilityTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportService = new ReportService();
    }

    /**
     * End-to-end: getMonthlyStats returns correctly grouped and ordered
     * data for approved leaves spread across multiple months.
     */
    public function test_get_monthly_stats_groups_by_month_correctly(): void
    {
        Cache::flush();

        $department = Department::create(['name' => 'Engineering']);
        $leaveType = LeaveType::create([
            'name' => 'Annual',
            'allowed_days' => 20,
        ]);

        $user = User::factory()->create([
            'department_id' => $department->id,
            'role' => 'Employee',
        ]);

        $year = date('Y');

        // January: 3 days approved (Mon Jan 12 to Wed Jan 14)
        LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => "{$year}-01-12",
            'end_date' => "{$year}-01-14",
            'days_requested' => 3,
            'reason' => 'Vacation',
            'status' => 'Approved',
        ]);

        // March: 2 days approved
        LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => "{$year}-03-05",
            'end_date' => "{$year}-03-06",
            'days_requested' => 2,
            'reason' => 'Personal',
            'status' => 'Approved',
        ]);

        // March: 1 more day approved (same month, should aggregate)
        LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => "{$year}-03-20",
            'end_date' => "{$year}-03-20",
            'days_requested' => 1,
            'reason' => 'Errand',
            'status' => 'Approved',
        ]);

        // June: 5 days but REJECTED — must not appear
        LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => "{$year}-06-01",
            'end_date' => "{$year}-06-05",
            'days_requested' => 5,
            'reason' => 'Holiday',
            'status' => 'Rejected',
        ]);

        $results = $this->reportService->getMonthlyStats();

        // Only January and March should appear (June is rejected)
        $this->assertCount(2, $results);

        $january = $results->firstWhere('month', '01');
        $march = $results->firstWhere('month', '03');

        $this->assertNotNull($january, 'January row must exist.');
        $this->assertNotNull($march, 'March row must exist.');

        $this->assertEquals(3, $january->count);
        $this->assertEquals(3, $march->count); // 2 + 1

        // Verify ordering: January before March
        $this->assertSame('01', $results->first()->month);
        $this->assertSame('03', $results->last()->month);
    }

    /**
     * End-to-end: getMonthlyStats returns empty collection when no
     * approved leaves exist.
     */
    public function test_get_monthly_stats_returns_empty_when_no_approved_leaves(): void
    {
        Cache::flush();

        $results = $this->reportService->getMonthlyStats();

        $this->assertTrue($results->isEmpty());
    }
}
