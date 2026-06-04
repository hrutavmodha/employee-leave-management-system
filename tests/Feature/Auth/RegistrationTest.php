<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verify that the registration screen is disabled and returns 404.
     */
    public function test_registration_screen_is_disabled(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(404);
    }

    /**
     * Verify that post requests to register are disabled and return 404.
     */
    public function test_registration_post_is_disabled(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'Employee',
        ]);

        $response->assertStatus(404);
        $this->assertGuest();
    }
}
