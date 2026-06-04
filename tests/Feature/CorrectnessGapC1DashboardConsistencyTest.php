<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\Setting;
use App\Services\ReportService;
use App\Services\LeaveCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Extensive tests for Correctness Gap C#1:
 *
 * The dashboard, reports, and balance adjustments must all read from the
 * statically persisted `leave_request_dates` table — never from dynamic
 * recalculation of working days. This ensures that post-submission changes
 * to weekend/holiday settings do not corrupt leave counts, refunds, or
 * dashboard statistics.
 */
class CorrectnessGapC1DashboardConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private LeaveType $leaveType;
    private LeaveBalance $balance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::factory()->create(['role' => 'Employee']);
        $this->leaveType = LeaveType::create([
            'name' => 'Annual Leave',
            'allowed_days' => 20,
            'carry_forward' => false,
        ]);

        $this->balance = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => 2026,
            'allocated_days' => 20,
            'used_days' => 0,
            'remaining_days' => 20,
        ]);

        Setting::setVal('week_holidays', [0, 6]);
    }

    /**
     * Helper: create an approved leave request with static dates persisted.
     */
    private function createApprovedLeave(
        string $startDate,
        string $endDate,
        int $expectedDays
    ): LeaveRequest {
        $request = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_requested' => $expectedDays,
            'status' => 'Pending',
            'reason' => 'Test leave',
        ]);

        $calcService = app(LeaveCalculationService::class);
        $calcService->deductBalance($request);
        $request->update(['status' => 'Approved']);

        return $request;
    }

    /**
     * Dashboard approved count must equal the report's approved_leaves for the same user.
     *
     * Both should read from leave_request_dates, so their values must agree.
     */
    public function test_dashboard_count_matches_report_service_count(): void
    {
        $request = $this->createApprovedLeave('2026-06-08', '2026-06-12', 5);

        // Verify static dates were persisted
        $storedDateCount = DB::table('leave_request_dates')
            ->where('leave_request_id', $request->id)
            ->count();
        $this->assertEquals(5, $storedDateCount);

        Cache::forget('reports.employees');

        // Dashboard count
        $dashboardCount = DB::table('leave_request_dates')
            ->join('leave_requests', 'leave_request_dates.leave_request_id', '=', 'leave_requests.id')
            ->where('leave_requests.user_id', $this->employee->id)
            ->where('leave_requests.status', 'Approved')
            ->where('leave_request_dates.year', 2026)
            ->count();

        // Report count
        $reportService = new ReportService();
        $report = $reportService->getEmployeeReport();
        $userReport = $report->firstWhere('id', $this->employee->id);

        $this->assertNotNull($userReport);
        $this->assertEquals(5, $dashboardCount);
        $this->assertEquals(5, $userReport->approved_leaves);
        $this->assertEquals($dashboardCount, $userReport->approved_leaves);
    }

    /**
     * After changing weekend settings, dashboard and report counts must
     * remain unchanged because they rely on persisted dates, not dynamic
     * recalculation.
     */
    public function test_dashboard_count_unchanged_after_weekend_settings_change(): void
    {
        $this->createApprovedLeave('2026-06-08', '2026-06-10', 3);

        // Record the count before setting change
        $countBefore = DB::table('leave_request_dates')
            ->join('leave_requests', 'leave_request_dates.leave_request_id', '=', 'leave_requests.id')
            ->where('leave_requests.user_id', $this->employee->id)
            ->where('leave_requests.status', 'Approved')
            ->where('leave_request_dates.year', 2026)
            ->count();

        $this->assertEquals(3, $countBefore);

        // Change weekends to include Mon, Tue, Wed (this would make the
        // original dates all "holidays" if recalculated dynamically)
        Setting::setVal('week_holidays', [1, 2, 3, 0, 6]);
        Cache::forget('reports.employees');

        // Count after setting change — must still be 3
        $countAfter = DB::table('leave_request_dates')
            ->join('leave_requests', 'leave_request_dates.leave_request_id', '=', 'leave_requests.id')
            ->where('leave_requests.user_id', $this->employee->id)
            ->where('leave_requests.status', 'Approved')
            ->where('leave_request_dates.year', 2026)
            ->count();

        $this->assertEquals(3, $countAfter);

        // Report must also still show 3
        $reportService = new ReportService();
        $report = $reportService->getEmployeeReport();
        $userReport = $report->firstWhere('id', $this->employee->id);
        $this->assertEquals(3, $userReport->approved_leaves);
    }

    /**
     * Dashboard shows zero approved days when user has no approved leave requests.
     */
    public function test_dashboard_shows_zero_when_no_approved_leaves(): void
    {
        // Create a pending request — should not count
        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-10',
            'days_requested' => 3,
            'status' => 'Pending',
            'reason' => 'Pending only',
        ]);

        $count = DB::table('leave_request_dates')
            ->join('leave_requests', 'leave_request_dates.leave_request_id', '=', 'leave_requests.id')
            ->where('leave_requests.user_id', $this->employee->id)
            ->where('leave_requests.status', 'Approved')
            ->where('leave_request_dates.year', 2026)
            ->count();

        $this->assertEquals(0, $count);
    }

    /**
     * Dashboard counts only approved leaves, not pending, rejected, or cancelled.
     */
    public function test_dashboard_counts_only_approved_status(): void
    {
        // Approved: 3 days
        $this->createApprovedLeave('2026-06-08', '2026-06-10', 3);

        // Pending: should not count
        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-17',
            'days_requested' => 3,
            'status' => 'Pending',
            'reason' => 'Pending leave',
        ]);

        // Rejected: should not count
        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-06-22',
            'end_date' => '2026-06-24',
            'days_requested' => 3,
            'status' => 'Rejected',
            'reason' => 'Rejected leave',
        ]);

        // Cancelled: should not count
        LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-06-29',
            'end_date' => '2026-07-01',
            'days_requested' => 3,
            'status' => 'Cancelled',
            'reason' => 'Cancelled leave',
        ]);

        $count = DB::table('leave_request_dates')
            ->join('leave_requests', 'leave_request_dates.leave_request_id', '=', 'leave_requests.id')
            ->where('leave_requests.user_id', $this->employee->id)
            ->where('leave_requests.status', 'Approved')
            ->where('leave_request_dates.year', 2026)
            ->count();

        $this->assertEquals(3, $count);
    }

    /**
     * Cross-year leave: the dashboard for year X must count only the dates
     * that fall within year X, not the total days_requested across both years.
     */
    public function test_dashboard_counts_only_current_year_portion_of_cross_year_leave(): void
    {
        // Create balance for 2027 and 2028
        LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => 2027,
            'allocated_days' => 20,
            'used_days' => 0,
            'remaining_days' => 20,
        ]);

        LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => 2028,
            'allocated_days' => 20,
            'used_days' => 0,
            'remaining_days' => 20,
        ]);

        // Create a cross-year leave: Dec 29 2027 (Wed) to Jan 5 2028 (Wed)
        // 2027 working days: Dec 29 (Wed), Dec 30 (Thu), Dec 31 (Fri) = 3 days
        // 2028 working days: Jan 3 (Mon), Jan 4 (Tue), Jan 5 (Wed) = 3 days
        // Jan 1 (Sat) and Jan 2 (Sun) are weekends
        // Total: 6 days
        $request = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2027-12-29',
            'end_date' => '2028-01-05',
            'days_requested' => 6,
            'status' => 'Pending',
            'reason' => 'Cross year leave',
        ]);

        $calcService = app(LeaveCalculationService::class);
        $calcService->deductBalance($request);
        $request->update(['status' => 'Approved']);

        // Check 2027 dates count
        $count2027 = DB::table('leave_request_dates')
            ->join('leave_requests', 'leave_request_dates.leave_request_id', '=', 'leave_requests.id')
            ->where('leave_requests.user_id', $this->employee->id)
            ->where('leave_requests.status', 'Approved')
            ->where('leave_request_dates.year', 2027)
            ->count();

        // Check 2028 dates count
        $count2028 = DB::table('leave_request_dates')
            ->join('leave_requests', 'leave_request_dates.leave_request_id', '=', 'leave_requests.id')
            ->where('leave_requests.user_id', $this->employee->id)
            ->where('leave_requests.status', 'Approved')
            ->where('leave_request_dates.year', 2028)
            ->count();

        $this->assertEquals(3, $count2027);
        $this->assertEquals(3, $count2028);

        // Total static dates must equal total days_requested
        $this->assertEquals(6, $count2027 + $count2028);
    }

    /**
     * Refund uses static dates: after weekend settings change, cancelling an
     * approved leave refunds exactly the original number of working days.
     */
    public function test_refund_uses_static_dates_after_settings_change(): void
    {
        // Create and approve a 3-day leave
        $request = $this->createApprovedLeave('2026-06-08', '2026-06-10', 3);

        $this->balance->refresh();
        $this->assertEquals(3, $this->balance->used_days);
        $this->assertEquals(17, $this->balance->remaining_days);

        // Change weekends so Mon/Tue/Wed are now "holidays"
        Setting::setVal('week_holidays', [1, 2, 3, 0, 6]);

        // Refund must still return exactly 3 days (from stored dates)
        $calcService = app(LeaveCalculationService::class);
        $calcService->refundBalance($request);

        $this->balance->refresh();
        $this->assertEquals(0, $this->balance->used_days);
        $this->assertEquals(20, $this->balance->remaining_days);
    }

    /**
     * Deduction uses static dates: the balance deduction reads from
     * leave_request_dates when they exist, not recalculating dynamically.
     */
    public function test_deduction_uses_static_dates_not_dynamic_calculation(): void
    {
        // Create a pending leave that stores static dates via the model observer
        $request = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-12',
            'days_requested' => 5,
            'status' => 'Pending',
            'reason' => 'Deduction test',
        ]);

        // Verify 5 static dates were stored
        $storedCount = DB::table('leave_request_dates')
            ->where('leave_request_id', $request->id)
            ->count();
        $this->assertEquals(5, $storedCount);

        // Now change weekends to include the weekdays
        Setting::setVal('week_holidays', [1, 2, 3, 4, 5]);

        // Deduct: must still deduct 5 days because it reads static dates
        $calcService = app(LeaveCalculationService::class);
        $calcService->deductBalance($request);

        $this->balance->refresh();
        $this->assertEquals(5, $this->balance->used_days);
        $this->assertEquals(15, $this->balance->remaining_days);
    }

    /**
     * The dashboard endpoint (GET /dashboard) returns the correct approved
     * leave count sourced from leave_request_dates.
     */
    public function test_dashboard_http_endpoint_returns_correct_approved_count(): void
    {
        $this->createApprovedLeave('2026-06-08', '2026-06-10', 3);

        $response = $this->actingAs($this->employee)->get('/dashboard');
        $response->assertStatus(200);

        // The view should receive $stats['approved'] = 3
        $response->assertViewHas('stats', function ($stats) {
            return $stats['approved'] === 3;
        });
    }

    /**
     * Dashboard endpoint returns zero approved for a user with no leaves.
     */
    public function test_dashboard_http_endpoint_zero_for_no_leaves(): void
    {
        $response = $this->actingAs($this->employee)->get('/dashboard');
        $response->assertStatus(200);

        $response->assertViewHas('stats', function ($stats) {
            return $stats['approved'] === 0;
        });
    }

    /**
     * Dashboard endpoint shows correct count after cancelling an approved leave.
     */
    public function test_dashboard_endpoint_after_cancellation(): void
    {
        $request = $this->createApprovedLeave('2026-06-08', '2026-06-10', 3);

        // Cancel the leave
        $this->actingAs($this->employee)->post("/leaves/{$request->id}/cancel");

        $request->refresh();
        $this->assertEquals('Cancelled', $request->status);

        $response = $this->actingAs($this->employee)->get('/dashboard');
        $response->assertStatus(200);

        // After cancellation, approved count should be 0
        $response->assertViewHas('stats', function ($stats) {
            return $stats['approved'] === 0;
        });
    }

    /**
     * Static date records are immutable to setting changes: the
     * leave_request_dates table rows must not change when holiday settings
     * are updated.
     */
    public function test_static_dates_persist_unchanged_after_holiday_setting_change(): void
    {
        $request = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-12',
            'days_requested' => 5,
            'status' => 'Pending',
            'reason' => 'Immutability test',
        ]);

        $datesBefore = DB::table('leave_request_dates')
            ->where('leave_request_id', $request->id)
            ->pluck('date')
            ->sort()
            ->values()
            ->toArray();

        $this->assertCount(5, $datesBefore);

        // Change settings dramatically
        Setting::setVal('week_holidays', [0, 1, 2, 3, 4, 5, 6]);

        // Dates in the table must be unchanged
        $datesAfter = DB::table('leave_request_dates')
            ->where('leave_request_id', $request->id)
            ->pluck('date')
            ->sort()
            ->values()
            ->toArray();

        $this->assertCount(5, $datesAfter);
        $this->assertEquals($datesBefore, $datesAfter);
    }

    /**
     * Monthly stats also read from leave_request_dates and are unaffected
     * by post-submission setting changes.
     */
    public function test_monthly_stats_unaffected_by_setting_changes(): void
    {
        $this->createApprovedLeave('2026-06-08', '2026-06-10', 3);

        Cache::forget('reports.monthly');

        $reportService = new ReportService();
        $statsBefore = $reportService->getMonthlyStats();
        $juneBefore = $statsBefore->firstWhere('month', '06');
        $this->assertNotNull($juneBefore);
        $this->assertEquals(3, $juneBefore->count);

        // Change weekends
        Setting::setVal('week_holidays', [1, 2, 3, 0, 6]);
        Cache::forget('reports.monthly');

        $statsAfter = $reportService->getMonthlyStats();
        $juneAfter = $statsAfter->firstWhere('month', '06');
        $this->assertNotNull($juneAfter);
        $this->assertEquals(3, $juneAfter->count);
    }

    /**
     * Department report uses static dates and remains consistent after
     * weekend setting changes.
     */
    public function test_department_report_unaffected_by_setting_changes(): void
    {
        $department = \App\Models\Department::create(['name' => 'Engineering']);
        $this->employee->update(['department_id' => $department->id]);

        $this->createApprovedLeave('2026-06-08', '2026-06-12', 5);

        Cache::forget('reports.departments');
        $reportService = new ReportService();
        $reportBefore = $reportService->getDepartmentReport();
        $deptBefore = $reportBefore->firstWhere('name', 'Engineering');
        $this->assertEquals(5, $deptBefore->approved_leaves);

        // Change weekends
        Setting::setVal('week_holidays', [1, 2, 3, 4, 5]);
        Cache::forget('reports.departments');

        $reportAfter = $reportService->getDepartmentReport();
        $deptAfter = $reportAfter->firstWhere('name', 'Engineering');
        $this->assertEquals(5, $deptAfter->approved_leaves);
    }
}
