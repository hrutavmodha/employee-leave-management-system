<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveTypeDeletionGuardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::create(['name' => 'Engineering']);
        $this->admin = User::factory()->create([
            'department_id' => $this->department->id,
            'role' => 'HR/Admin',
        ]);
    }

    /**
     * An unused leave type (no requests, no balances) can be deleted.
     */
    public function test_can_delete_leave_type_without_history(): void
    {
        $leaveType = LeaveType::create([
            'name' => 'Bonus Leave',
            'allowed_days' => 5,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('leave-types.destroy', $leaveType));

        $response->assertRedirect(route('leave-types.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('leave_types', ['id' => $leaveType->id]);
    }

    /**
     * A leave type with associated leave requests cannot be deleted.
     */
    public function test_cannot_delete_leave_type_with_leave_requests(): void
    {
        $leaveType = LeaveType::create([
            'name' => 'Sick Leave',
            'allowed_days' => 12,
        ]);

        $employee = User::factory()->create([
            'department_id' => $this->department->id,
            'role' => 'Employee',
        ]);

        LeaveRequest::create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'days_requested' => 2,
            'reason' => 'Fever',
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('leave-types.destroy', $leaveType));

        $response->assertRedirect(route('leave-types.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('leave_types', ['id' => $leaveType->id]);
    }

    /**
     * A leave type with associated balance records cannot be deleted.
     */
    public function test_cannot_delete_leave_type_with_balances(): void
    {
        $leaveType = LeaveType::create([
            'name' => 'Annual Leave',
            'allowed_days' => 20,
        ]);

        $employee = User::factory()->create([
            'department_id' => $this->department->id,
            'role' => 'Employee',
        ]);

        LeaveBalance::create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => date('Y'),
            'allocated_days' => 20,
            'used_days' => 0,
            'remaining_days' => 20,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('leave-types.destroy', $leaveType));

        $response->assertRedirect(route('leave-types.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('leave_types', ['id' => $leaveType->id]);
    }

    /**
     * The error message is descriptive and actionable.
     */
    public function test_deletion_blocked_message_is_descriptive(): void
    {
        $leaveType = LeaveType::create([
            'name' => 'Comp Off',
            'allowed_days' => 5,
        ]);

        $employee = User::factory()->create([
            'department_id' => $this->department->id,
            'role' => 'Employee',
        ]);

        LeaveRequest::create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'days_requested' => 2,
            'reason' => 'Personal',
            'status' => 'Approved',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('leave-types.destroy', $leaveType));

        $response->assertSessionHas('error', 'Cannot delete this leave type because it has associated leave requests or balance records.');
    }
}
