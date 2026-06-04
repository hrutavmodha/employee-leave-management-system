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
            'start_date' => '2026-06-08', // Monday
            'end_date' => '2026-06-10', // Wednesday
            'days_requested' => 3,
            'status' => 'Approved',
            'reason' => 'Vacation 1',
        ]);
        LeaveRequest::create([
            'user_id' => $user2->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-08', // Monday
            'end_date' => '2026-06-09', // Tuesday
            'days_requested' => 2,
            'status' => 'Rejected',
            'reason' => 'Rejected Sick',
        ]);

        // 4 days Pending (should NOT count as Approved/Rejected, but does count as total_leaves) for Marketing (Dept B)
        LeaveRequest::create([
            'user_id' => $user3->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-08', // Monday
            'end_date' => '2026-06-11', // Thursday
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
        $this->assertEquals(3, $reportA->total_leaves);
        $this->assertEquals(3, $reportA->approved_leaves);
        $this->assertEquals(2, $reportA->rejected_leaves);

        $reportB = $report->firstWhere('name', 'Marketing');
        $this->assertNotNull($reportB);
        $this->assertEquals(1, $reportB->total_employees);
        $this->assertEquals(0, $reportB->total_leaves);
        $this->assertEquals(0, $reportB->approved_leaves);
        $this->assertEquals(0, $reportB->rejected_leaves);
    }

    public function test_reports_page_displays_department_stats_correctly_excluding_rejected_leaves(): void
    {
        Cache::forget('reports.departments');
        Cache::forget('reports.employees');
        Cache::forget('reports.monthly');

        $admin = User::factory()->create(['role' => 'HR/Admin']);
        $dept = Department::create(['name' => 'Design', 'description' => 'UI UX']);
        
        $employee = User::factory()->create([
            'department_id' => $dept->id,
            'role' => 'Employee',
        ]);

        $leaveType = LeaveType::create([
            'name' => 'Sick Leave',
            'allowed_days' => 10,
            'carry_forward' => false,
        ]);

        // Approved leave
        LeaveRequest::create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(1)->format('Y-m-d'),
            'days_requested' => 2,
            'status' => 'Approved',
            'reason' => 'Recovery',
        ]);

        // Rejected leave
        LeaveRequest::create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(7)->format('Y-m-d'),
            'days_requested' => 3,
            'status' => 'Rejected',
            'reason' => 'Not urgent',
        ]);

        // Pending leave
        LeaveRequest::create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(10)->format('Y-m-d'),
            'end_date' => now()->addDays(14)->format('Y-m-d'),
            'days_requested' => 5,
            'status' => 'Pending',
            'reason' => 'Planned vacation',
        ]);

        // Act
        $response = $this->actingAs($admin)->get(route('reports.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewHas('departmentStats', function ($stats) use ($dept) {
            $deptStats = $stats->firstWhere('id', $dept->id);
            return $deptStats && 
                $deptStats->total_leaves == 2 && 
                $deptStats->approved_leaves == 2 && 
                $deptStats->rejected_leaves == 3;
        });
    }

    public function test_database_seeder_assigns_human_resources_department_to_test_user(): void
    {
        // Act: Run the database seeder
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        // Assert: The user 'test@example.com' exists and belongs to 'Human Resources' department
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->department);
        $this->assertEquals('Human Resources', $user->department->name);
    }
}


