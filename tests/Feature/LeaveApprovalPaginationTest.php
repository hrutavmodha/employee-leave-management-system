<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class LeaveApprovalPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $manager;
    protected LeaveType $leaveType;

    /**
     * Set up common models for the pagination tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create(['role' => 'Manager']);
        $this->user = User::factory()->create([
            'role' => 'Employee',
            'manager_id' => $this->manager->id,
        ]);

        $this->leaveType = LeaveType::create([
            'name' => 'Vacation',
            'allowed_days' => 30,
            'carry_forward' => false,
        ]);
    }

    /**
     * Verify that the employee leaves index returns a paginated result set.
     */
    public function test_leaves_index_returns_paginated_results(): void
    {
        $response = $this->actingAs($this->user)->get(route('leaves.index'));

        $response->assertStatus(200);
        $response->assertViewHas('requests');

        $requests = $response->viewData('requests');
        $this->assertInstanceOf(LengthAwarePaginator::class, $requests);
    }

    /**
     * Verify that the leaves index limits results to 15 per page.
     */
    public function test_leaves_index_limits_to_15_per_page(): void
    {
        // Create 20 leave requests for the user
        for ($i = 0; $i < 20; $i++) {
            LeaveRequest::create([
                'user_id' => $this->user->id,
                'leave_type_id' => $this->leaveType->id,
                'start_date' => now()->addDays($i + 1)->format('Y-m-d'),
                'end_date' => now()->addDays($i + 1)->format('Y-m-d'),
                'days_requested' => 1,
                'status' => 'Pending',
                'reason' => 'Reason ' . $i,
            ]);
        }

        $response = $this->actingAs($this->user)->get(route('leaves.index'));
        $requests = $response->viewData('requests');

        $this->assertCount(15, $requests->items());
        $this->assertEquals(20, $requests->total());
        $this->assertEquals(2, $requests->lastPage());
    }

    /**
     * Verify page 2 of the leaves index returns the remaining requests.
     */
    public function test_leaves_index_page_two_returns_remaining(): void
    {
        for ($i = 0; $i < 20; $i++) {
            LeaveRequest::create([
                'user_id' => $this->user->id,
                'leave_type_id' => $this->leaveType->id,
                'start_date' => now()->addDays($i + 1)->format('Y-m-d'),
                'end_date' => now()->addDays($i + 1)->format('Y-m-d'),
                'days_requested' => 1,
                'status' => 'Pending',
                'reason' => 'Reason ' . $i,
            ]);
        }

        $response = $this->actingAs($this->user)->get(route('leaves.index', ['page' => 2]));
        $requests = $response->viewData('requests');

        $this->assertCount(5, $requests->items());
        $this->assertEquals(2, $requests->currentPage());
    }

    /**
     * Verify the leaves index renders page navigation markup.
     */
    public function test_leaves_index_renders_pagination_links(): void
    {
        for ($i = 0; $i < 20; $i++) {
            LeaveRequest::create([
                'user_id' => $this->user->id,
                'leave_type_id' => $this->leaveType->id,
                'start_date' => now()->addDays($i + 1)->format('Y-m-d'),
                'end_date' => now()->addDays($i + 1)->format('Y-m-d'),
                'days_requested' => 1,
                'status' => 'Pending',
                'reason' => 'Reason ' . $i,
            ]);
        }

        $response = $this->actingAs($this->user)->get(route('leaves.index'));
        $response->assertSee('page=2');
    }

    /**
     * Verify that the manager approvals index returns a paginated result set.
     */
    public function test_approvals_index_returns_paginated_results(): void
    {
        $response = $this->actingAs($this->manager)->get(route('approvals.index'));

        $response->assertStatus(200);
        $response->assertViewHas('pendingRequests');

        $requests = $response->viewData('pendingRequests');
        $this->assertInstanceOf(LengthAwarePaginator::class, $requests);
    }

    /**
     * Verify that the approvals index limits results to 15 per page.
     */
    public function test_approvals_index_limits_to_15_per_page(): void
    {
        // Create 20 pending leave requests for the manager's employee
        for ($i = 0; $i < 20; $i++) {
            LeaveRequest::create([
                'user_id' => $this->user->id,
                'leave_type_id' => $this->leaveType->id,
                'start_date' => now()->addDays($i + 1)->format('Y-m-d'),
                'end_date' => now()->addDays($i + 1)->format('Y-m-d'),
                'days_requested' => 1,
                'status' => 'Pending',
                'reason' => 'Reason ' . $i,
            ]);
        }

        $response = $this->actingAs($this->manager)->get(route('approvals.index'));
        $requests = $response->viewData('pendingRequests');

        $this->assertCount(15, $requests->items());
        $this->assertEquals(20, $requests->total());
        $this->assertEquals(2, $requests->lastPage());
    }

    /**
     * Verify page 2 of the approvals index returns the remaining requests.
     */
    public function test_approvals_index_page_two_returns_remaining(): void
    {
        for ($i = 0; $i < 20; $i++) {
            LeaveRequest::create([
                'user_id' => $this->user->id,
                'leave_type_id' => $this->leaveType->id,
                'start_date' => now()->addDays($i + 1)->format('Y-m-d'),
                'end_date' => now()->addDays($i + 1)->format('Y-m-d'),
                'days_requested' => 1,
                'status' => 'Pending',
                'reason' => 'Reason ' . $i,
            ]);
        }

        $response = $this->actingAs($this->manager)->get(route('approvals.index', ['page' => 2]));
        $requests = $response->viewData('pendingRequests');

        $this->assertCount(5, $requests->items());
        $this->assertEquals(2, $requests->currentPage());
    }

    /**
     * Verify the approvals index renders page navigation markup.
     */
    public function test_approvals_index_renders_pagination_links(): void
    {
        for ($i = 0; $i < 20; $i++) {
            LeaveRequest::create([
                'user_id' => $this->user->id,
                'leave_type_id' => $this->leaveType->id,
                'start_date' => now()->addDays($i + 1)->format('Y-m-d'),
                'end_date' => now()->addDays($i + 1)->format('Y-m-d'),
                'days_requested' => 1,
                'status' => 'Pending',
                'reason' => 'Reason ' . $i,
            ]);
        }

        $response = $this->actingAs($this->manager)->get(route('approvals.index'));
        $response->assertSee('page=2');
    }
}
