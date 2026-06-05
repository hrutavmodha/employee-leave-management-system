<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Services\LeaveCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Extensive tests for Correctness Gap C#2:
 *
 * When a previous year's leave balance changes (via retrospective
 * cancellation, approval, or manual adjustment), the next year's
 * carry-forward balance must be automatically updated by the
 * LeaveBalance Eloquent observer. Changes must cascade through
 * multiple subsequent years.
 */
class CorrectnessGapC2CarryForwardTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;
    private LeaveType $carryType;
    private LeaveType $nonCarryType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = User::factory()->create(['role' => 'Employee']);

        $this->carryType = LeaveType::create([
            'name' => 'Carry Forward Annual',
            'allowed_days' => 15,
            'carry_forward' => true,
        ]);

        $this->nonCarryType = LeaveType::create([
            'name' => 'Sick Leave',
            'allowed_days' => 10,
            'carry_forward' => false,
        ]);
    }

    /**
     * When remaining_days for a carry-forward type increases in year Y,
     * the next year (Y+1) balance automatically increases by the same delta.
     */
    public function test_increasing_prior_year_remaining_propagates_to_next_year(): void
    {
        $balance2026 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2026,
            'allocated_days' => 15,
            'used_days' => 5,
            'remaining_days' => 10,
        ]);

        $balance2027 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2027,
            'allocated_days' => 25,
            'used_days' => 0,
            'remaining_days' => 25,
        ]);

        // Retrospective change: used_days decreases by 3, remaining increases by 3
        $balance2026->update([
            'used_days' => 2,
            'remaining_days' => 13,
        ]);

        $balance2027->refresh();
        $this->assertEquals(28, $balance2027->allocated_days);
        $this->assertEquals(28, $balance2027->remaining_days);
    }

    /**
     * When remaining_days for a carry-forward type decreases in year Y,
     * the next year (Y+1) balance automatically decreases by the same delta.
     */
    public function test_decreasing_prior_year_remaining_propagates_to_next_year(): void
    {
        $balance2026 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2026,
            'allocated_days' => 15,
            'used_days' => 5,
            'remaining_days' => 10,
        ]);

        $balance2027 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2027,
            'allocated_days' => 25,
            'used_days' => 0,
            'remaining_days' => 25,
        ]);

        // Retrospective change: used_days increases by 4, remaining decreases by 4
        $balance2026->update([
            'used_days' => 9,
            'remaining_days' => 6,
        ]);

        $balance2027->refresh();
        $this->assertEquals(21, $balance2027->allocated_days);
        $this->assertEquals(21, $balance2027->remaining_days);
    }

    /**
     * Changes cascade through three consecutive years:
     * Y → Y+1 → Y+2.
     */
    public function test_carry_forward_cascades_through_three_years(): void
    {
        $balance2026 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2026,
            'allocated_days' => 15,
            'used_days' => 5,
            'remaining_days' => 10,
        ]);

        $balance2027 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2027,
            'allocated_days' => 25,
            'used_days' => 0,
            'remaining_days' => 25,
        ]);

        $balance2028 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2028,
            'allocated_days' => 40,
            'used_days' => 0,
            'remaining_days' => 40,
        ]);

        // Delta: +3 in 2026
        $balance2026->update([
            'used_days' => 2,
            'remaining_days' => 13,
        ]);

        $balance2027->refresh();
        $this->assertEquals(28, $balance2027->allocated_days);
        $this->assertEquals(28, $balance2027->remaining_days);

        $balance2028->refresh();
        $this->assertEquals(43, $balance2028->allocated_days);
        $this->assertEquals(43, $balance2028->remaining_days);
    }

    /**
     * Non-carry-forward leave types must NOT propagate changes to the next year.
     */
    public function test_non_carry_forward_type_does_not_propagate(): void
    {
        $balance2026 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->nonCarryType->id,
            'year' => 2026,
            'allocated_days' => 10,
            'used_days' => 3,
            'remaining_days' => 7,
        ]);

        $balance2027 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->nonCarryType->id,
            'year' => 2027,
            'allocated_days' => 10,
            'used_days' => 0,
            'remaining_days' => 10,
        ]);

        // Change 2026 remaining_days: delta = +3
        $balance2026->update([
            'used_days' => 0,
            'remaining_days' => 10,
        ]);

        $balance2027->refresh();
        // Must NOT change for non-carry-forward type
        $this->assertEquals(10, $balance2027->allocated_days);
        $this->assertEquals(10, $balance2027->remaining_days);
    }

    /**
     * If the next year's balance record does not exist, the observer must
     * NOT crash — it should silently do nothing.
     */
    public function test_no_crash_when_next_year_balance_missing(): void
    {
        $balance2026 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2026,
            'allocated_days' => 15,
            'used_days' => 5,
            'remaining_days' => 10,
        ]);

        // No 2027 balance exists — this must not throw an exception
        $balance2026->update([
            'used_days' => 2,
            'remaining_days' => 13,
        ]);

        // Verify 2026 was updated correctly
        $balance2026->refresh();
        $this->assertEquals(13, $balance2026->remaining_days);
        $this->assertEquals(2, $balance2026->used_days);

        // And no spurious record was created for 2027
        $balance2027 = LeaveBalance::where('user_id', $this->employee->id)
            ->where('leave_type_id', $this->carryType->id)
            ->where('year', 2027)
            ->first();
        $this->assertNull($balance2027);
    }

    /**
     * Zero delta (remaining_days updated to the same value) should NOT
     * trigger any propagation.
     */
    public function test_zero_delta_does_not_propagate(): void
    {
        $balance2026 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2026,
            'allocated_days' => 15,
            'used_days' => 5,
            'remaining_days' => 10,
        ]);

        $balance2027 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2027,
            'allocated_days' => 25,
            'used_days' => 0,
            'remaining_days' => 25,
        ]);

        // Update 2026 with same remaining_days
        $balance2026->update([
            'used_days' => 5,
            'remaining_days' => 10,
        ]);

        $balance2027->refresh();
        $this->assertEquals(25, $balance2027->allocated_days);
        $this->assertEquals(25, $balance2027->remaining_days);
    }

    /**
     * When next year's balance already has used_days, the cascade must
     * adjust allocated_days and remaining_days while preserving used_days.
     */
    public function test_cascade_preserves_next_year_used_days(): void
    {
        $balance2026 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2026,
            'allocated_days' => 15,
            'used_days' => 5,
            'remaining_days' => 10,
        ]);

        $balance2027 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2027,
            'allocated_days' => 25,
            'used_days' => 3,
            'remaining_days' => 22,
        ]);

        // Delta: +5 (remaining goes from 10 to 15)
        $balance2026->update([
            'used_days' => 0,
            'remaining_days' => 15,
        ]);

        $balance2027->refresh();
        $this->assertEquals(30, $balance2027->allocated_days);
        $this->assertEquals(27, $balance2027->remaining_days);
        $this->assertEquals(3, $balance2027->used_days);
    }

    /**
     * Carry-forward propagation triggered by a real leave cancellation
     * (refund scenario): cancelling a 2026 approved leave refunds the
     * balance for 2026 which then cascades carry-forward to 2027.
     */
    public function test_carry_forward_triggered_by_leave_cancellation_refund(): void
    {
        \App\Models\Setting::setVal('week_holidays', [0, 6]);

        $balance2026 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2026,
            'allocated_days' => 15,
            'used_days' => 0,
            'remaining_days' => 15,
        ]);

        $balance2027 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2027,
            'allocated_days' => 30,
            'used_days' => 0,
            'remaining_days' => 30,
        ]);

        // Create and approve a 3-day leave in 2026
        $request = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-10',
            'days_requested' => 3,
            'status' => 'Pending',
            'reason' => 'Trip',
        ]);

        $calcService = app(LeaveCalculationService::class);
        $calcService->deductBalance($request);
        $request->update(['status' => 'Approved']);

        $balance2026->refresh();
        $this->assertEquals(3, $balance2026->used_days);
        $this->assertEquals(12, $balance2026->remaining_days);

        // Deduction of 3 from 2026 causes delta -3 to cascade to 2027
        $balance2027->refresh();
        $this->assertEquals(27, $balance2027->allocated_days);
        $this->assertEquals(27, $balance2027->remaining_days);

        // Now cancel the leave (refund)
        DB::transaction(function () use ($calcService, $request) {
            $calcService->refundBalance($request);
            $request->update(['status' => 'Cancelled']);
        });

        // 2026 should be fully restored
        $balance2026->refresh();
        $this->assertEquals(0, $balance2026->used_days);
        $this->assertEquals(15, $balance2026->remaining_days);

        // 2027 should also be restored via cascade
        $balance2027->refresh();
        $this->assertEquals(30, $balance2027->allocated_days);
        $this->assertEquals(30, $balance2027->remaining_days);
    }

    /**
     * initializeBalances correctly incorporates carry-forward from the
     * previous year's remaining_days.
     */
    public function test_initialize_balances_applies_carry_forward(): void
    {
        $balance2026 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2026,
            'allocated_days' => 15,
            'used_days' => 7,
            'remaining_days' => 8,
        ]);

        $calcService = app(LeaveCalculationService::class);
        $calcService->initializeBalances($this->employee, 2027);

        $balance2027 = LeaveBalance::where('user_id', $this->employee->id)
            ->where('leave_type_id', $this->carryType->id)
            ->where('year', 2027)
            ->first();

        $this->assertNotNull($balance2027);
        $this->assertEquals(23, $balance2027->allocated_days);
        $this->assertEquals(23, $balance2027->remaining_days);
        $this->assertEquals(0, $balance2027->used_days);
    }

    /**
     * initializeBalances does NOT carry forward for non-carry-forward types.
     */
    public function test_initialize_balances_does_not_carry_forward_for_non_carry_type(): void
    {
        LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->nonCarryType->id,
            'year' => 2026,
            'allocated_days' => 10,
            'used_days' => 3,
            'remaining_days' => 7,
        ]);

        $calcService = app(LeaveCalculationService::class);
        $calcService->initializeBalances($this->employee, 2027);

        $balance2027 = LeaveBalance::where('user_id', $this->employee->id)
            ->where('leave_type_id', $this->nonCarryType->id)
            ->where('year', 2027)
            ->first();

        $this->assertNotNull($balance2027);
        $this->assertEquals(10, $balance2027->allocated_days);
        $this->assertEquals(10, $balance2027->remaining_days);
        $this->assertEquals(0, $balance2027->used_days);
    }

    /**
     * initializeBalances is idempotent: calling it twice does not duplicate
     * or alter existing balance records.
     */
    public function test_initialize_balances_is_idempotent(): void
    {
        LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2026,
            'allocated_days' => 15,
            'used_days' => 5,
            'remaining_days' => 10,
        ]);

        $calcService = app(LeaveCalculationService::class);
        $calcService->initializeBalances($this->employee, 2027);
        $calcService->initializeBalances($this->employee, 2027);

        $balanceCount = LeaveBalance::where('user_id', $this->employee->id)
            ->where('leave_type_id', $this->carryType->id)
            ->where('year', 2027)
            ->count();

        $this->assertEquals(1, $balanceCount);
    }

    /**
     * Multiple carry-forward types are handled independently: changing
     * remaining_days for type A must not affect type B's next-year balance.
     */
    public function test_carry_forward_is_per_leave_type(): void
    {
        $carryTypeB = LeaveType::create([
            'name' => 'Carry Forward Vacation',
            'allowed_days' => 20,
            'carry_forward' => true,
        ]);

        // Type A balances
        $balanceA_2026 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2026,
            'allocated_days' => 15,
            'used_days' => 5,
            'remaining_days' => 10,
        ]);

        $balanceA_2027 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2027,
            'allocated_days' => 25,
            'used_days' => 0,
            'remaining_days' => 25,
        ]);

        // Type B balances
        $balanceB_2026 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $carryTypeB->id,
            'year' => 2026,
            'allocated_days' => 20,
            'used_days' => 10,
            'remaining_days' => 10,
        ]);

        $balanceB_2027 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $carryTypeB->id,
            'year' => 2027,
            'allocated_days' => 30,
            'used_days' => 0,
            'remaining_days' => 30,
        ]);

        // Change only type A: delta = +5
        $balanceA_2026->update([
            'used_days' => 0,
            'remaining_days' => 15,
        ]);

        // Type A 2027 should update
        $balanceA_2027->refresh();
        $this->assertEquals(30, $balanceA_2027->allocated_days);
        $this->assertEquals(30, $balanceA_2027->remaining_days);

        // Type B 2027 must NOT be affected
        $balanceB_2027->refresh();
        $this->assertEquals(30, $balanceB_2027->allocated_days);
        $this->assertEquals(30, $balanceB_2027->remaining_days);
    }

    /**
     * Multiple users: carry-forward for user A must not affect user B.
     */
    public function test_carry_forward_is_per_user(): void
    {
        $userB = User::factory()->create(['role' => 'Employee']);

        // User A
        $balanceA_2026 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2026,
            'allocated_days' => 15,
            'used_days' => 5,
            'remaining_days' => 10,
        ]);

        $balanceA_2027 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2027,
            'allocated_days' => 25,
            'used_days' => 0,
            'remaining_days' => 25,
        ]);

        // User B
        $balanceB_2026 = LeaveBalance::create([
            'user_id' => $userB->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2026,
            'allocated_days' => 15,
            'used_days' => 5,
            'remaining_days' => 10,
        ]);

        $balanceB_2027 = LeaveBalance::create([
            'user_id' => $userB->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2027,
            'allocated_days' => 25,
            'used_days' => 0,
            'remaining_days' => 25,
        ]);

        // Update user A only: delta = +3
        $balanceA_2026->update([
            'used_days' => 2,
            'remaining_days' => 13,
        ]);

        // User A 2027 should update
        $balanceA_2027->refresh();
        $this->assertEquals(28, $balanceA_2027->allocated_days);

        // User B 2027 must NOT be affected
        $balanceB_2027->refresh();
        $this->assertEquals(25, $balanceB_2027->allocated_days);
        $this->assertEquals(25, $balanceB_2027->remaining_days);
    }

    /**
     * Large delta: carry forward handles a large remaining_days change.
     */
    public function test_large_delta_carry_forward(): void
    {
        $balance2026 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2026,
            'allocated_days' => 15,
            'used_days' => 15,
            'remaining_days' => 0,
        ]);

        $balance2027 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2027,
            'allocated_days' => 15,
            'used_days' => 0,
            'remaining_days' => 15,
        ]);

        // Massive delta: +15 (from 0 remaining to 15 remaining)
        $balance2026->update([
            'used_days' => 0,
            'remaining_days' => 15,
        ]);

        $balance2027->refresh();
        $this->assertEquals(30, $balance2027->allocated_days);
        $this->assertEquals(30, $balance2027->remaining_days);
    }

    /**
     * Negative delta that would make next year's remaining_days go below 0
     * still applies — this is a valid state indicating the user has overused
     * their leave (the system should handle this at the policy level, not
     * at the observer level).
     */
    public function test_negative_delta_reduces_below_original_base(): void
    {
        $balance2026 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2026,
            'allocated_days' => 15,
            'used_days' => 0,
            'remaining_days' => 15,
        ]);

        $balance2027 = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->carryType->id,
            'year' => 2027,
            'allocated_days' => 30,
            'used_days' => 28,
            'remaining_days' => 2,
        ]);

        // Under Correctness Gap 1.3 (Retrospective Negative Balance/Double-Spending)
        // in REPORT.md, updates driving any subsequent year's remaining balance
        // below zero must throw an Exception to prevent double-spending.
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Deduction failed: Retrospective balance reduction would drive remaining days for year 2027 below zero");

        // Delta: -10 (remaining goes from 15 to 5)
        $balance2026->update([
            'used_days' => 10,
            'remaining_days' => 5,
        ]);
    }
}
