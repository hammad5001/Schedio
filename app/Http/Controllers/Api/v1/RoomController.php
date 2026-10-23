<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoomController extends Controller
{
    /**
     * List meeting rooms.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $status = $request->query('status'); // active, inactive

        $query = Room::query()->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $perPage = (int) $request->query('per_page', 25);

        if ($request->query('paginate') === 'false' || $request->query('all') === 'true') {
            $rooms = $query->get();
            return response()->json([
                'data' => $rooms->map(fn($r) => $this->formatRoom($r)),
            ]);
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => collect($paginated->items())->map(fn($r) => $this->formatRoom($r)),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
            'links' => [
                'first' => $paginated->url(1),
                'last' => $paginated->url($paginated->lastPage()),
                'prev' => $paginated->previousPageUrl(),
                'next' => $paginated->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Show single room details and upcoming meetings.
     */
    public function show(Request $request, Room $room): JsonResponse
    {
        $room->load(['meetings' => function ($q) {
            $q->with('organizer')
              ->where('status', '!=', 'cancelled')
              ->where('start_time', '>=', now()->subHours(1))
              ->orderBy('start_time')
              ->take(10);
        }]);

        return response()->json([
            'data' => array_merge($this->formatRoom($room), [
                'upcoming_meetings' => $room->meetings->map(fn($m) => [
                    'id' => $m->id,
                    'title' => $m->title,
                    'start_time' => $m->start_time->toIso8601String(),
                    'end_time' => $m->end_time->toIso8601String(),
                    'status' => $m->status,
                    'organizer' => $m->organizer ? ['id' => $m->organizer->id, 'name' => $m->organizer->name] : null,
                ]),
            ]),
        ]);
    }

    /**
     * Store new room.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (!$user->isSuperAdmin() && !$user->isManager()) {
            abort(403, 'Unauthorized. Super Administrators or Managers only.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:50', 'unique:rooms,code'],
            'location' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'amenities' => ['nullable', 'array'],
            'amenities_text' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $code = !empty($validated['code'])
            ? Str::upper(Str::slug($validated['code']))
            : Str::upper('ROOM-' . Str::slug(substr($validated['name'], 0, 10)) . '-' . rand(100, 999));

        $amenities = $validated['amenities'] ?? [];
        if (!empty($validated['amenities_text'])) {
            $extra = array_map('trim', explode(',', $validated['amenities_text']));
            $amenities = array_values(array_unique(array_merge($amenities, array_filter($extra))));
        }

        $room = Room::create([
            'company_id' => $user->company_id,
            'name' => $validated['name'],
            'code' => $code,
            'location' => $validated['location'],
            'capacity' => $validated['capacity'],
            'amenities' => $amenities,
            'color' => $validated['color'] ?? '#6366f1',
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => 'room_created',
            'auditable_type' => Room::class,
            'auditable_id' => $room->id,
            'description' => "Room '{$room->name}' ({$room->code}) created via API by {$user->name}.",
            'new_values' => $room->toArray(),
        ]);

        return response()->json([
            'message' => 'Room created successfully.',
            'data' => $this->formatRoom($room),
        ], 201);
    }

    /**
     * Update room details.
     */
    public function update(Request $request, Room $room): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (!$user->isSuperAdmin() && !$user->isManager()) {
            abort(403, 'Unauthorized. Super Administrators or Managers only.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', 'unique:rooms,code,' . $room->id],
            'location' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
            'amenities' => ['nullable', 'array'],
            'amenities_text' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $amenities = $validated['amenities'] ?? [];
        if (!empty($validated['amenities_text'])) {
            $extra = array_map('trim', explode(',', $validated['amenities_text']));
            $amenities = array_values(array_unique(array_merge($amenities, array_filter($extra))));
        }

        $oldValues = $room->toArray();

        $room->update([
            'name' => $validated['name'],
            'code' => Str::upper($validated['code']),
            'location' => $validated['location'],
            'capacity' => $validated['capacity'],
            'amenities' => $amenities,
            'color' => $validated['color'] ?? $room->color,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $room->is_active,
        ]);

        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => 'room_updated',
            'auditable_type' => Room::class,
            'auditable_id' => $room->id,
            'description' => "Room '{$room->name}' updated via API by {$user->name}.",
            'old_values' => $oldValues,
            'new_values' => $room->fresh()->toArray(),
        ]);

        return response()->json([
            'message' => 'Room updated successfully.',
            'data' => $this->formatRoom($room->fresh()),
        ]);
    }

    /**
     * Delete room.
     */
    public function destroy(Request $request, Room $room): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (!$user->isSuperAdmin()) {
            abort(403, 'Unauthorized. Super Administrators only.');
        }

        $roomName = $room->name;
        $room->delete();

        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => 'room_deleted',
            'auditable_type' => Room::class,
            'auditable_id' => $room->id,
            'description' => "Room '{$roomName}' deleted via API by {$user->name}.",
        ]);

        return response()->json([
            'message' => 'Room deleted successfully.',
        ]);
    }

    protected function formatRoom(Room $r): array
    {
        return [
            'id' => $r->id,
            'name' => $r->name,
            'code' => $r->code,
            'location' => $r->location,
            'capacity' => $r->capacity,
            'color' => $r->color,
            'amenities' => $r->amenities ?: [],
            'is_active' => (bool) $r->is_active,
            'created_at' => $r->created_at?->toIso8601String(),
        ];
    }
}
