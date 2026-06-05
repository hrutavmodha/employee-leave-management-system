<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\Setting;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuditCorrectnessGapsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Correctness Gap C#1:
     * Storing static request dates prevents report discrepancies and refund mismatches
     * when weekend/holiday settings are dynamically changed later.
     */
    public function test_leave_counts_unaffected_by_dynamic_setting_changes(): void
    {
        // 1. Arrange: Create user, leave type, and initialized balance
        $user = User::factory()->create();
        $leaveType = LeaveType::create([
            'name' => 'Vacation',
            'allowed_days' => 15,
            'carry_forward' => false,
        ]);

        $balance = LeaveBalance::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'allocated_days' => 15,
            'used_days' => 0,
            'remaining_days' => 15,
        ]);

        // Default week_holidays is [0, 6] (Saturday & Sunday)
        Setting::setVal('week_holidays', [0, 6]);

        // Apply for 3 days of leave (2026-06-08 Monday, 2026-06-09 Tuesday, 2026-06-10 Wednesday)
        $request = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-10',
            'days_requested' => 3,
            'status' => 'Pending',
            'reason' => 'Trip',
        ]);

        // Approve request to deduct balance
        $calcService = app(\App\Services\LeaveCalculationService::class);
        $calcService->deductBalance($request);
        $request->update(['status' => 'Approved']);

        $balance->refresh();
        $this->assertEquals(3, $balance->used_days);
        $this->assertEquals(12, $balance->remaining_days);

        // 2. Act: Dynamic Settings Change
        // Change week_holidays setting: make Monday (1), Tuesday (2), Wednesday (3) weekend holidays
        Setting::setVal('week_holidays', [1, 2, 3, 0, 6]);
        Cache::forget('reports.employees');

        // Check report service statistics: it should still report 3 days of approved leaves for 2026
        $reportService = new ReportService();
        $report = $reportService->getEmployeeReport();
        $userReport = $report->firstWhere('id', $user->id);

        $this->assertNotNull($userReport);
        $this->assertEquals(3, $userReport->approved_leaves); // Still 3, not 0!

        // Cancel the leave request and check refund: it must refund exactly 3 days
        $calcService->refundBalance($request);
        $balance->refresh();

        $this->assertEquals(0, $balance->used_days);
        $this->assertEquals(15, $balance->remaining_days); // Refunded 3 days successfully!
    }

    /**
     * Test Correctness Gap C#2:
     * Carry-forward balances update dynamically in subsequent years when previous year balance changes.
     */
    public function test_carry_forward_balances_sync_on_prior_year_balance_change(): void
    {
        $user = User::factory()->create();
        $leaveType = LeaveType::create([
            'name' => 'Carry Forward Leave',
            'allowed_days' => 15,
            'carry_forward' => true,
        ]);

        // Initialize 2026 balance
        $balance2026 = LeaveBalance::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'allocated_days' => 15,
            'used_days' => 5,
            'remaining_days' => 10,
        ]);

        // Initialize 2027 balance: rolls over 10 remaining from 2026 -> 15 + 10 = 25
        $balance2027 = LeaveBalance::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2027,
            'allocated_days' => 25,
            'used_days' => 0,
            'remaining_days' => 25,
        ]);

        // Initialize 2028 balance: rolls over 25 remaining from 2027 -> 15 + 25 = 40
        $balance2028 = LeaveBalance::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2028,
            'allocated_days' => 40,
            'used_days' => 0,
            'remaining_days' => 40,
        ]);

        // Act: Modify the remaining days for 2026 retrospectively (e.g. increase remaining by 3 days)
        $balance2026->update([
            'used_days' => 2,
            'remaining_days' => 13,
        ]);

        // Assert: 2027 and 2028 balances should automatically propagate the delta (+3)
        $balance2027->refresh();
        $this->assertEquals(28, $balance2027->allocated_days);
        $this->assertEquals(28, $balance2027->remaining_days);

        $balance2028->refresh();
        $this->assertEquals(43, $balance2028->allocated_days);
        $this->assertEquals(43, $balance2028->remaining_days);
    }

    /**
     * Test Correctness Gap 1.2:
     * Broken Carry-Forward Chain for Skip-Years.
     */
    public function test_skip_years_preserves_carry_forward_chain(): void
    {
        $user = User::factory()->create([
            'joining_date' => '2024-01-01',
        ]);

        $leaveType = LeaveType::create([
            'name' => 'Skip Year Vacation',
            'allowed_days' => 10,
            'carry_forward' => true,
        ]);

        // We initialize balance for year 2026.
        // The user was created in 2024.
        // It should initialize 2024, 2025, and 2026 balance records, and correctly cascade the carry-forward.
        // 2024 allowed: 10
        // 2025 allowed: 10 + 10 = 20
        // 2026 allowed: 10 + 20 = 30
        $calcService = app(\App\Services\LeaveCalculationService::class);
        $balance2026 = $calcService->getOrCreateBalance($user, $leaveType->id, 2026);

        $this->assertEquals(30, $balance2026->allocated_days);
        $this->assertEquals(30, $balance2026->remaining_days);

        // Assert database has 2024 and 2025 balances as well
        $this->assertDatabaseHas('leave_balances', [
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2024,
            'allocated_days' => 10,
        ]);

        $this->assertDatabaseHas('leave_balances', [
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2025,
            'allocated_days' => 20,
        ]);
    }

    /**
     * Test Correctness Gap 1.1:
     * Concurrency & Race Conditions in Leave Status Transitions.
     */
    public function test_cannot_approve_non_pending_leave_request(): void
    {
        $manager = User::factory()->create(['role' => 'Manager']);
        $employee = User::factory()->create(['manager_id' => $manager->id]);
        $leaveType = LeaveType::create([
            'name' => 'Sick Leave',
            'allowed_days' => 10,
            'carry_forward' => false
        ]);

        \App\Models\LeaveBalance::create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => (int) date('Y'),
            'allocated_days' => 10,
            'used_days' => 0,
            'remaining_days' => 10,
        ]);

        // Create an Approved request
        $request = LeaveRequest::create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => \Carbon\Carbon::tomorrow()->format('Y-m-d'),
            'end_date' => \Carbon\Carbon::tomorrow()->format('Y-m-d'),
            'days_requested' => 1,
            'reason' => 'Sick',
            'status' => 'Approved',
        ]);

        // Try to approve it again -> should redirect back with error
        $response = $this->actingAs($manager)->post("/approvals/{$request->id}/approve", [
            'manager_comment' => 'Duplicate approval',
        ]);

        $response->assertRedirect(route('approvals.index'));
        $response->assertSessionHas('error', 'Only pending leave requests can be approved.');
    }

    public function test_cannot_cancel_non_pending_or_approved_leave_request(): void
    {
        $user = User::factory()->create();
        $leaveType = LeaveType::create([
            'name' => 'Sick Leave',
            'allowed_days' => 10,
            'carry_forward' => false
        ]);

        // Create a Rejected request
        $request = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => \Carbon\Carbon::tomorrow()->format('Y-m-d'),
            'end_date' => \Carbon\Carbon::tomorrow()->format('Y-m-d'),
            'days_requested' => 1,
            'reason' => 'Sick',
            'status' => 'Rejected',
        ]);

        // Try to cancel a rejected request -> should fail
        $response = $this->actingAs($user)->post("/leaves/{$request->id}/cancel");
        $response->assertRedirect(route('leaves.index'));
        $response->assertSessionHas('error', 'Cancellation failed: Only pending or approved requests can be cancelled.');
    }
}
