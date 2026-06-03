<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageCleanupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a fully wired user with a leave request and attachment
     * for storage cleanup testing.
     *
     * @return array{user: User, leaveRequest: LeaveRequest, attachment: Attachment}
     */
    private function createUserWithAttachment(): array
    {
        $department = Department::create(['name' => 'Engineering']);
        $leaveType = LeaveType::create([
            'name' => 'Casual',
            'allowed_days' => 12,
        ]);

        $user = User::factory()->create([
            'department_id' => $department->id,
            'role' => 'Employee',
            'profile_picture' => 'profile_pictures/test_avatar.jpg',
        ]);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'days_requested' => 3,
            'reason' => 'Family event',
            'status' => 'Pending',
        ]);

        $attachment = Attachment::create([
            'leave_request_id' => $leaveRequest->id,
            'file_name' => 'medical_cert.pdf',
            'file_path' => 'attachments/medical_cert.pdf',
        ]);

        return compact('user', 'leaveRequest', 'attachment');
    }

    /**
     * Deleting an Attachment record removes the associated file from
     * the local storage disk.
     */
    public function test_attachment_deletion_removes_file_from_disk(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('attachments/report.pdf', 'dummy-content');

        $department = Department::create(['name' => 'QA']);
        $leaveType = LeaveType::create(['name' => 'Sick', 'allowed_days' => 10]);
        $user = User::factory()->create([
            'department_id' => $department->id,
            'role' => 'Employee',
        ]);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'days_requested' => 2,
            'reason' => 'Unwell',
            'status' => 'Pending',
        ]);

        $attachment = Attachment::create([
            'leave_request_id' => $leaveRequest->id,
            'file_name' => 'report.pdf',
            'file_path' => 'attachments/report.pdf',
        ]);

        Storage::disk('local')->assertExists('attachments/report.pdf');

        $attachment->delete();

        Storage::disk('local')->assertMissing('attachments/report.pdf');
    }

    /**
     * Deleting a LeaveRequest cascades file cleanup to all of its
     * attachments via Eloquent events (not just DB cascade).
     */
    public function test_leave_request_deletion_removes_attachment_files(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('attachments/cert_a.pdf', 'content-a');
        Storage::disk('local')->put('attachments/cert_b.pdf', 'content-b');

        $department = Department::create(['name' => 'Sales']);
        $leaveType = LeaveType::create(['name' => 'Annual', 'allowed_days' => 20]);
        $user = User::factory()->create([
            'department_id' => $department->id,
            'role' => 'Employee',
        ]);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'days_requested' => 3,
            'reason' => 'Holiday',
            'status' => 'Approved',
        ]);

        Attachment::create([
            'leave_request_id' => $leaveRequest->id,
            'file_name' => 'cert_a.pdf',
            'file_path' => 'attachments/cert_a.pdf',
        ]);

        Attachment::create([
            'leave_request_id' => $leaveRequest->id,
            'file_name' => 'cert_b.pdf',
            'file_path' => 'attachments/cert_b.pdf',
        ]);

        Storage::disk('local')->assertExists('attachments/cert_a.pdf');
        Storage::disk('local')->assertExists('attachments/cert_b.pdf');

        $leaveRequest->delete();

        Storage::disk('local')->assertMissing('attachments/cert_a.pdf');
        Storage::disk('local')->assertMissing('attachments/cert_b.pdf');

        $this->assertDatabaseMissing('attachments', [
            'leave_request_id' => $leaveRequest->id,
        ]);
    }

    /**
     * Deleting a User removes their profile picture from the public
     * disk and cascades file cleanup through LeaveRequest → Attachment.
     */
    public function test_user_deletion_removes_profile_picture_and_attachment_files(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        Storage::disk('public')->put('profile_pictures/avatar.jpg', 'avatar-data');
        Storage::disk('local')->put('attachments/doc.pdf', 'doc-data');

        $department = Department::create(['name' => 'HR']);
        $leaveType = LeaveType::create(['name' => 'Maternity', 'allowed_days' => 90]);

        $user = User::factory()->create([
            'department_id' => $department->id,
            'role' => 'Employee',
            'profile_picture' => 'profile_pictures/avatar.jpg',
        ]);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
            'days_requested' => 3,
            'reason' => 'Medical',
            'status' => 'Pending',
        ]);

        Attachment::create([
            'leave_request_id' => $leaveRequest->id,
            'file_name' => 'doc.pdf',
            'file_path' => 'attachments/doc.pdf',
        ]);

        Storage::disk('public')->assertExists('profile_pictures/avatar.jpg');
        Storage::disk('local')->assertExists('attachments/doc.pdf');

        $user->delete();

        // Profile picture pruned from public disk
        Storage::disk('public')->assertMissing('profile_pictures/avatar.jpg');

        // Attachment file pruned from local disk (cascaded via Eloquent events)
        Storage::disk('local')->assertMissing('attachments/doc.pdf');

        // Database records fully removed
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('leave_requests', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('attachments', [
            'leave_request_id' => $leaveRequest->id,
        ]);
    }

    /**
     * Deleting a user without a profile picture does not throw errors.
     */
    public function test_user_deletion_without_profile_picture_does_not_error(): void
    {
        $department = Department::create(['name' => 'Finance']);

        $user = User::factory()->create([
            'department_id' => $department->id,
            'role' => 'Employee',
            'profile_picture' => null,
        ]);

        $user->delete();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /**
     * Deleting an attachment with an empty file_path does not throw errors.
     */
    public function test_attachment_deletion_with_empty_path_does_not_error(): void
    {
        Storage::fake('local');

        $department = Department::create(['name' => 'Ops']);
        $leaveType = LeaveType::create(['name' => 'Comp Off', 'allowed_days' => 5]);
        $user = User::factory()->create([
            'department_id' => $department->id,
            'role' => 'Employee',
        ]);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'days_requested' => 1,
            'reason' => 'Comp off',
            'status' => 'Pending',
        ]);

        $attachment = Attachment::create([
            'leave_request_id' => $leaveRequest->id,
            'file_name' => 'ghost.pdf',
            'file_path' => '',
        ]);

        $attachment->delete();

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    }
}
