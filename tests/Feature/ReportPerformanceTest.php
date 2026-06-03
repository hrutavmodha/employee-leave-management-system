<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Department;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ReportPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected ReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReportService();
    }

    public function test_get_department_report_aggregates_stats_correctly(): void
    {
        // Arrange
        Cache::forget('reports.departments');

        $deptA = Department::create(['name' => 'Engineering', 'description' => 'Developers']);
        $deptB = Department::create(['name' => 'Marketing', 'description' => 'Sales']);

        $user1 = User::factory()->create(['department_id' => $deptA->id]);
        $user2 = User::factory()->create(['department_id' => $deptA->id]);
        $user3 = User::factory()->create(['department_id' => $deptB->id]);

        $leaveType = LeaveType::create([
            'name' => 'Vacation',
            'allowed_days' => 15,
            'carry_forward' => false,
        ]);

        // 3 days Approved, 2 days Rejected for Engineering (Dept A)
        LeaveRequest::create([
            'user_id' => $user1->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(2)->format('Y-m-d'),
            'days_requested' => 3,
            'status' => 'Approved',
            'reason' => 'Vacation 1',
        ]);
        LeaveRequest::create([
            'user_id' => $user2->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(1)->format('Y-m-d'),
            'days_requested' => 2,
            'status' => 'Rejected',
            'reason' => 'Rejected Sick',
        ]);

        // 4 days Pending (should NOT count as Approved/Rejected, but does count as total_leaves) for Marketing (Dept B)
        LeaveRequest::create([
            'user_id' => $user3->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(3)->format('Y-m-d'),
            'days_requested' => 4,
            'status' => 'Pending',
            'reason' => 'Pending Trip',
        ]);

        // Act
        $report = $this->service->getDepartmentReport();

        // Assert
        $this->assertCount(2, $report);

        $reportA = $report->firstWhere('name', 'Engineering');
        $this->assertNotNull($reportA);
        $this->assertEquals(2, $reportA->total_employees);
        $this->assertEquals(5, $reportA->total_leaves);
        $this->assertEquals(3, $reportA->approved_leaves);
        $this->assertEquals(2, $reportA->rejected_leaves);

        $reportB = $report->firstWhere('name', 'Marketing');
        $this->assertNotNull($reportB);
        $this->assertEquals(1, $reportB->total_employees);
        $this->assertEquals(4, $reportB->total_leaves);
        $this->assertEquals(0, $reportB->approved_leaves);
        $this->assertEquals(0, $reportB->rejected_leaves);
    }
}
