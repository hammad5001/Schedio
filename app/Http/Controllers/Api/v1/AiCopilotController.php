<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Meeting;
use App\Models\User;
use App\Services\Ai\SchedioAiAgentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiCopilotController extends Controller
{
    public function __construct(
        protected SchedioAiAgentService $aiService
    ) {}

    /**
     * Interactive AI Copilot Chat for Mobile.
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'history' => ['nullable', 'array'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,model'],
            'history.*.content' => ['required_with:history', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $history = $validated['history'] ?? [];

        // Limit conversation history to last 6 turns
        if (count($history) > 6) {
            $history = array_slice($history, -6);
        }

        $result = $this->aiService->chat($user, $validated['message'], $history);

        return response()->json([
            'message' => 'AI Copilot response generated.',
            'data' => [
                'answer' => $result['answer'],
                'suggestions' => $result['suggestions'],
            ],
        ]);
    }

    /**
     * Natural Language AI Quick-Scheduler ("Prompt-to-Meeting").
     */
    public function quickSchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:1000'],
            'auto_create' => ['nullable', 'boolean'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $autoCreate = (bool)($validated['auto_create'] ?? false);

        $result = $this->aiService->quickSchedule($user, $validated['prompt'], $autoCreate);

        $statusCode = $result['created'] ? 201 : 200;

        return response()->json([
            'message' => $result['summary'],
            'data' => $result,
        ], $statusCode);
    }

    /**
     * Smart AI Daily Executive Briefing ("Morning Digest").
     */
    public function briefing(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $targetDate = !empty($validated['date']) ? Carbon::parse($validated['date']) : null;

        $briefing = $this->aiService->getDailyBriefing($user, $targetDate);

        return response()->json([
            'message' => 'Daily executive briefing generated.',
            'data' => $briefing,
        ]);
    }

    /**
     * AI Meeting Notes, Summary & Auto-Task Extractor (Granola / Otter style).
     */
    public function summarizeMeeting(Request $request, int $meetingId): JsonResponse
    {
        $validated = $request->validate([
            'raw_notes' => ['nullable', 'string', 'max:10000'],
            'auto_create_tasks' => ['nullable', 'boolean'],
            'save_notes' => ['nullable', 'boolean'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $meeting = Meeting::withoutGlobalScopes()->find($meetingId);
        if (!$meeting) {
            return response()->json([
                'message' => 'Meeting not found.',
            ], 404);
        }

        // Tenancy check
        if ($user->company_id && $meeting->company_id !== $user->company_id && !$user->isDeveloper()) {
            return response()->json([
                'message' => 'Unauthorized access to this meeting.',
            ], 403);
        }

        $rawNotes = $validated['raw_notes'] ?? null;
        $autoCreateTasks = (bool)($validated['auto_create_tasks'] ?? false);
        $saveNotes = (bool)($validated['save_notes'] ?? true);

        $result = $this->aiService->summarizeMeetingNotes($meeting, $rawNotes, $autoCreateTasks, $saveNotes, $user);

        return response()->json([
            'message' => 'Meeting notes summarized successfully.',
            'data' => $result,
        ]);
    }

    /**
     * AI Client Deal Intelligence (CRM / BD Copilot).
     */
    public function dealInsights(Request $request, int $clientId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $client = Client::withoutGlobalScopes()->find($clientId);
        if (!$client) {
            return response()->json([
                'message' => 'Client not found.',
            ], 404);
        }

        // Tenancy check
        if ($user->company_id && $client->company_id !== $user->company_id && !$user->isDeveloper()) {
            return response()->json([
                'message' => 'Unauthorized access to this client record.',
            ], 403);
        }

        $insights = $this->aiService->getClientDealInsights($client, $user);

        return response()->json([
            'message' => 'Deal intelligence insights generated.',
            'data' => $insights,
        ]);
    }
}
