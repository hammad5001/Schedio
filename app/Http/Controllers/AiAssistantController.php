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
}
