<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * List events (holidays, trainings, milestones, etc.).
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $category = $request->query('category');
        $departmentId = $request->query('department_id');
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 25);

        $query = Event::with(['user', 'department'])
            ->orderBy('start_time', 'asc');

        if (! $user->isSuperAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('is_company_wide', true)
                  ->orWhere('user_id', $user->id)
                  ->orWhere('department_id', $user->department_id);
            });
        }

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('end_time', '>=', $request->query('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('start_time', '<=', $request->query('end_date'));
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => collect($paginated->items())->map(fn($e) => $this->formatEvent($e)),
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
     * Show single event details.
     */
    public function show(Request $request, Event $event): JsonResponse
    {
        $this->authorizeEventView($event, $request->user());
        $event->loadMissing(['user', 'department']);

        return response()->json([
            'data' => $this->formatEvent($event),
        ]);
    }

    /**
     * Create new company or department event.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'in:company_holiday,deadline,training,milestone,out_of_office,general'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after_or_equal:start_time'],
            'is_all_day' => ['boolean'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_company_wide' => ['boolean'],
        ]);

        $categoryColors = [
            'company_holiday' => '#ec4899',
            'training' => '#8b5cf6',
            'milestone' => '#f59e0b',
            'deadline' => '#ef4444',
            'out_of_office' => '#64748b',
            'general' => '#3b82f6',
        ];

        $isCompanyWide = $user->isSuperAdmin() ? $request->boolean('is_company_wide') : false;

        $event = Event::create([
            'company_id' => $user->company_id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'user_id' => $user->id,
            'department_id' => $validated['department_id'] ?? null,
            'category' => $validated['category'],
            'start_time' => Carbon::parse($validated['start_time']),
            'end_time' => Carbon::parse($validated['end_time']),
            'is_all_day' => $request->boolean('is_all_day'),
            'color' => !empty($validated['color']) ? $validated['color'] : ($categoryColors[$validated['category']] ?? '#3b82f6'),
            'location' => $validated['location'] ?? null,
            'is_company_wide' => $isCompanyWide,
        ]);

        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => 'event_created',
            'auditable_type' => Event::class,
            'auditable_id' => $event->id,
            'description' => "Event '{$event->title}' ({$event->category}) created via Mobile API by {$user->name}.",
            'new_values' => $event->toArray(),
        ]);

        $event->load(['user', 'department']);

        return response()->json([
            'message' => 'Event created successfully.',
            'data' => $this->formatEvent($event),
        ], 201);
    }

    /**
     * Update an event.
     */
    public function update(Request $request, Event $event): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (!$user->isSuperAdmin() && $event->user_id !== $user->id) {
            abort(403, 'Unauthorized to update this event.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'in:company_holiday,deadline,training,milestone,out_of_office,general'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after_or_equal:start_time'],
            'is_all_day' => ['boolean'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_company_wide' => ['boolean'],
        ]);

        $isCompanyWide = $user->isSuperAdmin() ? $request->boolean('is_company_wide') : $event->is_company_wide;
        $oldValues = $event->toArray();

        $event->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'category' => $validated['category'],
            'start_time' => Carbon::parse($validated['start_time']),
            'end_time' => Carbon::parse($validated['end_time']),
            'is_all_day' => $request->boolean('is_all_day'),
            'color' => !empty($validated['color']) ? $validated['color'] : $event->color,
            'location' => $validated['location'] ?? null,
            'is_company_wide' => $isCompanyWide,
        ]);

        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => 'event_updated',
            'auditable_type' => Event::class,
            'auditable_id' => $event->id,
            'description' => "Event '{$event->title}' updated via Mobile API by {$user->name}.",
            'old_values' => $oldValues,
            'new_values' => $event->fresh()->toArray(),
        ]);

        return response()->json([
            'message' => 'Event updated successfully.',
            'data' => $this->formatEvent($event->fresh(['user', 'department'])),
        ]);
    }

    /**
     * Delete an event.
     */
    public function destroy(Request $request, Event $event): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (!$user->isSuperAdmin() && $event->user_id !== $user->id) {
            abort(403, 'Unauthorized to delete this event.');
        }

        $title = $event->title;
        $event->delete();

        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => 'event_deleted',
            'auditable_type' => Event::class,
            'auditable_id' => $event->id,
            'description' => "Event '{$title}' removed via Mobile API by {$user->name}.",
        ]);

        return response()->json([
            'message' => 'Event removed successfully.',
        ]);
    }

    protected function formatEvent(Event $e): array
    {
        return [
            'id' => $e->id,
            'title' => $e->title,
            'description' => $e->description,
            'category' => $e->category,
            'start_time' => $e->start_time->toIso8601String(),
            'end_time' => $e->end_time->toIso8601String(),
            'is_all_day' => (bool) $e->is_all_day,
            'is_company_wide' => (bool) $e->is_company_wide,
            'color' => $e->color,
            'location' => $e->location,
            'department' => $e->department ? [
                'id' => $e->department->id,
                'name' => $e->department->name,
                'code' => $e->department->code,
            ] : null,
            'user' => $e->user ? [
                'id' => $e->user->id,
                'name' => $e->user->name,
                'email' => $e->user->email,
            ] : null,
            'created_at' => $e->created_at?->toIso8601String(),
        ];
    }

    protected function authorizeEventView(Event $event, User $user): void
    {
        if ($user->isSuperAdmin() || $event->is_company_wide) {
            return;
        }

        if ($event->user_id === $user->id) {
            return;
        }

        if ($event->department_id && $event->department_id === $user->department_id) {
            return;
        }

        abort(403, 'Unauthorized access to event.');
    }
}
