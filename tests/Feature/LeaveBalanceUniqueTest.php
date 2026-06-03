<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class LeaveBalanceUniqueTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_insert_duplicate_leave_balance_for_same_user_type_and_year(): void
    {
        // Arrange
        $user = User::factory()->create();
        $leaveType = LeaveType::create([
            'name' => 'Annual Leave',
            'allowed_days' => 20,
            'carry_forward' => false,
        ]);

        // Create initial balance
        LeaveBalance::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'allocated_days' => 20,
            'used_days' => 0,
            'remaining_days' => 20,
        ]);

        // Assert: Attempting to create another duplicate record should throw a QueryException
        $this->expectException(QueryException::class);

        // Act
        LeaveBalance::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026, // Same year
            'allocated_days' => 20,
            'used_days' => 0,
            'remaining_days' => 20,
        ]);
    }
}
