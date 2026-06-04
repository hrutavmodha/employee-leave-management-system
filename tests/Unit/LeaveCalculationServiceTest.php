<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Services\LeaveCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Exception;

class LeaveCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LeaveCalculationService $service;
    protected User $user;
    protected LeaveType $leaveType;
    protected LeaveBalance $balance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LeaveCalculationService();

        $this->user = User::factory()->create();

        $this->leaveType = LeaveType::create([
            'name' => 'Sick Leave',
            'allowed_days' => 10,
            'carry_forward' => false,
            'description' => 'Sick leave type',
        ]);

        $this->balance = LeaveBalance::create([
            'user_id' => $this->user->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => date('Y'),
            'allocated_days' => 10,
            'used_days' => 2,
            'remaining_days' => 8,
        ]);
    }

    /**
     * Test successful balance deduction under lock.
     */
    public function test_deduct_balance_deducts_successfully_when_sufficient(): void
    {
        // Arrange
        $request = new LeaveRequest([
            'user_id' => $this->user->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now(),
            'end_date' => now()->addDays(2),
            'days_requested' => 3,
            'status' => 'Pending',
        ]);
        // Workaround since relations expect set model instance
        $request->user = $this->user;

        // Act
        DB::transaction(function () use ($request) {
            $this->service->deductBalance($request);
        });

        // Assert
        $updatedBalance = LeaveBalance::find($this->balance->id);
        $this->assertEquals(5, $updatedBalance->remaining_days);
    }

    /**
     * Test balance deduction throws exception when balance is insufficient.
     */
    public function test_deduct_balance_throws_exception_when_insufficient(): void
    {
        // Arrange
        $request = new LeaveRequest([
            'user_id' => $this->user->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now(),
            'end_date' => now()->addDays(10),
            'days_requested' => 12,
            'status' => 'Pending',
        ]);
        $request->user = $this->user;

        // Assert
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Insufficient balance');

        // Act
        DB::transaction(function () use ($request) {
            $this->service->deductBalance($request);
        });
    }

    /**
     * Test successful balance refund.
     */
    public function test_refund_balance_refunds_successfully(): void
    {
        // Arrange
        $request = new LeaveRequest([
            'user_id' => $this->user->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now(),
            'end_date' => now()->addDays(2),
            'days_requested' => 2,
            'status' => 'Approved',
        ]);
        $request->user = $this->user;

        // Act
        DB::transaction(function () use ($request) {
            $this->service->refundBalance($request);
        });

        // Assert
        $updatedBalance = LeaveBalance::find($this->balance->id);
        $this->assertEquals(10, $updatedBalance->remaining_days);
    }

    /**
     * Test refund balance does not exceed allocated days.
     */
    public function test_refund_balance_does_not_exceed_allocated_days(): void
    {
        // Arrange
        $request = new LeaveRequest([
            'user_id' => $this->user->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now(),
            'end_date' => now()->addDays(5),
            'days_requested' => 4,
            'status' => 'Approved',
        ]);
        $request->user = $this->user;

        // Act
        DB::transaction(function () use ($request) {
            $this->service->refundBalance($request);
        });

        // Assert
        $updatedBalance = LeaveBalance::find($this->balance->id);
        $this->assertEquals(10, $updatedBalance->remaining_days);
    }

    /**
     * Test successful cross-year balance deduction.
     */
    public function test_deduct_balance_cross_year_splits_successfully(): void
    {
        // Arrange
        $balance2027 = LeaveBalance::create([
            'user_id' => $this->user->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => 2027,
            'allocated_days' => 10,
            'used_days' => 0,
            'remaining_days' => 10,
        ]);

        $balance2028 = LeaveBalance::create([
            'user_id' => $this->user->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => 2028,
            'allocated_days' => 10,
            'used_days' => 0,
            'remaining_days' => 10,
        ]);

        $request = new LeaveRequest([
            'user_id' => $this->user->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2027-12-25',
            'end_date' => '2028-01-05',
            'days_requested' => 12,
            'status' => 'Pending',
        ]);
        $request->user = $this->user;

        // Act
        DB::transaction(function () use ($request) {
            $this->service->deductBalance($request);
        });

        // Assert
        $balance2027->refresh();
        $balance2028->refresh();

        $this->assertEquals(7, $balance2027->used_days);
        $this->assertEquals(3, $balance2027->remaining_days);

        $this->assertEquals(5, $balance2028->used_days);
        $this->assertEquals(5, $balance2028->remaining_days);
    }

    /**
     * Test successful cross-year balance refund.
     */
    public function test_refund_balance_cross_year_refunds_successfully(): void
    {
        // Arrange
        $balance2027 = LeaveBalance::create([
            'user_id' => $this->user->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => 2027,
            'allocated_days' => 10,
            'used_days' => 7,
            'remaining_days' => 3,
        ]);

        $balance2028 = LeaveBalance::create([
            'user_id' => $this->user->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => 2028,
            'allocated_days' => 10,
            'used_days' => 5,
            'remaining_days' => 5,
        ]);

        $request = new LeaveRequest([
            'user_id' => $this->user->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2027-12-25',
            'end_date' => '2028-01-05',
            'days_requested' => 12,
            'status' => 'Approved',
        ]);
        $request->user = $this->user;

        // Act
        DB::transaction(function () use ($request) {
            $this->service->refundBalance($request);
        });

        // Assert
        $balance2027->refresh();
        $balance2028->refresh();

        $this->assertEquals(0, $balance2027->used_days);
        $this->assertEquals(10, $balance2027->remaining_days);

        $this->assertEquals(0, $balance2028->used_days);
        $this->assertEquals(10, $balance2028->remaining_days);
    }
}
