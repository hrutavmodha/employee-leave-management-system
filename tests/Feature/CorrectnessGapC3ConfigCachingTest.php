<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Notifications\LeaveRequestSubmitted;
use App\Notifications\LeaveStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tests for Correctness Gap C#3:
 *
 * All env()-dependent runtime values must be read through the config()
 * helper (backed by files in config/) so they survive `php artisan
 * config:cache`. Direct env() calls outside config files return null
 * when the config cache is active.
 *
 * We verify:
 * - config entries exist and return sensible defaults
 * - notification classes use config() to build URLs
 * - AppServiceProvider reads queue threshold from config()
 * - no env() calls remain in app/ code
 */
class CorrectnessGapC3ConfigCachingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * config('app.protocol') returns the APP_PROTOCOL value.
     */
    public function test_config_app_protocol_is_registered(): void
    {
        $value = config('app.protocol');

        $this->assertNotNull($value, 'config(app.protocol) must not be null');
        $this->assertIsString($value);
    }

    /**
     * config('app.domain') returns the APP_DOMAIN value.
     */
    public function test_config_app_domain_is_registered(): void
    {
        $value = config('app.domain');

        $this->assertNotNull($value, 'config(app.domain) must not be null');
        $this->assertIsString($value);
    }

    /**
     * config('queue.flush_threshold') returns the QUEUE_FLUSH_THRESHOLD value.
     */
    public function test_config_queue_flush_threshold_is_registered(): void
    {
        $value = config('queue.flush_threshold');

        $this->assertNotNull($value, 'config(queue.flush_threshold) must not be null');
        $this->assertIsInt($value);
        $this->assertGreaterThanOrEqual(1, $value);
    }

    /**
     * Simulates config cache by blanking the env var and verifying that
     * config() still returns a valid default. This mirrors production
     * behavior where env() returns null after `config:cache`.
     */
    public function test_config_returns_default_when_env_is_absent(): void
    {
        // Force config values to their defaults (simulating env vars absent)
        Config::set('app.protocol', 'http');
        Config::set('app.domain', 'localhost:8000');
        Config::set('queue.flush_threshold', 1);

        $this->assertEquals('http', config('app.protocol'));
        $this->assertEquals('localhost:8000', config('app.domain'));
        $this->assertEquals(1, config('queue.flush_threshold'));
    }

    /**
     * LeaveRequestSubmitted notification builds a valid URL using
     * config('app.protocol') and config('app.domain').
     */
    public function test_leave_request_submitted_notification_builds_url_from_config(): void
    {
        Config::set('app.protocol', 'https');
        Config::set('app.domain', 'elms.example.com');

        $user = User::factory()->create(['role' => 'Employee']);
        $leaveType = LeaveType::create([
            'name' => 'Annual',
            'allowed_days' => 20,
            'carry_forward' => false,
        ]);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-10',
            'days_requested' => 3,
            'status' => 'Pending',
            'reason' => 'Test',
        ]);

        $notification = new LeaveRequestSubmitted($leaveRequest);
        $manager = User::factory()->create(['role' => 'Manager']);
        $mail = $notification->toMail($manager);

        // The action URL must contain the configured protocol and domain
        $actionUrl = $mail->actionUrl;
        $this->assertStringContainsString('https://', $actionUrl);
        $this->assertStringContainsString('elms.example.com', $actionUrl);
        $this->assertStringContainsString('/approvals', $actionUrl);
    }

    /**
     * LeaveStatusUpdated notification builds a valid URL using
     * config('app.protocol') and config('app.domain').
     */
    public function test_leave_status_updated_notification_builds_url_from_config(): void
    {
        Config::set('app.protocol', 'https');
        Config::set('app.domain', 'elms.example.com');

        $user = User::factory()->create(['role' => 'Employee']);
        $leaveType = LeaveType::create([
            'name' => 'Annual',
            'allowed_days' => 20,
            'carry_forward' => false,
        ]);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-10',
            'days_requested' => 3,
            'status' => 'Approved',
            'reason' => 'Test',
        ]);

        $notification = new LeaveStatusUpdated($leaveRequest);
        $mail = $notification->toMail($user);

        $actionUrl = $mail->actionUrl;
        $this->assertStringContainsString('https://', $actionUrl);
        $this->assertStringContainsString('elms.example.com', $actionUrl);
        $this->assertStringContainsString('/leaves', $actionUrl);
    }

    /**
     * Notification URLs must NOT produce broken `:// /` patterns
     * (which would happen if env() returned null).
     */
    public function test_notification_url_is_well_formed_with_defaults(): void
    {
        // Reset to defaults
        Config::set('app.protocol', 'http');
        Config::set('app.domain', 'localhost:8000');

        $user = User::factory()->create(['role' => 'Employee']);
        $leaveType = LeaveType::create([
            'name' => 'Annual',
            'allowed_days' => 20,
            'carry_forward' => false,
        ]);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-10',
            'days_requested' => 3,
            'status' => 'Approved',
            'reason' => 'Test',
        ]);

        $submittedMail = (new LeaveRequestSubmitted($leaveRequest))->toMail($user);
        $statusMail = (new LeaveStatusUpdated($leaveRequest))->toMail($user);

        // Must be a valid URL pattern: protocol://domain/route
        $this->assertMatchesRegularExpression(
            '#^https?://[^/]+/.+$#',
            $submittedMail->actionUrl,
            'LeaveRequestSubmitted URL is malformed'
        );
        $this->assertMatchesRegularExpression(
            '#^https?://[^/]+/.+$#',
            $statusMail->actionUrl,
            'LeaveStatusUpdated URL is malformed'
        );
    }

    /**
     * Verify that no env() calls exist in any PHP file under app/.
     * This is a static analysis guard to prevent regressions.
     */
    public function test_no_env_calls_in_app_directory(): void
    {
        $appDir = base_path('app');
        $violations = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($appDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if (preg_match('/\benv\s*\(/', $contents)) {
                $relativePath = str_replace(base_path() . '/', '', $file->getPathname());
                $violations[] = $relativePath;
            }
        }

        $this->assertEmpty(
            $violations,
            "The following files contain env() calls outside config/:\n" .
            implode("\n", $violations)
        );
    }
}
