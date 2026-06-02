<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_departments_index(): void
    {
        $response = $this->get('/departments');

        $response->assertRedirect('/login');
    }

    public function test_manager_cannot_access_departments_index(): void
    {
        $manager = User::factory()->create(['role' => 'Manager']);

        $response = $this->actingAs($manager)->get('/departments');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_departments_index(): void
    {
        $admin = User::factory()->create(['role' => 'HR/Admin']);

        $response = $this->actingAs($admin)->get('/departments');

        $response->assertStatus(200);
        $response->assertSee('Departments');
    }

    public function test_admin_can_create_a_department(): void
    {
        $admin = User::factory()->create(['role' => 'HR/Admin']);

        $response = $this->actingAs($admin)->post('/departments', [
            'name' => 'Research and Development',
            'description' => 'Fostering core innovation.',
        ]);

        $response->assertRedirect(route('departments.index'));
        $response->assertSessionHasNoErrors();
        
        $this->assertDatabaseHas('departments', [
            'name' => 'Research and Development',
            'description' => 'Fostering core innovation.',
        ]);
    }

    public function test_admin_can_update_a_department(): void
    {
        $admin = User::factory()->create(['role' => 'HR/Admin']);
        $dept = Department::create([
            'name' => 'Sales',
            'description' => 'Sales and acquisition.',
        ]);

        $response = $this->actingAs($admin)->patch("/departments/{$dept->id}", [
            'name' => 'Enterprise Sales',
            'description' => 'B2B contracts.',
        ]);

        $response->assertRedirect(route('departments.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('departments', [
            'id' => $dept->id,
            'name' => 'Enterprise Sales',
            'description' => 'B2B contracts.',
        ]);
    }

    public function test_admin_can_delete_a_department(): void
    {
        $admin = User::factory()->create(['role' => 'HR/Admin']);
        $dept = Department::create([
            'name' => 'Marketing',
            'description' => 'Branding division.',
        ]);

        $response = $this->actingAs($admin)->delete("/departments/{$dept->id}");

        $response->assertRedirect(route('departments.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('departments', [
            'id' => $dept->id,
        ]);
    }
}
