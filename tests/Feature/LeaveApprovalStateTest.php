<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LeaveApprovalStateTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $employee;
    protected LeaveType $leaveType;
    protected LeaveBalance $balance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create(['role' => 'Manager']);
        
        $this->employee = User::factory()->create([
            'role' => 'Employee',
            'manager_id' => $this->manager->id,
        ]);

        $this->leaveType = LeaveType::create([
            'name' => 'Vacation',
            'allowed_days' => 15,
            'carry_forward' => false,
        ]);

        $this->balance = LeaveBalance::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year' => date('Y'),
            'allocated_days' => 15,
            'used_days' => 0,
            'remaining_days' => 15,
        ]);
    }

    public function test_cannot_approve_non_pending_leave_request(): void
    {
        Notification::fake();

        // Arrange: Create an already Approved request
        $request = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(4),
            'days_requested' => 3,
            'reason' => 'Trip',
            'status' => 'Approved',
        ]);

        // Capture initial balance state
        $initialRemaining = $this->balance->remaining_days;

        // Act: Attempt to approve it again
        $response = $this->actingAs($this->manager)->post(
            route('approvals.approve', $request),
            ['manager_comment' => 'Duplicate approval attempt']
        );

        // Assert: It should redirect with an error message
        $response->assertRedirect(route('approvals.index'));
        $response->assertSessionHas('error', 'Only pending leave requests can be approved.');

        // Assert: The balance has NOT been deducted a second time
        $this->balance->refresh();
        $this->assertEquals($initialRemaining, $this->balance->remaining_days);
    }

    public function test_cannot_reject_non_pending_leave_request(): void
    {
        Notification::fake();

        // Arrange: Create an already Approved request
        $request = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(4),
            'days_requested' => 3,
            'reason' => 'Trip',
            'status' => 'Approved',
        ]);

        // Act: Attempt to reject it
        $response = $this->actingAs($this->manager)->post(
            route('approvals.reject', $request),
            ['manager_comment' => 'Rejecting approved request']
        );

        // Assert: It should redirect with an error message
        $response->assertRedirect(route('approvals.index'));
        $response->assertSessionHas('error', 'Only pending leave requests can be rejected.');

        // Assert: Status remains 'Approved'
        $request->refresh();
        $this->assertEquals('Approved', $request->status);
    }

    public function test_cannot_approve_cancelled_leave_request(): void
    {
        Notification::fake();

        // Arrange: Create a Cancelled request
        $request = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(4),
            'days_requested' => 3,
            'reason' => 'Trip',
            'status' => 'Cancelled',
        ]);

        // Capture initial balance state
        $initialRemaining = $this->balance->remaining_days;

        // Act: Attempt to approve it
        $response = $this->actingAs($this->manager)->post(
            route('approvals.approve', $request),
            ['manager_comment' => 'Approving cancelled request']
        );

        // Assert
        $response->assertRedirect(route('approvals.index'));
        $response->assertSessionHas('error', 'Only pending leave requests can be approved.');

        // Assert: Balance unchanged
        $this->balance->refresh();
        $this->assertEquals($initialRemaining, $this->balance->remaining_days);
    }

    public function test_manager_can_reject_leave_request_without_comment(): void
    {
        Notification::fake();

        // Arrange: Create a Pending request
        $request = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(4),
            'days_requested' => 3,
            'reason' => 'Trip',
            'status' => 'Pending',
        ]);

        // Access the index page to populate the cache
        $this->actingAs($this->manager)->get(route('approvals.index'))
            ->assertStatus(200)
            ->assertViewHas('pendingRequests', function ($requests) use ($request) {
                return $requests->contains('id', $request->id);
            });

        // Act: Reject the request without manager_comment
        $response = $this->actingAs($this->manager)->post(
            route('approvals.reject', $request),
            ['manager_comment' => '']
        );

        // Assert
        $response->assertRedirect(route('approvals.index'));
        $response->assertSessionHas('success', 'Leave request rejected and employee notified.');

        // Assert status in database is updated
        $request->refresh();
        $this->assertEquals('Rejected', $request->status);
        $this->assertNull($request->manager_comment);

        // Assert notification was sent
        Notification::assertSentTo(
            $this->employee,
            \App\Notifications\LeaveStatusUpdated::class,
            function ($notification) use ($request) {
                return $notification->leaveRequest->id === $request->id;
            }
        );

        // Assert that visiting the index again shows the updated list (cache cleared)
        $this->actingAs($this->manager)->get(route('approvals.index'))
            ->assertStatus(200)
            ->assertViewHas('pendingRequests', function ($requests) use ($request) {
                return !$requests->contains('id', $request->id);
            });
    }

    public function test_manager_can_reject_leave_request_with_comment(): void
    {
        Notification::fake();

        // Arrange: Create a Pending request
        $request = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(4),
            'days_requested' => 3,
            'reason' => 'Trip',
            'status' => 'Pending',
        ]);

        // Access the index page to populate the cache
        $this->actingAs($this->manager)->get(route('approvals.index'))
            ->assertStatus(200)
            ->assertViewHas('pendingRequests', function ($requests) use ($request) {
                return $requests->contains('id', $request->id);
            });

        // Act: Reject the request with manager_comment
        $response = $this->actingAs($this->manager)->post(
            route('approvals.reject', $request),
            ['manager_comment' => 'Not allowed at this time']
        );

        // Assert
        $response->assertRedirect(route('approvals.index'));
        $response->assertSessionHas('success', 'Leave request rejected and employee notified.');

        // Assert status in database is updated
        $request->refresh();
        $this->assertEquals('Rejected', $request->status);
        $this->assertEquals('Not allowed at this time', $request->manager_comment);

        // Assert notification was sent
        Notification::assertSentTo(
            $this->employee,
            \App\Notifications\LeaveStatusUpdated::class,
            function ($notification) use ($request) {
                return $notification->leaveRequest->id === $request->id;
            }
        );

        // Assert that visiting the index again shows the updated list (cache cleared)
        $this->actingAs($this->manager)->get(route('approvals.index'))
            ->assertStatus(200)
            ->assertViewHas('pendingRequests', function ($requests) use ($request) {
                return !$requests->contains('id', $request->id);
            });
    }
}

