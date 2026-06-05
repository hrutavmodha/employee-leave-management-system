<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityGapsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear rate limiter cache before each test
        \Illuminate\Support\Facades\RateLimiter::clear('login');
        \Illuminate\Support\Facades\RateLimiter::clear('forgot-password');
    }

    /**
     * Test 2.1: Missing Rate Limiting on Authentication Routes.
     */
    public function test_login_route_has_rate_limiting(): void
    {
        // The post('login') route is throttled to 5 requests per minute
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'invalid-user@example.com',
                'password' => 'wrong-password',
            ]);
            $this->assertNotEquals(429, $response->getStatusCode());
        }

        // The 6th request should return 429 Too Many Requests
        $response = $this->post('/login', [
            'email' => 'invalid-user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    public function test_forgot_password_route_has_rate_limiting(): void
    {
        // The post('forgot-password') route is throttled to 3 requests per minute
        for ($i = 0; $i < 3; $i++) {
            $response = $this->post('/forgot-password', [
                'email' => 'invalid-user@example.com',
            ]);
            $this->assertNotEquals(429, $response->getStatusCode());
        }

        // The 4th request should return 429 Too Many Requests
        $response = $this->post('/forgot-password', [
            'email' => 'invalid-user@example.com',
        ]);

        $response->assertStatus(429);
    }
}
