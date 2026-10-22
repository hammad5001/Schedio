<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Department;
use App\Models\Meeting;
use App\Models\MeetingNote;
use App\Models\Room;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiCopilotIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Department $department;
    protected User $user;
    protected User $colleague;
    protected Room $room;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'Acme Corporation']);
        $this->department = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Product Engineering',
            'code' => 'ENG',
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'role' => User::ROLE_MANAGER,
            'approval_status' => User::STATUS_APPROVED,
            'timezone' => 'UTC',
        ]);

        $this->colleague = User::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'name' => 'Sarah Connor',
            'role' => User::ROLE_EMPLOYEE,
            'approval_status' => User::STATUS_APPROVED,
        ]);

        $this->room = Room::create([
            'company_id' => $this->company->id,
            'name' => 'Apollo Boardroom',
            'code' => 'APOLLO-101',
            'capacity' => 12,
            'location' => 'Building A, 3rd Floor',
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'company_id' => $this->company->id,
            'company_name' => 'Starlight Media',
            'contact_name' => 'Elena Fisher',
            'contact_email' => 'elena@starlight.io',
            'contact_phone' => '+1-555-0199',
            'contact_role' => 'VP Technology',
            'status' => Client::STATUS_PAPERWORK_ONBOARDING,
            'deal_value' => 45000.00,
            'notes' => 'Contract sent for digital signature.',
            'last_activity_at' => Carbon::now()->subDays(6),
        ]);
    }

    /**
     * Test Mobile AI Chat endpoint returns answers and smart dynamic suggestions.
     */
    public function test_ai_copilot_chat_returns_valid_response(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/ai/chat', [
            'message' => 'Kaunse rooms available hain hamari company me?',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'answer',
                    'suggestions',
                ],
            ]);

        $this->assertStringContainsString('Apollo Boardroom', $response->json('data.answer'));
    }

    /**
     * Test Natural Language Quick Schedule preview mode (conflict analysis without auto-booking).
     */
    public function test_ai_quick_schedule_parses_and_previews(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/ai/quick-schedule', [
            'prompt' => 'Schedule a 30-minute sync with Sarah tomorrow at 3pm in Apollo Boardroom',
            'auto_create' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'status',
                    'created',
                    'has_conflicts',
                    'summary',
                    'parsed' => [
                        'title',
                        'start_time',
                        'end_time',
                        'duration_minutes',
                    ],
                ],
            ]);

        $this->assertFalse($response->json('data.created'));
        $this->assertEquals(30, $response->json('data.parsed.duration_minutes'));
    }

    /**
     * Test Natural Language Quick Schedule with auto_create=true books meeting directly in DB.
     */
    public function test_ai_quick_schedule_auto_creates_meeting(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/ai/quick-schedule', [
            'prompt' => 'Schedule team architecture sync tomorrow at 10am',
            'auto_create' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'status',
                    'created',
                    'meeting' => [
                        'id',
                        'title',
                        'start_time',
                        'end_time',
                        'status',
                    ],
                ],
            ]);

        $this->assertTrue($response->json('data.created'));
        $meetingId = $response->json('data.meeting.id');

        $this->assertDatabaseHas('meetings', [
            'id' => $meetingId,
            'company_id' => $this->company->id,
            'organizer_id' => $this->user->id,
            'status' => 'scheduled',
        ]);
    }

    /**
     * Test AI Daily Executive Briefing calculation with meetings and focus blocks.
     */
    public function test_ai_daily_briefing_generates_schedule_and_deep_work_blocks(): void
    {
        Sanctum::actingAs($this->user);

        // Create a meeting today at 2:00 PM - 3:00 PM
        $meetingToday = Meeting::create([
            'company_id' => $this->company->id,
            'title' => 'Client Demo with Starlight',
            'organizer_id' => $this->user->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room->id,
            'client_id' => $this->client->id,
            'start_time' => Carbon::today()->setTime(14, 0, 0),
            'end_time' => Carbon::today()->setTime(15, 0, 0),
            'status' => 'scheduled',
            'type' => 'client',
        ]);
        $meetingToday->participants()->attach($this->user->id, ['status' => 'accepted', 'role' => 'organizer']);

        // Create a task due today
        Task::create([
            'company_id' => $this->company->id,
            'title' => 'Submit Q4 Engineering Roadmap',
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
            'department_id' => $this->department->id,
            'status' => 'pending',
            'priority' => 'high',
            'due_date' => Carbon::today()->setTime(17, 0, 0),
        ]);

        $response = $this->getJson('/api/v1/ai/briefing?date=' . Carbon::today()->format('Y-m-d'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'date',
                    'greeting',
                    'headline',
                    'summary',
                    'ai_productivity_tip',
                    'meetings_count',
                    'tasks_count',
                    'focus_blocks',
                    'meetings',
                    'tasks',
                ],
            ]);

        $this->assertEquals(1, $response->json('data.meetings_count'));
        $this->assertEquals(1, $response->json('data.tasks_count'));
        $this->assertNotEmpty($response->json('data.focus_blocks'));
    }

    /**
     * Test AI Meeting Notes Summarizer generates executive notes and auto-creates tasks.
     */
    public function test_ai_meeting_notes_summarizer_extracts_decisions_and_creates_tasks(): void
    {
        Sanctum::actingAs($this->user);

        $meeting = Meeting::create([
            'company_id' => $this->company->id,
            'title' => 'Sprint 42 Retrospective',
            'organizer_id' => $this->user->id,
            'department_id' => $this->department->id,
            'room_id' => $this->room->id,
            'start_time' => Carbon::yesterday()->setTime(10, 0, 0),
            'end_time' => Carbon::yesterday()->setTime(11, 0, 0),
            'status' => 'scheduled',
            'type' => 'internal',
        ]);
        $meeting->participants()->attach($this->user->id, ['status' => 'accepted', 'role' => 'organizer']);
        $meeting->participants()->attach($this->colleague->id, ['status' => 'accepted', 'role' => 'participant']);

        $rawTranscript = "The team reviewed the Sprint 42 accomplishments. We decided to transition microservices to Docker Compose. Sarah Connor will prepare the production migration plan by next Friday. We also decided to mandate PR reviews by 2 engineers.";

        $response = $this->postJson("/api/v1/ai/summarize-meeting/{$meeting->id}", [
            'raw_notes' => $rawTranscript,
            'auto_create_tasks' => true,
            'save_notes' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'meeting_id',
                    'executive_summary',
                    'key_decisions',
                    'action_items',
                    'saved_note_id',
                    'created_tasks_count',
                    'created_tasks',
                ],
            ]);

        $noteId = $response->json('data.saved_note_id');
        $this->assertNotNull($noteId);
        $this->assertDatabaseHas('meeting_notes', [
            'id' => $noteId,
            'meeting_id' => $meeting->id,
            'company_id' => $this->company->id,
        ]);

        // Verify task was automatically created
        $this->assertGreaterThanOrEqual(1, $response->json('data.created_tasks_count'));
        $this->assertDatabaseHas('tasks', [
            'meeting_id' => $meeting->id,
            'company_id' => $this->company->id,
        ]);
    }

    /**
     * Test AI CRM Deal Intelligence calculates health score and drafts follow-up email.
     */
    public function test_ai_crm_deal_insights_calculates_health_and_generates_followup(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/ai/deal-insights/{$this->client->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'client_id',
                    'company_name',
                    'contact_name',
                    'stage',
                    'health_score',
                    'health_status',
                    'next_best_action',
                    'follow_up_draft' => [
                        'subject',
                        'body',
                    ],
                ],
            ]);

        $this->assertEquals($this->client->company_name, $response->json('data.company_name'));
        $this->assertNotEmpty($response->json('data.follow_up_draft.subject'));
        $this->assertNotEmpty($response->json('data.follow_up_draft.body'));
    }

    /**
     * Test strict tenant isolation on AI endpoints.
     */
    public function test_ai_endpoints_enforce_tenant_isolation(): void
    {
        $otherCompany = Company::create(['name' => 'Competitor Corp']);
        $foreignMeeting = Meeting::create([
            'company_id' => $otherCompany->id,
            'title' => 'Top Secret Acquisition',
            'organizer_id' => $this->user->id,
            'start_time' => Carbon::now()->addDay(),
            'end_time' => Carbon::now()->addDay()->addHour(),
            'status' => 'scheduled',
            'type' => 'internal',
        ]);

        $foreignClient = Client::create([
            'company_id' => $otherCompany->id,
            'company_name' => 'Foreign Corp',
            'contact_name' => 'Secret Agent',
            'contact_email' => 'agent@secret.com',
            'status' => Client::STATUS_DISCOVERY,
        ]);

        Sanctum::actingAs($this->user);

        // Foreign meeting summarization should be forbidden (403)
        $resMeeting = $this->postJson("/api/v1/ai/summarize-meeting/{$foreignMeeting->id}", [
            'raw_notes' => 'Testing secret leaks',
        ]);
        $resMeeting->assertStatus(403);

        // Foreign client deal insights should be forbidden (403)
        $resClient = $this->postJson("/api/v1/ai/deal-insights/{$foreignClient->id}");
        $resClient->assertStatus(403);
    }
}
