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
}
