<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Department;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EfficiencyGapsTest extends TestCase
{
    use RefreshDatabase;

    private ReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportService = new ReportService();
    }

    /**
     * Test 3.1: Non-Paginated Eager Loading in Employee Report (OOM Risk).
     * Verifies that the report uses LengthAwarePaginator, respects the perPage setting,
     * and correctly calculates total employee count.
     */
    public function test_employee_report_is_paginated_and_custom_paginator_works(): void
    {
        Cache::forget('reports.employees');

        // Create 20 users
        User::factory()->count(20)->create();

        // Retrieve employee report with pagination limit of 12
        $report = $this->reportService->getEmployeeReport(12);

        // Verify it is an instance of LengthAwarePaginator and supports firstWhere macro/method
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $report);
        $this->assertEquals(12, $report->perPage());
        $this->assertEquals(20, $report->total());
        $this->assertCount(12, $report->items());

        // Verify custom firstWhere collection method delegates correctly on the paginator
        $firstUser = User::first();
        $matched = $report->firstWhere('id', $firstUser->id);
        $this->assertNotNull($matched);
        $this->assertEquals($firstUser->name, $matched->name);
    }

    /**
     * Test 3.2: Inefficient Department Report Queries (Hydration Bottleneck).
     * Verifies that the department stats map matches the user count.
     */
    public function test_department_report_aggregates_total_employees_correctly(): void
    {
        Cache::forget('reports.departments');

        $dept = Department::create(['name' => 'Support', 'description' => 'Customer Care']);
        User::factory()->count(6)->create(['department_id' => $dept->id]);

        $report = $this->reportService->getDepartmentReport();
        $deptStats = $report->firstWhere('name', 'Support');

        $this->assertNotNull($deptStats);
        $this->assertEquals(6, $deptStats->total_employees);
    }
}
