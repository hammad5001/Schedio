<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    /**
     * List user notifications with filtering and unread count.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $filter = $request->query('filter', 'all'); // all, unread, read
        $perPage = (int) $request->query('per_page', 20);

        $query = $user->notifications()->latest();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => collect($paginated->items())->map(fn($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'type' => $n->data['type'] ?? ($n->data['category'] ?? 'general'),
                'meeting_id' => $n->data['meeting_id'] ?? null,
                'task_id' => $n->data['task_id'] ?? null,
                'read_at' => $n->read_at?->toIso8601String(),
                'is_read' => $n->read_at !== null,
                'created_at' => $n->created_at?->toIso8601String(),
                'data' => $n->data,
            ]),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read.',
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a single notification as unread.
     */
    public function markAsUnread(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->update(['read_at' => null]);

        return response()->json([
            'message' => 'Notification marked as unread.',
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Delete a single notification.
     */
    public function destroy(Request $request, string $id): Response
    {
        /** @var User $user */
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->delete();

        return response()->noContent();
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read.',
            'unread_count' => 0,
        ]);
    }

    /**
     * Get user notification preferences.
     */
    public function getPreferences(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => $user->notification_preferences ?: [
                'channels' => ['in_app' => true, 'email' => true],
                'categories' => (object)[],
            ],
        ]);
    }

    /**
     * Save user notification preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'channels' => ['nullable', 'array'],
            'categories' => ['nullable', 'array'],
        ]);

        $current = $user->notification_preferences ?: [];
        $updated = array_merge($current, $validated);

        $user->update(['notification_preferences' => $updated]);

        return response()->json([
            'message' => 'Notification preferences updated.',
            'data' => $updated,
        ]);
    }

    /**
     * Dispatch controlled test push notification to user's registered devices.
     */
    public function testPush(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $title = $request->input('title', 'Schedio Test Push');
        $body = $request->input('body', 'Test push notification delivery verified on Android device.');

        /** @var \App\Services\FirebaseCloudMessagingService $fcm */
        $fcm = app(\App\Services\FirebaseCloudMessagingService::class);

        $result = $fcm->sendTestNotification($user->email);

        return response()->json([
            'message' => 'Test notification requested.',
            'configured' => $fcm->isConfigured(),
            'result' => $result,
        ]);
    }
}
