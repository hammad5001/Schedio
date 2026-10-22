<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeviceController extends Controller
{
    /**
     * Register or refresh a push notification device token.
     * Response: 201 on create, 200 on update.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['required', 'string', 'in:android,ios'],
        ]);

        $existing = DeviceToken::where('token', $validated['token'])->first();
        $statusCode = $existing ? 200 : 201;

        $deviceToken = DeviceToken::updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $validated['platform'],
                'last_seen_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Device registered successfully.',
            'data' => [
                'id' => $deviceToken->id,
                'token' => $deviceToken->token,
                'platform' => $deviceToken->platform,
                'last_seen_at' => $deviceToken->last_seen_at?->toIso8601String(),
            ],
        ], $statusCode);
    }

    /**
     * Unregister device token upon sign-out.
     * Response: 204 No Content.
     */
    public function destroy(Request $request, string $token): Response
    {
        DeviceToken::where('token', $token)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->noContent();
    }
}
