<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class EmployeePaginationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verify the employee index returns a paginated result set
     * instead of an unbounded collection.
     */
    public function test_employee_index_returns_paginated_results(): void
    {
        $department = Department::create(['name' => 'Engineering']);

        $admin = User::factory()->create([
            'department_id' => $department->id,
            'role' => 'HR/Admin',
        ]);

        $response = $this->actingAs($admin)->get(route('employees.index'));

        $response->assertStatus(200);
        $response->assertViewHas('employees');

        $employees = $response->viewData('employees');
        $this->assertInstanceOf(LengthAwarePaginator::class, $employees);
    }

    /**
     * Verify only 15 employees appear per page when more than 15 exist.
     */
    public function test_employee_index_limits_to_15_per_page(): void
    {
        $department = Department::create(['name' => 'Engineering']);

        $admin = User::factory()->create([
            'department_id' => $department->id,
            'role' => 'HR/Admin',
        ]);

        // Create 20 additional employees (21 total including admin)
        User::factory()->count(20)->create([
            'department_id' => $department->id,
            'role' => 'Employee',
        ]);

        $response = $this->actingAs($admin)->get(route('employees.index'));
        $employees = $response->viewData('employees');

        $this->assertCount(15, $employees->items());
        $this->assertEquals(21, $employees->total());
        $this->assertEquals(2, $employees->lastPage());
    }

    /**
     * Verify page 2 returns the remaining employees.
     */
    public function test_employee_index_page_two_returns_remaining(): void
    {
        $department = Department::create(['name' => 'Engineering']);

        $admin = User::factory()->create([
            'department_id' => $department->id,
            'role' => 'HR/Admin',
        ]);

        User::factory()->count(20)->create([
            'department_id' => $department->id,
            'role' => 'Employee',
        ]);

        $response = $this->actingAs($admin)->get(route('employees.index', ['page' => 2]));
        $employees = $response->viewData('employees');

        // 21 total - 15 on page 1 = 6 on page 2
        $this->assertCount(6, $employees->items());
        $this->assertEquals(2, $employees->currentPage());
    }

    /**
     * Verify the view renders pagination links (checks that
     * the response body contains pagination navigation markup).
     */
    public function test_employee_index_renders_pagination_links(): void
    {
        $department = Department::create(['name' => 'Engineering']);

        $admin = User::factory()->create([
            'department_id' => $department->id,
            'role' => 'HR/Admin',
        ]);

        User::factory()->count(20)->create([
            'department_id' => $department->id,
            'role' => 'Employee',
        ]);

        $response = $this->actingAs($admin)->get(route('employees.index'));

        // Laravel's pagination renders navigation with "Next" link
        $response->assertSee('page=2');
    }

    /**
     * Verify that with fewer employees than the page size,
     * only a single page exists and all records are shown.
     */
    public function test_employee_index_single_page_when_few_records(): void
    {
        $department = Department::create(['name' => 'Small Team']);

        $admin = User::factory()->create([
            'department_id' => $department->id,
            'role' => 'HR/Admin',
        ]);

        User::factory()->count(5)->create([
            'department_id' => $department->id,
            'role' => 'Employee',
        ]);

        $response = $this->actingAs($admin)->get(route('employees.index'));
        $employees = $response->viewData('employees');

        $this->assertCount(6, $employees->items()); // 5 + 1 admin
        $this->assertEquals(1, $employees->lastPage());
    }
}
