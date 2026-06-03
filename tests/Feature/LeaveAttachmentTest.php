<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\Attachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $employee;
    protected User $manager;
    protected User $admin;
    protected User $otherUser;
    protected LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create(['role' => 'Manager']);
        
        $this->employee = User::factory()->create([
            'role' => 'Employee',
            'manager_id' => $this->manager->id,
        ]);

        $this->admin = User::factory()->create(['role' => 'HR/Admin']);
        $this->otherUser = User::factory()->create(['role' => 'Employee']);

        $this->leaveType = LeaveType::create([
            'name' => 'Medical Leave',
            'allowed_days' => 15,
            'carry_forward' => false,
        ]);
    }

    public function test_attachment_upload_stores_file_privately(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $file = UploadedFile::fake()->create('medical_cert.pdf', 500);

        $response = $this->actingAs($this->employee)->post('/leaves', [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addDays(3)->format('Y-m-d'),
            'reason' => 'Surgical recovery',
            'attachment' => $file,
        ]);

        $response->assertRedirect(route('leaves.index'));

        // Check it was stored on 'local' disk and NOT on 'public' disk
        $attachment = Attachment::first();
        $this->assertNotNull($attachment);
        
        Storage::disk('local')->assertExists($attachment->file_path);
        Storage::disk('public')->assertMissing($attachment->file_path);
    }

    public function test_unauthorized_user_cannot_access_attachment(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('confidential.pdf', 100);
        $path = Storage::disk('local')->putFile('attachments', $file);

        $request = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'days_requested' => 2,
            'reason' => 'Confidential health matter',
            'status' => 'Pending',
        ]);

        $attachment = $request->attachments()->create([
            'file_name' => 'confidential.pdf',
            'file_path' => $path,
        ]);

        // Attempting to access as another unrelated employee should fail
        $response = $this->actingAs($this->otherUser)->get(
            route('leaves.attachment', [$request, $attachment])
        );

        $response->assertStatus(403);
    }

    public function test_owner_can_access_attachment(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('medical.pdf', 100);
        $path = Storage::disk('local')->putFile('attachments', $file);

        $request = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'days_requested' => 2,
            'reason' => 'Medical leave',
            'status' => 'Pending',
        ]);

        $attachment = $request->attachments()->create([
            'file_name' => 'medical.pdf',
            'file_path' => $path,
        ]);

        $response = $this->actingAs($this->employee)->get(
            route('leaves.attachment', [$request, $attachment])
        );

        $response->assertStatus(200);
    }

    public function test_manager_can_access_subordinate_attachment(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('medical.pdf', 100);
        $path = Storage::disk('local')->putFile('attachments', $file);

        $request = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'days_requested' => 2,
            'reason' => 'Subordinate medical leave',
            'status' => 'Pending',
        ]);

        $attachment = $request->attachments()->create([
            'file_name' => 'medical.pdf',
            'file_path' => $path,
        ]);

        $response = $this->actingAs($this->manager)->get(
            route('leaves.attachment', [$request, $attachment])
        );

        $response->assertStatus(200);
    }

    public function test_admin_can_access_any_attachment(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('medical.pdf', 100);
        $path = Storage::disk('local')->putFile('attachments', $file);

        $request = LeaveRequest::create([
            'user_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'days_requested' => 2,
            'reason' => 'Sick',
            'status' => 'Pending',
        ]);

        $attachment = $request->attachments()->create([
            'file_name' => 'medical.pdf',
            'file_path' => $path,
        ]);

        $response = $this->actingAs($this->admin)->get(
            route('leaves.attachment', [$request, $attachment])
        );

        $response->assertStatus(200);
    }
}
