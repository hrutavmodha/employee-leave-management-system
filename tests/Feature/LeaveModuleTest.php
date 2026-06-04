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

    public function test_employee_can_apply_for_cross_year_leave_successfully()
    {
        $user = User::factory()->create();
        $leaveType = LeaveType::create([
            'name' => 'Annual Leave',
            'allowed_days' => 10,
            'carry_forward' => false
        ]);

        \App\Models\LeaveBalance::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2027,
            'allocated_days' => 10,
            'used_days' => 0,
            'remaining_days' => 10,
        ]);

        \App\Models\LeaveBalance::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2028,
            'allocated_days' => 10,
            'used_days' => 0,
            'remaining_days' => 10,
        ]);

        $response = $this->actingAs($user)->post('/leaves', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2027-12-25',
            'end_date' => '2028-01-05',
            'reason' => 'Cross year vacation',
        ]);

        $response->assertRedirect(route('leaves.index'));
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $user->id,
            'days_requested' => 12,
            'status' => 'Pending'
        ]);
    }

    public function test_employee_cannot_apply_for_cross_year_leave_when_one_year_insufficient()
    {
        $user = User::factory()->create();
        $leaveType = LeaveType::create([
            'name' => 'Annual Leave',
            'allowed_days' => 10,
            'carry_forward' => false
        ]);

        \App\Models\LeaveBalance::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2027,
            'allocated_days' => 10,
            'used_days' => 5,
            'remaining_days' => 5, // We need 7 for 2027-12-25 to 2027-12-31
        ]);

        \App\Models\LeaveBalance::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2028,
            'allocated_days' => 10,
            'used_days' => 0,
            'remaining_days' => 10, // We need 5 for 2028-01-01 to 2028-01-05
        ]);

        $response = $this->actingAs($user)->post('/leaves', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2027-12-25',
            'end_date' => '2028-01-05',
            'reason' => 'Cross year vacation failed',
        ]);

        $response->assertSessionHasErrors(['end_date']);
        $errors = session('errors')->get('end_date');
        $this->assertStringContainsString('Insufficient balance: You only have 5 days left for the year 2027, but you requested 7 days.', $errors[0]);

        $this->assertDatabaseMissing('leave_requests', [
            'user_id' => $user->id,
            'days_requested' => 12,
        ]);
    }
}
