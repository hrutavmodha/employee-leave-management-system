<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LeaveType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_apply_for_leave()
    {
        $user = User::factory()->create();
        $leaveType = LeaveType::create([
            'name' => 'Annual Leave',
            'allowed_days' => 20,
            'carry_forward' => true
        ]);

        $response = $this->actingAs($user)->post('/leaves', [
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays(3)->format('Y-m-d'),
            'reason' => 'Testing leave application',
        ]);

        $response->assertRedirect(route('leaves.index'));
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $user->id,
            'days_requested' => 3,
            'status' => 'Pending'
        ]);
    }

    public function test_carry_forward_is_calculated_correctly_on_initialization()
    {
        $user = User::factory()->create();
        
        $carryType = LeaveType::create([
            'name' => 'Carry Forward Leave',
            'allowed_days' => 15,
            'carry_forward' => true
        ]);

        $noCarryType = LeaveType::create([
            'name' => 'Standard Sick Leave',
            'allowed_days' => 10,
            'carry_forward' => false
        ]);

        // Initialize year 2026 and simulate used days
        $service = app(\App\Services\LeaveCalculationService::class);
        $service->initializeBalances($user, 2026);

        $carryBalance2026 = \App\Models\LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $carryType->id)
            ->where('year', 2026)
            ->first();
        $carryBalance2026->update([
            'used_days' => 5,
            'remaining_days' => 10,
        ]);

        $noCarryBalance2026 = \App\Models\LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $noCarryType->id)
            ->where('year', 2026)
            ->first();
        $noCarryBalance2026->update([
            'used_days' => 5,
            'remaining_days' => 5,
        ]);

        // Initialize year 2027 balances
        $service->initializeBalances($user, 2027);

        // Assert 2027 carry-forward balance: 15 (base) + 10 (carried over) = 25
        $carryBalance2027 = \App\Models\LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $carryType->id)
            ->where('year', 2027)
            ->first();
        $this->assertEquals(25, $carryBalance2027->remaining_days);
        $this->assertEquals(25, $carryBalance2027->allocated_days);

        // Assert 2027 non-carry-forward balance: remains 10 (base)
        $noCarryBalance2027 = \App\Models\LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $noCarryType->id)
            ->where('year', 2027)
            ->first();
        $this->assertEquals(10, $noCarryBalance2027->remaining_days);
        $this->assertEquals(10, $noCarryBalance2027->allocated_days);
    }
}
