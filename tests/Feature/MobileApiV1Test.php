<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\DeviceToken;
use App\Models\Meeting;
use App\Models\Room;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileApiV1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_mobile_login_returns_token_and_user_profile(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@schedio.com',
            'password' => 'password',
            'device_name' => 'Pixel 8 Pro',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
            'user' => [
                'id',
                'name',
                'email',
                'role',
                'role_label',
                'timezone',
                'company' => ['id', 'name', 'slug'],
                'department' => ['id', 'name', 'code'],
                'booking_url',
                'booking_username',
                'can_administer',
                'can_manage_rooms',
                'notification_preferences',
            ],
        ]);

        $this->assertNotEmpty($response->json('token'));
        $this->assertEquals('Alex Rivera', $response->json('user.name'));
        $this->assertTrue($response->json('user.can_administer'));
    }

    public function test_pending_user_is_blocked_with_403_and_account_status(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'sophia.lin@schedio.com',
            'password' => 'password',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'account_status' => 'pending',
        ]);
    }

    public function test_wrong_credentials_return_401(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@schedio.com',
            'password' => 'wrong-pass',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_me_endpoint_returns_current_user(): void
    {
        $user = User::where('email', 'marcus.v@schedio.com')->first();
        $token = $user->createToken('test_device')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $response->assertJsonPath('data.email', 'marcus.v@schedio.com');
        $response->assertJsonPath('data.role', 'employee');
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::where('email', 'admin@schedio.com')->first();
        $token = $user->createToken('test_device')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);

        // Reset auth guards to simulate fresh HTTP request
        auth()->forgetGuards();

        // Subsequent call with revoked token returns 401
        $subsequent = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $subsequent->assertStatus(401);
    }

    public function test_push_device_token_registration_and_deletion(): void
    {
        $user = User::where('email', 'admin@schedio.com')->first();
        $token = $user->createToken('test_device')->plainTextToken;

        // 1. Register device
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/devices', [
                'token' => 'fcm_mock_token_abc_123',
                'platform' => 'android',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm_mock_token_abc_123',
            'platform' => 'android',
        ]);

        // 2. Refresh same device -> 200
        $refreshResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/devices', [
                'token' => 'fcm_mock_token_abc_123',
                'platform' => 'android',
            ]);
        $refreshResponse->assertStatus(200);

        // 3. Delete device on sign-out -> 204
        $deleteResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/v1/devices/fcm_mock_token_abc_123');
        $deleteResponse->assertStatus(204);

        $this->assertDatabaseMissing('device_tokens', [
            'token' => 'fcm_mock_token_abc_123',
        ]);
    }

    public function test_meetings_listing_and_creation(): void
    {
        $user = User::where('email', 'admin@schedio.com')->first();
        $token = $user->createToken('test_device')->plainTextToken;

        // 1. Get meetings list
        $indexResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/meetings');

        $indexResponse->assertStatus(200);
        $indexResponse->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'start_time',
                    'end_time',
                    'status',
                    'can_manage',
                    'can_rsvp',
                    'organizer',
                    'department',
                    'room',
                    'participants',
                ],
            ],
            'meta' => ['total', 'current_page'],
        ]);

        // 2. Create meeting via API
        $createResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/meetings', [
                'title' => 'Mobile Sync & Architecture Discussion',
                'description' => 'Discussion on mobile Flutter endpoints integration.',
                'start_time' => now()->addDays(3)->setTime(14, 0)->toIso8601String(),
                'end_time' => now()->addDays(3)->setTime(15, 0)->toIso8601String(),
                'timezone' => 'America/New_York',
                'type' => 'virtual',
                'meeting_link' => 'https://meet.schedio.com/mobile-sync',
                'reminder_minutes' => 15,
            ]);

        $createResponse->assertStatus(201);
        $this->assertDatabaseHas('meetings', [
            'title' => 'Mobile Sync & Architecture Discussion',
            'organizer_id' => $user->id,
        ]);
    }

    public function test_clients_pipeline_listing_and_status_transition(): void
    {
        $user = User::where('email', 'admin@schedio.com')->first();
        $token = $user->createToken('test_device')->plainTextToken;

        // 1. Create client
        $createResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/clients', [
                'company_name' => 'Acme Logistics Global',
                'contact_name' => 'John Doe',
                'contact_email' => 'john@acmelogistics.com',
                'status' => 'discovery',
                'deal_value' => 25000,
            ]);

        $createResponse->assertStatus(201);
        $clientId = $createResponse->json('data.id');

        // 2. List clients with meta.total
        $listResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/clients?per_page=100');

        $listResponse->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $listResponse->json('meta.total'));

        // 3. Update status to scheduled
        $statusResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/v1/clients/{$clientId}/status", [
                'status' => 'scheduled',
                'progress' => 'Discovery call completed. Contract drafting in progress.',
            ]);

        $statusResponse->assertStatus(200);
        $this->assertEquals('scheduled', $statusResponse->json('data.status'));
    }

    public function test_public_booking_flow_via_api(): void
    {
        $host = User::where('email', 'admin@schedio.com')->first();
        $username = $host->username ?: (string) $host->id;

        // 1. Get host booking info
        $hostResponse = $this->getJson("/api/v1/public/booking/{$username}");
        $hostResponse->assertStatus(200);
        $hostResponse->assertJsonPath('data.name', 'Alex Rivera');

        // 2. Get available slots
        $tomorrow = now()->addDay()->format('Y-m-d');
        $slotsResponse = $this->getJson("/api/v1/public/booking/{$username}/slots?date={$tomorrow}&duration=30");
        $slotsResponse->assertStatus(200);
        $slots = $slotsResponse->json('slots');
        $this->assertNotEmpty($slots);

        // 3. Book a slot
        $slotToBook = $slots[0]['start_time'];
        $bookResponse = $this->postJson("/api/v1/public/booking/{$username}", [
            'client_name' => 'Michael Scott',
            'client_email' => 'michael@dundermifflin.com',
            'client_company' => 'Dunder Mifflin Paper',
            'start_time' => $slotToBook,
            'duration_minutes' => 30,
            'notes' => 'Discuss enterprise scheduling license.',
        ]);

        $bookResponse->assertStatus(201);
        $this->assertNotEmpty($bookResponse->json('data.calendar_export_url'));
        $this->assertDatabaseHas('meetings', [
            'title' => 'Consultation: Michael Scott & Alex Rivera',
        ]);
    }

    public function test_registration_options_and_register_flow_via_api(): void
    {
        // 1. Get registration options (public)
        $optionsResponse = $this->getJson('/api/v1/auth/registration-options');
        $optionsResponse->assertStatus(200);
        $optionsResponse->assertJsonStructure([
            'companies',
            'departments',
        ]);
        $this->assertNotEmpty($optionsResponse->json('companies'));

        // 2. Submit new employee registration
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Sara Connor',
            'email' => 'sara.connor@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'title' => 'Security Specialist',
            'registration_notes' => 'Joining cyber defence team.',
        ]);

        $registerResponse->assertStatus(201);
        $registerResponse->assertJson([
            'account_status' => 'pending',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'sara.connor@example.com',
            'approval_status' => 'pending',
        ]);
    }

    public function test_dashboard_and_lookups_endpoints(): void
    {
        $user = User::where('email', 'admin@schedio.com')->first();
        $token = $user->createToken('test_device')->plainTextToken;

        // 1. Dashboard
        $dashResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/dashboard');

        $dashResponse->assertStatus(200);
        $dashResponse->assertJsonStructure([
            'metrics' => [
                'today_meetings_count',
                'total_users',
                'upcoming_meetings',
                'active_reminders',
                'pending_tasks',
            ],
            'today_meetings',
            'today_events',
            'upcoming_meetings',
            'pending_tasks',
            'upcoming_reminders',
        ]);

        // 2. Lookups
        $lookupResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/lookups');

        $lookupResponse->assertStatus(200);
        $lookupResponse->assertJsonStructure([
            'departments',
            'rooms',
            'users',
            'roles',
            'task_statuses',
            'task_priorities',
            'event_categories',
        ]);
    }

    public function test_meeting_notes_crud_via_api(): void
    {
        $user = User::where('email', 'admin@schedio.com')->first();
        $token = $user->createToken('test_device')->plainTextToken;
        $meeting = Meeting::first();

        // 1. Create meeting note
        $createResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/meetings/{$meeting->id}/notes", [
                'agenda' => 'Quarterly Q4 Review',
                'discussion' => 'Reviewed OKRs and infrastructure plans.',
                'decisions' => 'Approved Flutter mobile release candidate.',
                'action_items' => 'Deploy API v1 and verify 58 routes.',
            ]);

        $createResponse->assertStatus(201);
        $noteId = $createResponse->json('data.id');
        $this->assertNotEmpty($noteId);

        // 2. Get meeting note by ID
        $getResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/meeting-notes/{$noteId}");

        $getResponse->assertStatus(200);
        $this->assertEquals('Quarterly Q4 Review', $getResponse->json('data.agenda'));

        // 3. Update meeting note
        $updateResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/meeting-notes/{$noteId}", [
                'agenda' => 'Quarterly Q4 Review - Finalized',
                'decisions' => 'Mobile API contract 100% verified.',
            ]);

        $updateResponse->assertStatus(200);
        $this->assertEquals('Quarterly Q4 Review - Finalized', $updateResponse->json('data.agenda'));

        // 4. Delete meeting note
        $deleteResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/meeting-notes/{$noteId}");

        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('meeting_notes', ['id' => $noteId]);
    }

    public function test_rooms_crud_and_show_via_api(): void
    {
        $user = User::where('email', 'admin@schedio.com')->first();
        $token = $user->createToken('test_device')->plainTextToken;

        // 1. List rooms
        $indexResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/rooms');

        $indexResponse->assertStatus(200);
        $this->assertNotEmpty($indexResponse->json('data'));

        // 2. Create room
        $createResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/rooms', [
                'name' => 'Zeus Boardroom',
                'code' => 'ZEUS-01',
                'location' => 'Executive Tower 10th Floor',
                'capacity' => 20,
                'amenities' => ['4K Projector', 'Whiteboard', 'Polycom AV'],
            ]);

        $createResponse->assertStatus(201);
        $roomId = $createResponse->json('data.id');

        // 3. Show room
        $showResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/rooms/{$roomId}");

        $showResponse->assertStatus(200);
        $this->assertEquals('Zeus Boardroom', $showResponse->json('data.name'));

        // 4. Update room
        $updateResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/rooms/{$roomId}", [
                'name' => 'Zeus Executive Boardroom',
                'code' => 'ZEUS-01',
                'location' => 'Executive Tower 10th Floor',
                'capacity' => 25,
            ]);

        $updateResponse->assertStatus(200);
        $this->assertEquals('Zeus Executive Boardroom', $updateResponse->json('data.name'));

        // 5. Delete room
        $deleteResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/rooms/{$roomId}");

        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('rooms', ['id' => $roomId]);
    }

    public function test_events_crud_via_api(): void
    {
        $user = User::where('email', 'admin@schedio.com')->first();
        $token = $user->createToken('test_device')->plainTextToken;

        // 1. Create event
        $createResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/events', [
                'title' => 'Annual Strategy Summit 2026',
                'description' => 'Global company alignment and product roadmaps.',
                'category' => 'milestone',
                'start_time' => now()->addDays(10)->setTime(9, 0)->toIso8601String(),
                'end_time' => now()->addDays(10)->setTime(18, 0)->toIso8601String(),
                'location' => 'Main Auditorium & Virtual Stream',
                'is_company_wide' => true,
            ]);

        $createResponse->assertStatus(201);
        $eventId = $createResponse->json('data.id');

        // 2. List events
        $indexResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/events');

        $indexResponse->assertStatus(200);
        $this->assertNotEmpty($indexResponse->json('data'));

        // 3. Show event
        $showResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/events/{$eventId}");

        $showResponse->assertStatus(200);
        $this->assertEquals('Annual Strategy Summit 2026', $showResponse->json('data.title'));

        // 4. Update event
        $updateResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/events/{$eventId}", [
                'title' => 'Annual Strategy Summit 2026 (Updated)',
                'category' => 'milestone',
                'start_time' => now()->addDays(10)->setTime(9, 30)->toIso8601String(),
                'end_time' => now()->addDays(10)->setTime(18, 0)->toIso8601String(),
            ]);

        $updateResponse->assertStatus(200);
        $this->assertEquals('Annual Strategy Summit 2026 (Updated)', $updateResponse->json('data.title'));

        // 5. Delete event
        $deleteResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/events/{$eventId}");

        $deleteResponse->assertStatus(200);
        $this->assertSoftDeleted('events', ['id' => $eventId]);
    }

    public function test_admin_registration_requests_management_via_api(): void
    {
        $admin = User::where('email', 'admin@schedio.com')->first();
        $token = $admin->createToken('test_device')->plainTextToken;

        // Create a pending applicant
        $applicant = User::create([
            'company_id' => $admin->company_id,
            'name' => 'David Miller',
            'email' => 'david.m@schedio.com',
            'password' => bcrypt('Secret123!'),
            'role' => 'employee',
            'approval_status' => 'pending',
            'registration_notes' => 'Software developer candidate.',
            'is_active' => true,
        ]);

        // 1. List requests
        $listResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/registration-requests?status=pending');

        $listResponse->assertStatus(200);
        $listResponse->assertJsonStructure(['data', 'counts']);

        // 2. Get options for approving
        $optionsResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/v1/admin/registration-requests/{$applicant->id}/options");

        $optionsResponse->assertStatus(200);
        $optionsResponse->assertJsonStructure(['roles', 'departments']);

        // 3. Approve applicant
        $approveResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/v1/admin/registration-requests/{$applicant->id}/approve", [
                'role' => 'employee',
                'title' => 'Senior Backend Engineer',
            ]);

        $approveResponse->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $applicant->id,
            'approval_status' => 'approved',
            'title' => 'Senior Backend Engineer',
        ]);

        // 4. Reject applicant
        $rejectResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/v1/admin/registration-requests/{$applicant->id}/reject", [
                'rejection_reason' => 'Background verification inconclusive.',
            ]);

        $rejectResponse->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $applicant->id,
            'approval_status' => 'rejected',
        ]);
    }

    public function test_meeting_reschedule_update_and_cancel_via_api(): void
    {
        $user = User::where('email', 'admin@schedio.com')->first();
        $token = $user->createToken('test_device')->plainTextToken;
        $meeting = Meeting::first();

        // 1. Reschedule / update meeting -> returns 200 without HTTP 500
        $newStart = now()->addDays(5)->setTime(10, 0)->toIso8601String();
        $newEnd = now()->addDays(5)->setTime(11, 0)->toIso8601String();

        $updateResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/meetings/{$meeting->id}", [
                'title' => 'Updated Strategy Alignment',
                'description' => 'Updated description for roadmap.',
                'start_time' => $newStart,
                'end_time' => $newEnd,
                'timezone' => 'America/New_York',
                'type' => 'virtual',
                'meeting_link' => 'https://meet.schedio.com/updated-strategy',
            ]);

        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonPath('data.title', 'Updated Strategy Alignment');
        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
            'title' => 'Updated Strategy Alignment',
        ]);

        // 2. Cancel meeting -> returns 200 without HTTP 500
        $cancelResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/v1/meetings/{$meeting->id}/cancel");

        $cancelResponse->assertStatus(200);
        $cancelResponse->assertJsonPath('data.status', 'cancelled');
        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_meeting_delete_via_api(): void
    {
        $user = User::where('email', 'admin@schedio.com')->first();
        $token = $user->createToken('test_device')->plainTextToken;
        $meeting = Meeting::first();

        // Delete meeting -> returns 204 No Content without HTTP 500 and soft deletes record
        $deleteResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/meetings/{$meeting->id}");

        $deleteResponse->assertStatus(204);
        $this->assertSoftDeleted('meetings', [
            'id' => $meeting->id,
        ]);
    }

    public function test_task_update_and_delete_via_api(): void
    {
        $user = User::where('email', 'admin@schedio.com')->first();
        $assignee = User::where('email', 'marcus.v@schedio.com')->first();
        $token = $user->createToken('test_device')->plainTextToken;

        // 1. Create task
        $task = Task::create([
            'company_id' => $user->company_id,
            'title' => 'Initial Task Title',
            'description' => 'Initial description',
            'assigned_to' => $assignee->id,
            'created_by' => $user->id,
            'status' => 'pending',
            'priority' => 'medium',
        ]);

        // 2. Update task details via PUT /api/v1/tasks/{id}
        $updateResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/v1/tasks/{$task->id}", [
                'title' => 'Updated Task Title With Priorities',
                'description' => 'Comprehensive instructions.',
                'assigned_to' => $assignee->id,
                'priority' => 'urgent',
                'status' => 'in_progress',
                'due_date' => now()->addDays(2)->toIso8601String(),
            ]);

        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonPath('data.title', 'Updated Task Title With Priorities');
        $updateResponse->assertJsonPath('data.priority', 'urgent');
        $updateResponse->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Task Title With Priorities',
            'priority' => 'urgent',
            'status' => 'in_progress',
        ]);

        // 3. Delete task via DELETE /api/v1/tasks/{id}
        $deleteResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/tasks/{$task->id}");

        $deleteResponse->assertStatus(200);
        $deleteResponse->assertJsonPath('message', 'Task deleted successfully.');
        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_empty_meeting_note_is_rejected_with_422(): void
    {
        $user = User::where('email', 'admin@schedio.com')->first();
        $token = $user->createToken('test_device')->plainTextToken;
        $meeting = Meeting::first();

        // Submitting completely empty note must return HTTP 422
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/meetings/{$meeting->id}/notes", [
                'agenda' => '',
                'discussion' => '   ',
                'decisions' => null,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['content']);
    }

    public function test_rooms_listing_returns_pagination_metadata(): void
    {
        $user = User::where('email', 'admin@schedio.com')->first();
        $token = $user->createToken('test_device')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/rooms?per_page=2');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'code',
                    'location',
                    'capacity',
                    'color',
                    'amenities',
                    'is_active',
                ],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
        ]);

        $this->assertEquals(2, $response->json('meta.per_page'));
    }

    public function test_cleanup_command_safely_removes_only_test_sandbox_records(): void
    {
        $company = \App\Models\Company::first();
        $user = User::where('email', 'admin@schedio.com')->first();

        // Create 2 sandbox test meetings (1 cancelled, 1 scheduled)
        $cancelledTestMeeting = Meeting::create([
            'company_id' => $company->id,
            'title' => '[TEST] Cancelled Sandbox Meeting',
            'organizer_id' => $user->id,
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'status' => 'cancelled',
            'type' => 'virtual',
        ]);

        $scheduledTestMeeting = Meeting::create([
            'company_id' => $company->id,
            'title' => '[TEST] Scheduled Meeting (Must Preserve)',
            'organizer_id' => $user->id,
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHour(),
            'status' => 'scheduled',
            'type' => 'virtual',
        ]);

        // Create 2 sandbox test tasks (1 completed, 1 pending)
        $completedTestTask = Task::create([
            'company_id' => $company->id,
            'title' => '[TEST] Completed Mobile Verification',
            'assigned_to' => $user->id,
            'created_by' => $user->id,
            'status' => 'completed',
            'priority' => 'low',
        ]);

        $pendingTestTask = Task::create([
            'company_id' => $company->id,
            'title' => '[TEST] Pending Task (Must Preserve)',
            'assigned_to' => $user->id,
            'created_by' => $user->id,
            'status' => 'pending',
            'priority' => 'low',
        ]);

        // Run cleanup with --force
        $this->artisan('schedio:cleanup-test-records', ['--force' => true])
            ->assertSuccessful();

        // The cancelled [TEST] meeting must be permanently purged
        $this->assertDatabaseMissing('meetings', ['id' => $cancelledTestMeeting->id]);
        // The scheduled [TEST] meeting must be untouched
        $this->assertDatabaseHas('meetings', ['id' => $scheduledTestMeeting->id]);

        // The completed [TEST] task must be permanently purged
        $this->assertDatabaseMissing('tasks', ['id' => $completedTestTask->id]);
        // The pending [TEST] task must be untouched
        $this->assertDatabaseHas('tasks', ['id' => $pendingTestTask->id]);
    }
}
