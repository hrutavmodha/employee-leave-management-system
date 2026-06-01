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
}
