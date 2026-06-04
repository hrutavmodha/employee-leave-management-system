<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\PublicHoliday;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that user creation and deletion are logged in the audit trail.
     */
    public function test_user_creation_and_deletion_are_logged(): void
    {
        $logs = [];
        Log::listen(function ($level) use (&$logs) {
            $logs[] = $level->message;
        });

        // Trigger User Created
        $user = User::factory()->create([
            'email' => 'audit-test@example.com',
            'role' => 'Employee',
        ]);

        $this->assertNotEmpty(array_filter($logs, function ($msg) {
            return str_contains($msg, 'Audit log - Employee created') && str_contains($msg, 'audit-test@example.com');
        }), 'User creation was not logged in the audit trail.');

        // Trigger User Deleted
        $user->delete();

        $this->assertNotEmpty(array_filter($logs, function ($msg) {
            return str_contains($msg, 'Audit log - Employee deleted') && str_contains($msg, 'audit-test@example.com');
        }), 'User deletion was not logged in the audit trail.');
    }

    /**
     * Test that leave request creation and updates are logged in the audit trail.
     */
    public function test_leave_request_audit_logs(): void
    {
        $logs = [];
        Log::listen(function ($level) use (&$logs) {
            $logs[] = $level->message;
        });

        $user = User::factory()->create();
        $leaveType = LeaveType::create([
            'name' => 'Audit Vacation',
            'allowed_days' => 10,
            'carry_forward' => false,
        ]);

        // Trigger LeaveRequest created
        $request = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-06-08',
            'end_date' => '2026-06-10',
            'days_requested' => 3,
            'reason' => 'Audit test',
            'status' => 'Pending',
        ]);

        $this->assertNotEmpty(array_filter($logs, function ($msg) use ($request) {
            return str_contains($msg, 'Audit log - Leave request submitted') && str_contains($msg, "ID={$request->id}");
        }), 'Leave request creation was not logged.');

        // Trigger LeaveRequest status updated
        $request->update(['status' => 'Approved']);

        $this->assertNotEmpty(array_filter($logs, function ($msg) use ($request) {
            return str_contains($msg, 'Audit log - Leave request status changed') && str_contains($msg, 'From=Pending') && str_contains($msg, 'To=Approved');
        }), 'Leave request status change was not logged.');
    }
}
