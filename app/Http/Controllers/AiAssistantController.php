<?php

namespace App\Http\Controllers;

use App\Services\Ai\SchedioAiAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiAssistantController extends Controller
{
    /**
     * Send a prompt to the Schedio AI Copilot.
     */
    public function chat(Request $request, SchedioAiAgentService $aiService): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $history = session()->get('ai_chat_history', []);

        // Limit conversation history to the last 6 turns
        if (count($history) > 6) {
            $history = array_slice($history, -6);
        }

        $result = $aiService->chat($user, $validated['message'], $history);

        // Append to session history
        $history[] = ['role' => 'user', 'content' => $validated['message']];
        $history[] = ['role' => 'model', 'content' => $result['answer']];
        session()->put('ai_chat_history', $history);

        return response()->json([
            'status' => 'success',
            'answer' => $result['answer'],
            'suggestions' => $result['suggestions'],
        ]);
    }

    /**
     * Clear the current session conversation history.
     */
    public function clear(): JsonResponse
    {
        session()->forget('ai_chat_history');

        return response()->json([
            'status' => 'success',
            'message' => 'Chat history cleared successfully.',
        ]);
    }

    /**
     * Natural Language AI Quick-Scheduler for Web.
     */
    public function quickSchedule(Request $request, SchedioAiAgentService $aiService): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:1000'],
            'auto_create' => ['nullable', 'boolean'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $autoCreate = (bool)($validated['auto_create'] ?? false);

        $result = $aiService->quickSchedule($user, $validated['prompt'], $autoCreate);

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ], $result['created'] ? 201 : 200);
    }

    /**
     * Daily Executive Briefing for Web Dashboard.
     */
    public function briefing(Request $request, SchedioAiAgentService $aiService): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $date = $request->filled('date') ? \Carbon\Carbon::parse($request->query('date')) : null;

        $briefing = $aiService->getDailyBriefing($user, $date);

        return response()->json([
            'status' => 'success',
            'data' => $briefing,
        ]);
    }

    /**
     * Summarize meeting notes and extract tasks for Web.
     */
    public function summarizeMeeting(Request $request, int $meetingId, SchedioAiAgentService $aiService): JsonResponse
    {
        $validated = $request->validate([
            'raw_notes' => ['nullable', 'string', 'max:10000'],
            'auto_create_tasks' => ['nullable', 'boolean'],
            'save_notes' => ['nullable', 'boolean'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $meeting = \App\Models\Meeting::findOrFail($meetingId);

        if ($user->company_id && $meeting->company_id !== $user->company_id && !$user->isDeveloper()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized access to meeting.'], 403);
        }

        $result = $aiService->summarizeMeetingNotes(
            $meeting,
            $validated['raw_notes'] ?? null,
            (bool)($validated['auto_create_tasks'] ?? false),
            (bool)($validated['save_notes'] ?? true),
            $user
        );

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    /**
     * CRM Deal Intelligence & Follow-Up generator for Web.
     */
    public function dealInsights(int $clientId, SchedioAiAgentService $aiService): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $client = \App\Models\Client::findOrFail($clientId);

        if ($user->company_id && $client->company_id !== $user->company_id && !$user->isDeveloper()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized access to client.'], 403);
        }

        $insights = $aiService->getClientDealInsights($client, $user);

        return response()->json([
            'status' => 'success',
            'data' => $insights,
        ]);
    }
}
