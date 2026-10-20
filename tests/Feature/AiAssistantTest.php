<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use App\Models\Meeting;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create([
            'name' => 'Nexus Technologies',
            'status' => Company::STATUS_ACTIVE,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => User::ROLE_EMPLOYEE,
            'approval_status' => User::STATUS_APPROVED,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_ai_chat(): void
    {
        $response = $this->postJson(route('ai.chat'), [
            'message' => 'Meri meetings batao',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_receives_answer_for_meetings_query(): void
    {
        // Create an upcoming meeting
        $meeting = Meeting::create([
            'company_id' => $this->company->id,
            'organizer_id' => $this->user->id,
            'title' => 'Q4 Product Roadmap Discussion',
            'start_time' => Carbon::tomorrow()->setTime(14, 0),
            'end_time' => Carbon::tomorrow()->setTime(15, 0),
            'status' => 'scheduled',
            'type' => 'internal',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('ai.chat'), [
            'message' => 'Meri kal ki meeting batao',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'answer',
                'suggestions',
            ])
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertStringContainsString('Q4 Product Roadmap Discussion', $response->json('answer'));
    }

    public function test_ai_answers_questions_about_rooms_with_tenancy_isolation(): void
    {
        // Room in user's company
        Room::create([
            'company_id' => $this->company->id,
            'name' => 'Sapphire Executive Suite',
            'code' => 'CONF-SAPPHIRE',
            'capacity' => 12,
            'location' => 'Level 4, East Wing',
            'is_active' => true,
        ]);

        // Room in another company
        $otherCompany = Company::factory()->create(['name' => 'Rival Corp']);
        Room::create([
            'company_id' => $otherCompany->id,
            'name' => 'Forbidden Secret Room',
            'code' => 'CONF-FORBIDDEN',
            'capacity' => 20,
            'location' => 'Basement',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('ai.chat'), [
            'message' => 'Kaunse rooms available hain?',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('Sapphire Executive Suite', $response->json('answer'));
        $this->assertStringNotContainsString('Forbidden Secret Room', $response->json('answer'));
    }

    public function test_ai_explains_revert_feature_for_mistaken_ceo_applications(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('ai.chat'), [
            'message' => 'Staff ne CEO ke lia apply kr dia revert kaise hoga?',
        ]);

        $response->assertOk();
        $answer = $response->json('answer');
        $this->assertStringContainsStringIgnoringCase('revert', $answer);
        $this->assertStringContainsString('registration-requests', $answer);
    }

    public function test_user_can_clear_ai_conversation_history(): void
    {
        $this->actingAs($this->user)->withSession([
            'ai_chat_history' => [
                ['role' => 'user', 'content' => 'Hello'],
                ['role' => 'model', 'content' => 'Salam'],
            ],
        ]);

        $response = $this->actingAs($this->user)->postJson(route('ai.clear'));

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertNull(session('ai_chat_history'));
    }
}
