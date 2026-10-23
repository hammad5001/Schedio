<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Meeting;
use App\Models\MeetingNote;
use App\Models\MeetingParticipant;
use App\Models\Reminder;
use App\Models\User;
use App\Services\ConflictDetectionService;
use App\Services\NotificationDispatchService;
use App\Services\ReminderEngineService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class MeetingController extends Controller
{
    public function __construct(
        protected ConflictDetectionService $conflictService,
        protected NotificationDispatchService $notificationService,
        protected ReminderEngineService $reminderService
    ) {}

    /**
     * Get paginated list of meetings.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $status = $request->query('status', 'all');
        $scope = $request->query('scope', 'all');
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 15);

        $query = Meeting::with(['organizer', 'department', 'room', 'participants', 'client'])
            ->latest('start_time');

        // Isolation: non-super-admins only see meetings they organize or participate in
        if (! $user->isSuperAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('organizer_id', $user->id)
                  ->orWhereHas('participants', fn($p) => $p->where('users.id', $user->id));
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($scope === 'internal') {
            $query->where(function ($q) {
                $q->whereNull('metadata->is_client_meeting')
                  ->orWhere('metadata->is_client_meeting', false);
            });
        } elseif ($scope === 'external') {
            $query->where('metadata->is_client_meeting', true);
        } elseif ($scope === 'organized_by_me') {
            $query->where('organizer_id', $user->id);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => collect($paginated->items())->map(fn($m) => $this->formatMeeting($m, $user)),
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
     * Show detailed meeting information.
     */
    public function show(Request $request, Meeting $meeting): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorizeAccess($meeting, $user);

        $meeting->loadMissing(['organizer', 'department', 'room', 'participants', 'tasks', 'reminders', 'client', 'notes.user']);

        return response()->json([
            'data' => $this->formatMeeting($meeting, $user, true),
        ]);
    }

    /**
     * Create a new meeting with conflict detection.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'timezone' => ['required', 'string', 'max:50'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'type' => ['required', 'string', 'in:in_person,virtual,hybrid'],
            'meeting_link' => ['nullable', 'url', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_private' => ['boolean'],
            'is_client_meeting' => ['boolean'],
            'client_name' => ['nullable', 'string', 'max:150'],
            'client_email' => ['nullable', 'email', 'max:150'],
            'client_company' => ['nullable', 'string', 'max:150'],
            'recurrence_frequency' => ['nullable', 'string', 'in:none,daily,weekly,biweekly,monthly'],
            'reminder_minutes' => ['nullable', 'integer', 'in:0,5,10,15,30,60,1440'],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => ['exists:users,id'],
        ]);

        $startTime = Carbon::parse($validated['start_time']);
        $endTime = Carbon::parse($validated['end_time']);
        $roomId = !empty($validated['room_id']) ? (int) $validated['room_id'] : null;
        $participantIds = array_values(array_unique(array_merge(
            $validated['participant_ids'] ?? [],
            [$user->id]
        )));

        // Conflict check
        $conflicts = $this->conflictService->checkAvailability($startTime, $endTime, $roomId, $participantIds);
        if ($conflicts['has_conflicts']) {
            return response()->json([
                'message' => 'Scheduling conflict detected for the selected time slot.',
                'errors' => ['time' => ['Scheduling conflict detected for the selected time slot.']],
                'conflicts' => $conflicts,
            ], 422);
        }

        $meeting = DB::transaction(function () use ($validated, $user, $startTime, $endTime, $roomId, $participantIds) {
            $metadata = (object)[];
            if (!empty($validated['is_client_meeting'])) {
                $metadata = (object)[
                    'is_client_meeting' => true,
                    'client_name' => $validated['client_name'] ?? null,
                    'client_email' => $validated['client_email'] ?? null,
                    'client_company' => $validated['client_company'] ?? null,
                ];
            }

            $recurrence = null;
            if (!empty($validated['recurrence_frequency']) && $validated['recurrence_frequency'] !== 'none') {
                $recurrence = [
                    'frequency' => $validated['recurrence_frequency'],
                    'interval' => 1,
                ];
            }

            $meeting = Meeting::create([
                'company_id' => $user->company_id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'organizer_id' => $user->id,
                'department_id' => $validated['department_id'] ?? $user->department_id,
                'client_id' => $validated['client_id'] ?? null,
                'room_id' => $roomId,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'timezone' => $validated['timezone'] ?? 'UTC',
                'status' => 'scheduled',
                'type' => $validated['type'],
                'meeting_link' => $validated['meeting_link'] ?? null,
                'location' => $validated['location'] ?? null,
                'is_private' => $validated['is_private'] ?? false,
                'recurrence' => $recurrence,
                'metadata' => $metadata,
            ]);

            // Add Host
            MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'status' => 'accepted',
                'role' => 'host',
                'responded_at' => now(),
            ]);

            // Add other participants
            foreach ($participantIds as $pId) {
                if ($pId !== $user->id) {
                    MeetingParticipant::create([
                        'meeting_id' => $meeting->id,
                        'user_id' => $pId,
                        'status' => 'pending',
                        'role' => 'attendee',
                    ]);
                }
            }

            // Schedule Reminders
            $reminderMinutes = $validated['reminder_minutes'] ?? 15;
            if ($reminderMinutes > 0) {
                $this->reminderService->scheduleMeetingReminders($meeting, (int) $reminderMinutes);
            }

            // Dispatch Notifications
            $this->notificationService->notifyMeetingInvitation($meeting);

            AuditLog::create([
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'action' => 'meeting_created',
                'auditable_type' => Meeting::class,
                'auditable_id' => $meeting->id,
                'description' => "Meeting '{$meeting->title}' created via Mobile API by {$user->name}.",
                'ip_address' => request()->ip(),
            ]);

            return $meeting;
        });

        $meeting->load(['organizer', 'department', 'room', 'participants', 'client']);

        return response()->json([
            'message' => 'Meeting scheduled successfully.',
            'data' => $this->formatMeeting($meeting, $user, true),
        ], 201);
    }

    /**
     * Update an existing meeting.
     */
    public function update(Request $request, Meeting $meeting): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorizeManage($meeting, $user);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'timezone' => ['required', 'string', 'max:50'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'type' => ['required', 'string', 'in:in_person,virtual,hybrid'],
            'meeting_link' => ['nullable', 'url', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_private' => ['boolean'],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => ['exists:users,id'],
        ]);

        $startTime = Carbon::parse($validated['start_time']);
        $endTime = Carbon::parse($validated['end_time']);
        $roomId = !empty($validated['room_id']) ? (int) $validated['room_id'] : null;
        $participantIds = array_values(array_unique(array_merge(
            $validated['participant_ids'] ?? [],
            [$meeting->organizer_id]
        )));

        // Conflict check excluding current meeting
        $conflicts = $this->conflictService->checkAvailability($startTime, $endTime, $roomId, $participantIds, $meeting->id);
        if ($conflicts['has_conflicts']) {
            return response()->json([
                'message' => 'Scheduling conflict detected for the updated time slot.',
                'errors' => ['time' => ['Scheduling conflict detected for the updated time slot.']],
                'conflicts' => $conflicts,
            ], 422);
        }

        $meeting->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'department_id' => $validated['department_id'] ?? $meeting->department_id,
            'room_id' => $roomId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'timezone' => $validated['timezone'] ?? $meeting->timezone,
            'type' => $validated['type'],
            'meeting_link' => $validated['meeting_link'] ?? null,
            'location' => $validated['location'] ?? null,
            'is_private' => $validated['is_private'] ?? false,
        ]);

        // Sync participants preserving host
        if (isset($validated['participant_ids'])) {
            $existingParticipants = $meeting->participants->pluck('id')->toArray();
            foreach ($participantIds as $pId) {
                if (!in_array($pId, $existingParticipants, true)) {
                    MeetingParticipant::create([
                        'meeting_id' => $meeting->id,
                        'user_id' => $pId,
                        'status' => 'pending',
                        'role' => 'attendee',
                    ]);
                }
            }
        }

        $this->reminderService->updateRemindersForMeeting($meeting);
        $this->notificationService->notifyMeetingUpdated($meeting);

        $meeting->load(['organizer', 'department', 'room', 'participants', 'client']);

        return response()->json([
            'message' => 'Meeting updated successfully.',
            'data' => $this->formatMeeting($meeting, $user, true),
        ], 200);
    }

    /**
     * Cancel a meeting.
     */
    public function cancel(Request $request, Meeting $meeting): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorizeManage($meeting, $user);

        $meeting->update(['status' => 'cancelled']);
        $this->reminderService->cancelRemindersForMeeting($meeting);
        $this->notificationService->notifyMeetingCancelled($meeting, $user->name);

        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => 'meeting_cancelled',
            'auditable_type' => Meeting::class,
            'auditable_id' => $meeting->id,
            'description' => "Meeting '{$meeting->title}' cancelled via Mobile API by {$user->name}.",
            'ip_address' => request()->ip(),
        ]);

        $meeting->loadMissing(['organizer', 'department', 'room', 'participants', 'client']);

        return response()->json([
            'message' => 'Meeting has been cancelled.',
            'data' => $this->formatMeeting($meeting, $user),
        ], 200);
    }

    /**
     * Delete a meeting.
     */
    public function destroy(Request $request, Meeting $meeting): Response
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorizeManage($meeting, $user);

        $meetingTitle = $meeting->title;
        $meetingId = $meeting->id;
        $companyId = $user->company_id;

        $this->reminderService->cancelRemindersForMeeting($meeting);
        $this->notificationService->notifyMeetingCancelled($meeting, $user->name);
        $meeting->delete();

        AuditLog::create([
            'company_id' => $companyId,
            'user_id' => $user->id,
            'action' => 'meeting_deleted',
            'auditable_type' => Meeting::class,
            'auditable_id' => $meetingId,
            'description' => "Meeting '{$meetingTitle}' deleted via Mobile API by {$user->name}.",
            'ip_address' => request()->ip(),
        ]);

        return response()->noContent();
    }

    /**
     * Submit RSVP response.
     */
    public function rsvp(Request $request, Meeting $meeting): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:accepted,declined,tentative'],
            'response_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $participant = MeetingParticipant::firstOrCreate(
            ['meeting_id' => $meeting->id, 'user_id' => $user->id],
            ['role' => 'attendee']
        );

        $participant->update([
            'status' => $validated['status'],
            'response_notes' => $validated['response_notes'] ?? null,
            'responded_at' => now(),
        ]);

        $this->notificationService->notifyParticipantResponse($meeting, $user, $validated['status'], $validated['response_notes'] ?? null);

        return response()->json([
            'message' => 'RSVP submitted successfully.',
            'status' => $validated['status'],
        ], 200);
    }

    /**
     * List meeting notes.
     */
    public function notes(Request $request, Meeting $meeting): JsonResponse
    {
        $this->authorizeAccess($meeting, $request->user());

        $notes = $meeting->notes()->with('author')->latest()->get();

        return response()->json([
            'data' => $notes->map(fn($n) => [
                'id' => $n->id,
                'meeting_id' => $n->meeting_id,
                'user_id' => $n->user_id,
                'agenda' => $n->agenda,
                'discussion' => $n->discussion,
                'decisions' => $n->decisions,
                'action_items' => $n->action_items,
                'follow_up_date' => $n->follow_up_date?->toIso8601String(),
                'attachments' => $n->attachments ?: [],
                'author' => $n->author ? [
                    'id' => $n->author->id,
                    'name' => $n->author->name,
                    'email' => $n->author->email,
                ] : null,
                'created_at' => $n->created_at?->toIso8601String(),
                'updated_at' => $n->updated_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Create a meeting note.
     */
    public function storeNote(Request $request, Meeting $meeting): JsonResponse
    {
        $this->authorizeAccess($meeting, $request->user());

        $validated = $request->validate([
            'agenda' => ['nullable', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
            'discussion' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'decisions' => ['nullable', 'string'],
            'action_items' => ['nullable'],
            'follow_up_date' => ['nullable', 'date'],
            'attachments' => ['nullable', 'array'],
        ]);

        $agenda = trim((string) ($validated['agenda'] ?? ($validated['title'] ?? '')));
        $discussion = trim((string) ($validated['discussion'] ?? ($validated['content'] ?? '')));
        $decisions = trim((string) ($validated['decisions'] ?? ''));
        $rawActionItems = $validated['action_items'] ?? null;
        $actionItems = is_array($rawActionItems)
            ? trim(implode("\n", array_filter(array_map('trim', $rawActionItems))))
            : trim((string) $rawActionItems);

        if ($agenda === '' && $discussion === '' && $decisions === '' && $actionItems === '') {
            return response()->json([
                'message' => 'A meeting note cannot be empty. Please provide an agenda, discussion, decision, or action items.',
                'errors' => [
                    'content' => ['A meeting note cannot be empty. Please provide an agenda, discussion, decision, or action items.'],
                ],
            ], 422);
        }

        $agenda = $agenda !== '' ? $agenda : null;
        $discussion = $discussion !== '' ? $discussion : null;
        $decisions = $decisions !== '' ? $decisions : null;
        $actionItems = $actionItems !== '' ? $actionItems : null;

        $note = MeetingNote::create([
            'company_id' => $request->user()->company_id,
            'meeting_id' => $meeting->id,
            'user_id' => $request->user()->id,
            'agenda' => $agenda,
            'discussion' => $discussion,
            'decisions' => $validated['decisions'] ?? null,
            'action_items' => $actionItems,
            'follow_up_date' => !empty($validated['follow_up_date']) ? Carbon::parse($validated['follow_up_date']) : null,
            'attachments' => $validated['attachments'] ?? null,
        ]);

        return response()->json([
            'message' => 'Meeting note saved.',
            'data' => [
                'id' => $note->id,
                'meeting_id' => $note->meeting_id,
                'user_id' => $note->user_id,
                'agenda' => $note->agenda,
                'discussion' => $note->discussion,
                'decisions' => $note->decisions,
                'action_items' => $note->action_items,
                'follow_up_date' => $note->follow_up_date?->toIso8601String(),
                'attachments' => $note->attachments ?: [],
                'created_at' => $note->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Format Meeting model matching Flutter mobile schema contract.
     */
    protected function formatMeeting(Meeting $m, User $currentUser, bool $detailed = false): array
    {
        $isOrganizer = ($m->organizer_id === $currentUser->id);
        $canManage = $currentUser->isSuperAdmin() || $isOrganizer;
        $isParticipant = $m->participants->contains('id', $currentUser->id);

        $payload = [
            'id' => $m->id,
            'title' => $m->title,
            'description' => $m->description,
            'start_time' => $m->start_time?->toIso8601String(),
            'end_time' => $m->end_time?->toIso8601String(),
            'timezone' => $m->timezone,
            'status' => $m->status,
            'type' => $m->type,
            'location' => $m->location,
            'meeting_link' => $m->meeting_link,
            'is_private' => (bool) $m->is_private,
            'can_manage' => $canManage,
            'can_rsvp' => $isParticipant,
            'organizer' => $m->organizer ? [
                'id' => $m->organizer->id,
                'name' => $m->organizer->name,
                'email' => $m->organizer->email,
            ] : null,
            'department' => $m->department ? [
                'id' => $m->department->id,
                'name' => $m->department->name,
            ] : null,
            'room' => $m->room ? [
                'id' => $m->room->id,
                'name' => $m->room->name,
                'location' => $m->room->location,
            ] : null,
            'participants' => $m->participants->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'email' => $p->email,
                'role' => $p->pivot->role ?? 'attendee',
                'status' => $p->pivot->status ?? 'pending',
                'response_notes' => $p->pivot->response_notes ?? null,
            ]),
            'metadata' => (object) ($m->metadata ?: []),
        ];

        if ($detailed) {
            $payload['tasks'] = $m->tasks?->map(fn($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'status' => $t->status,
                'priority' => $t->priority,
                'due_date' => $t->due_date?->toIso8601String(),
            ]) ?: [];

            $payload['client'] = $m->client ? [
                'id' => $m->client->id,
                'company_name' => $m->client->company_name,
                'contact_name' => $m->client->contact_name,
            ] : null;
        }

        return $payload;
    }

    protected function authorizeAccess(Meeting $meeting, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $allowed = ($meeting->organizer_id === $user->id) || $meeting->participants->contains('id', $user->id);
        if (! $allowed) {
            abort(403, 'Unauthorized access to meeting details.');
        }
    }

    protected function authorizeManage(Meeting $meeting, User $user): void
    {
        if ($user->isSuperAdmin() || $meeting->organizer_id === $user->id) {
            return;
        }

        abort(403, 'Unauthorized. Only the organizer or company super admin can modify this meeting.');
    }
}
