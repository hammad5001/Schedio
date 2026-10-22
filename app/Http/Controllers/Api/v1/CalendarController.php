<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\User;
use App\Services\ConflictDetectionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function __construct(
        protected ConflictDetectionService $conflictService
    ) {}

    /**
     * Unified calendar feed combining meetings and company events.
     */
    public function feed(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'start' => ['required', 'string'],
            'end' => ['required', 'string'],
            'type' => ['nullable', 'string', 'in:all,meetings,events'],
        ]);

        $start = Carbon::parse($request->input('start'));
        $end = Carbon::parse($request->input('end'));
        $type = $request->input('type', 'all');

        $feed = [];

        // 1. Meetings
        if ($type === 'all' || $type === 'meetings') {
            $meetingQuery = Meeting::with(['organizer', 'room', 'department', 'participants'])
                ->where('status', '!=', 'cancelled')
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_time', [$start, $end])
                      ->orWhereBetween('end_time', [$start, $end])
                      ->orWhere(function ($sub) use ($start, $end) {
                          $sub->where('start_time', '<=', $start)
                              ->where('end_time', '>=', $end);
                      });
                });

            if (! $user->isSuperAdmin()) {
                $meetingQuery->where(function ($q) use ($user) {
                    $q->where('organizer_id', $user->id)
                      ->orWhereHas('participants', fn($p) => $p->where('users.id', $user->id));
                });
            }

            foreach ($meetingQuery->get() as $meeting) {
                $feed[] = [
                    'id' => $meeting->id,
                    'entity_type' => 'meeting',
                    'title' => $meeting->title,
                    'start_time' => $meeting->start_time?->toIso8601String(),
                    'end_time' => $meeting->end_time?->toIso8601String(),
                    'status' => $meeting->status,
                    'type' => $meeting->type,
                    'location' => $meeting->location,
                    'color' => $meeting->room?->color ?? '#6366f1',
                    'organizer' => $meeting->organizer?->name,
                    'room' => $meeting->room?->name,
                ];
            }
        }

        // 2. Company Events
        if ($type === 'all' || $type === 'events') {
            $eventQuery = Event::where(function ($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start, $end])
                  ->orWhereBetween('end_time', [$start, $end])
                  ->orWhere(function ($sub) use ($start, $end) {
                      $sub->where('start_time', '<=', $start)
                          ->where('end_time', '>=', $end);
                  });
            });

            if (! $user->isSuperAdmin()) {
                $eventQuery->where(function ($q) use ($user) {
                    $q->where('is_company_wide', true)
                      ->orWhere('user_id', $user->id)
                      ->orWhere('department_id', $user->department_id);
                });
            }

            foreach ($eventQuery->get() as $event) {
                $feed[] = [
                    'id' => $event->id,
                    'entity_type' => 'event',
                    'title' => $event->title,
                    'start_time' => $event->start_time?->toIso8601String(),
                    'end_time' => $event->end_time?->toIso8601String(),
                    'status' => 'confirmed',
                    'category' => $event->category,
                    'location' => $event->location,
                    'color' => $event->color ?: '#10b981',
                    'is_all_day' => (bool) $event->is_all_day,
                ];
            }
        }

        // Sort by start_time
        usort($feed, fn($a, $b) => strcmp($a['start_time'], $b['start_time']));

        return response()->json([
            'data' => $feed,
            'count' => count($feed),
        ]);
    }

    /**
     * Check real-time conflicts for selected room & participants.
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => ['exists:users,id'],
            'exclude_meeting_id' => ['nullable', 'exists:meetings,id'],
        ]);

        $start = Carbon::parse($validated['start_time']);
        $end = Carbon::parse($validated['end_time']);
        $roomId = !empty($validated['room_id']) ? (int) $validated['room_id'] : null;
        $participantIds = $validated['participant_ids'] ?? [];
        $excludeMeetingId = !empty($validated['exclude_meeting_id']) ? (int) $validated['exclude_meeting_id'] : null;

        $result = $this->conflictService->checkAvailability(
            $start,
            $end,
            $roomId,
            $participantIds,
            $excludeMeetingId
        );

        return response()->json($result);
    }

    /**
     * Suggest available alternative slots.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:480'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => ['exists:users,id'],
            'exclude_meeting_id' => ['nullable', 'exists:meetings,id'],
        ]);

        $date = Carbon::parse($validated['date']);
        $duration = (int) ($validated['duration_minutes'] ?? 60);
        $roomId = !empty($validated['room_id']) ? (int) $validated['room_id'] : null;
        $participantIds = $validated['participant_ids'] ?? [];
        $excludeMeetingId = !empty($validated['exclude_meeting_id']) ? (int) $validated['exclude_meeting_id'] : null;

        $suggestions = $this->conflictService->suggestAvailableSlots(
            $date,
            $duration,
            $roomId,
            $participantIds,
            $excludeMeetingId
        );

        return response()->json([
            'slots' => $suggestions,
            'count' => count($suggestions),
        ]);
    }
}
