<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;
use App\Services\FirebaseCloudMessagingService;
use App\Services\NotificationDispatchService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FirebaseCloudMessagingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_firebase_service_handles_unconfigured_gracefully(): void
    {
        $service = new FirebaseCloudMessagingService();

        // When credentials file is absent, it reports unconfigured and skips
        $result = $service->sendToToken('dummy_fcm_token_123', 'Test Title', 'Test Body');

        $this->assertFalse($result['success']);
        $this->assertEquals('unconfigured', $result['status']);
    }

    public function test_device_tokens_management_and_user_relationship(): void
    {
        $user = User::where('email', 'admin@schedio.com')->first();
        $token = $user->createToken('test_device')->plainTextToken;

        // Register Android device token
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/devices', [
                'token' => 'mock_android_fcm_token_xyz_456',
                'platform' => 'android',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'mock_android_fcm_token_xyz_456',
            'platform' => 'android',
        ]);

        // Check model relationship
        $this->assertTrue($user->deviceTokens()->where('token', 'mock_android_fcm_token_xyz_456')->exists());

        // Test unregister device token
        $deleteResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/v1/devices/mock_android_fcm_token_xyz_456');

        $deleteResponse->assertStatus(204);
        $this->assertDatabaseMissing('device_tokens', [
            'token' => 'mock_android_fcm_token_xyz_456',
        ]);
    }

    public function test_api_test_push_notification_endpoint(): void
    {
        $user = User::where('email', 'admin@schedio.com')->first();
        $token = $user->createToken('test_device')->plainTextToken;

        // Register a device token first
        DeviceToken::create([
            'user_id' => $user->id,
            'token' => 'test_device_fcm_token_abc',
            'platform' => 'android',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/notifications/test-push', [
                'title' => 'Schedio Android Physical Test',
                'body' => 'Testing foreground and background delivery.',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'configured',
            'result',
        ]);
    }

    public function test_notification_dispatch_service_triggers_fcm_without_errors(): void
    {
        $meeting = Meeting::first();
        $dispatchService = app(NotificationDispatchService::class);

        // Should execute meeting invitation, update, cancellation without throwing
        $dispatchService->notifyMeetingInvitation($meeting);
        $dispatchService->notifyMeetingUpdated($meeting, ['time']);
        $dispatchService->notifyMeetingCancelled($meeting, 'Alex Rivera');

        $task = Task::first();
        if ($task) {
            $dispatchService->notifyTaskActivity($task, 'assigned');
            $dispatchService->notifyTaskActivity($task, 'completed');
        }

        $this->assertTrue(true);
    }

    public function test_artisan_firebase_test_push_command(): void
    {
        $user = User::where('email', 'admin@schedio.com')->first();
        DeviceToken::create([
            'user_id' => $user->id,
            'token' => 'artisan_test_fcm_token_789',
            'platform' => 'android',
        ]);

        $this->artisan('firebase:test-push', ['target' => $user->email])
            ->assertExitCode(0);
    }
}
