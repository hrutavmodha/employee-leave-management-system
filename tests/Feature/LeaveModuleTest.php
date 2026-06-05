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
            'start_date' => '2026-06-08', // Monday
            'end_date' => '2026-06-10', // Wednesday (3 working days)
            'reason' => 'Testing leave application',
        ]);

        $response->assertRedirect(route('leaves.index'));
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $user->id,
            'days_requested' => 3,
            'status' => 'Pending'
        ]);
    }

    public function test_submitting_leave_request_notifies_manager(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $manager = User::factory()->create(['role' => 'Manager']);
        $employee = User::factory()->create([
            'role' => 'Employee',
            'manager_id' => $manager->id,
        ]);

        $leaveType = LeaveType::create([
            'name' => 'Annual Leave',
            'allowed_days' => 20,
            'carry_forward' => true
        ]);

        $response = $this->actingAs($employee)->post('/leaves', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-08', // Monday
            'end_date' => '2026-06-10', // Wednesday (3 working days)
            'reason' => 'Testing leave application notification',
        ]);

        $response->assertRedirect(route('leaves.index'));
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $employee->id,
            'days_requested' => 3,
            'status' => 'Pending'
        ]);

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $manager,
            \App\Notifications\LeaveRequestSubmitted::class,
            function ($notification) use ($employee) {
                return $notification->leaveRequest->user_id === $employee->id;
            }
        );
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
            'days_requested' => 8,
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
            'remaining_days' => 5, // We need 6 for 2027-12-24 to 2027-12-31
        ]);

        \App\Models\LeaveBalance::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2028,
            'allocated_days' => 10,
            'used_days' => 0,
            'remaining_days' => 10, // We need 3 for 2028-01-01 to 2028-01-05
        ]);

        $response = $this->actingAs($user)->post('/leaves', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2027-12-24', // Friday
            'end_date' => '2028-01-05',
            'reason' => 'Cross year vacation failed',
        ]);

        $response->assertSessionHasErrors(['end_date']);
        $errors = session('errors')->get('end_date');
        $this->assertStringContainsString('Insufficient balance: You only have 5 days left for the year 2027, but you requested 6 days.', $errors[0]);

        $this->assertDatabaseMissing('leave_requests', [
            'user_id' => $user->id,
            'days_requested' => 9,
        ]);
    }

    public function test_employee_cannot_apply_for_overlapping_leave_dates()
    {
        $user = User::factory()->create();
        $leaveType = LeaveType::create([
            'name' => 'Sick Leave',
            'allowed_days' => 10,
            'carry_forward' => false
        ]);

        \App\Models\LeaveBalance::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'allocated_days' => 10,
            'used_days' => 0,
            'remaining_days' => 10,
        ]);

        // Pre-create a Pending request
        \App\Models\LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-15',
            'days_requested' => 6,
            'reason' => 'Existing leave request',
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($user)->post('/leaves', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-12',
            'end_date' => '2026-06-18',
            'reason' => 'Overlapping request',
        ]);

        $response->assertSessionHasErrors(['start_date']);
        $errors = session('errors')->get('start_date');
        $this->assertStringContainsString(
            'You already have a pending or approved leave request overlapping with these dates.',
            $errors[0]
        );
    }

    public function test_employee_can_apply_overlapping_dates_if_cancelled()
    {
        $user = User::factory()->create();
        $leaveType = LeaveType::create([
            'name' => 'Sick Leave',
            'allowed_days' => 10,
            'carry_forward' => false
        ]);

        \App\Models\LeaveBalance::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'allocated_days' => 10,
            'used_days' => 0,
            'remaining_days' => 10,
        ]);

        // Pre-create a Cancelled request
        \App\Models\LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-15',
            'days_requested' => 6,
            'reason' => 'Existing leave request',
            'status' => 'Cancelled',
        ]);

        $response = $this->actingAs($user)->post('/leaves', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-12',
            'end_date' => '2026-06-18',
            'reason' => 'Non-overlapping request',
        ]);

        $response->assertRedirect(route('leaves.index'));
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $user->id,
            'start_date' => '2026-06-12 00:00:00',
            'end_date' => '2026-06-18 00:00:00',
            'status' => 'Pending',
        ]);
    }

    public function test_initialize_balances_is_efficient(): void
    {
        $user = User::factory()->create();

        // Ensure there are multiple leave types
        LeaveType::create(['name' => 'Type A', 'allowed_days' => 10, 'carry_forward' => true]);
        LeaveType::create(['name' => 'Type B', 'allowed_days' => 12, 'carry_forward' => true]);
        LeaveType::create(['name' => 'Type C', 'allowed_days' => 15, 'carry_forward' => false]);

        // Enable query log
        \Illuminate\Support\Facades\DB::enableQueryLog();

        // Act: Initialize balances
        $service = app(\App\Services\LeaveCalculationService::class);
        $service->initializeBalances($user, 2026);

        $queries = \Illuminate\Support\Facades\DB::getQueryLog();
        \Illuminate\Support\Facades\DB::disableQueryLog();

        // Filter for SELECT queries
        $selectQueries = array_filter($queries, function ($query) {
            return str_starts_with(strtolower($query['query']), 'select');
        });

        // The count of SELECT queries should be exactly 3:
        // 1. SELECT * FROM leave_types
        // 2. SELECT * FROM leave_balances WHERE user_id = ? AND year = 2025
        // 3. SELECT * FROM leave_balances WHERE user_id = ? AND year = 2026
        $this->assertLessThanOrEqual(3, count($selectQueries));
    }

    public function test_leave_requests_table_has_status_index(): void
    {
        $indexes = \Illuminate\Support\Facades\Schema::getIndexes('leave_requests');
        $hasStatusIndex = false;

        foreach ($indexes as $index) {
            if (in_array('status', $index['columns'], true)) {
                $hasStatusIndex = true;
                break;
            }
        }

        $this->assertTrue($hasStatusIndex, 'The status column on the leave_requests table is not indexed.');
    }

    public function test_employee_cannot_cancel_past_or_current_leave_requests(): void
    {
        $user = User::factory()->create();
        $leaveType = LeaveType::create([
            'name' => 'Sick Leave',
            'allowed_days' => 10,
            'carry_forward' => false
        ]);

        \App\Models\LeaveBalance::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'year' => (int) date('Y'),
            'allocated_days' => 10,
            'used_days' => 0,
            'remaining_days' => 10,
        ]);

        // 1. Past leave request (yesterday)
        $pastRequest = \App\Models\LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => \Carbon\Carbon::yesterday()->format('Y-m-d'),
            'end_date' => \Carbon\Carbon::yesterday()->format('Y-m-d'),
            'days_requested' => 1,
            'reason' => 'Past leave',
            'status' => 'Approved',
        ]);

        $response = $this->actingAs($user)->post("/leaves/{$pastRequest->id}/cancel");
        $response->assertSessionHasErrors(['start_date']);
        $pastRequest->refresh();
        $this->assertEquals('Approved', $pastRequest->status);

        // 2. Current leave request (today)
        $todayRequest = \App\Models\LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => \Carbon\Carbon::today()->format('Y-m-d'),
            'end_date' => \Carbon\Carbon::today()->format('Y-m-d'),
            'days_requested' => 1,
            'reason' => 'Today leave',
            'status' => 'Approved',
        ]);

        $response = $this->actingAs($user)->post("/leaves/{$todayRequest->id}/cancel");
        $response->assertSessionHasErrors(['start_date']);
        $todayRequest->refresh();
        $this->assertEquals('Approved', $todayRequest->status);

        // 3. Future leave request (tomorrow)
        $futureRequest = \App\Models\LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => \Carbon\Carbon::tomorrow()->format('Y-m-d'),
            'end_date' => \Carbon\Carbon::tomorrow()->format('Y-m-d'),
            'days_requested' => 1,
            'reason' => 'Future leave',
            'status' => 'Approved',
        ]);

        $response = $this->actingAs($user)->post("/leaves/{$futureRequest->id}/cancel");
        $response->assertRedirect(route('leaves.index'));
        $futureRequest->refresh();
        $this->assertEquals('Cancelled', $futureRequest->status);
    }

    public function test_cancelling_leave_request_notifies_manager(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $manager = User::factory()->create(['role' => 'Manager']);
        $employee = User::factory()->create([
            'role' => 'Employee',
            'manager_id' => $manager->id,
        ]);

        $leaveType = LeaveType::create([
            'name' => 'Annual Leave',
            'allowed_days' => 20,
            'carry_forward' => true
        ]);

        $futureRequest = \App\Models\LeaveRequest::create([
            'user_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => \Carbon\Carbon::tomorrow()->format('Y-m-d'),
            'end_date' => \Carbon\Carbon::tomorrow()->format('Y-m-d'),
            'days_requested' => 1,
            'reason' => 'Future leave request notification test',
            'status' => 'Approved',
        ]);

        $response = $this->actingAs($employee)->post("/leaves/{$futureRequest->id}/cancel");
        $response->assertRedirect(route('leaves.index'));

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $manager,
            \App\Notifications\LeaveRequestCancelled::class,
            function ($notification) use ($employee) {
                return $notification->leaveRequest->user_id === $employee->id;
            }
        );
    }
}
