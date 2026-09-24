<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\Reminder;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MeetingController extends Controller
{
    /**
     * Display a paginated listing of meetings with search and filter controls.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $status = $request->query('status', 'all');
        $scope = $request->query('scope', 'all'); // all, internal, external, organized_by_me
        $search = $request->query('search');
        $departmentId = $request->query('department_id');
        $roomId = $request->query('room_id');

        $query = Meeting::with(['organizer', 'department', 'room', 'participants'])
            ->latest('start_time');

        // Permission & Visibility filtering
        if (!$user->isSuperAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('is_private', false)
                  ->orWhere('organizer_id', $user->id)
                  ->orWhereHas('participants', fn($p) => $p->where('users.id', $user->id));
            });
        }

        // Status Filter
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Scope Filter
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

        // Department Filter
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        // Room Filter
        if ($roomId) {
            $query->where('room_id', $roomId);
        }

        // Search Query
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $meetings = $query->paginate(10)->withQueryString();

        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $rooms = Room::where('is_active', true)->orderBy('name')->get();

        $counts = [
            'all' => Meeting::count(),
            'upcoming' => Meeting::where('start_time', '>=', now())->where('status', 'scheduled')->count(),
            'completed' => Meeting::where('status', 'completed')->count(),
            'cancelled' => Meeting::where('status', 'cancelled')->count(),
        ];

        return view('meetings.index', compact('meetings', 'departments', 'rooms', 'counts', 'status', 'scope', 'search'));
    }

    /**
     * Show the form for creating a new meeting.
     */
    public function create(): View
    {
        $user = Auth::user();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $rooms = Room::where('is_active', true)->orderBy('name')->get();
        $users = User::where('approval_status', User::STATUS_APPROVED)->where('is_active', true)->orderBy('name')->get();

        return view('meetings.create', compact('departments', 'rooms', 'users', 'user'));
    }

    /**
     * Store a newly created meeting.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

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
            'is_client_meeting' => ['boolean'],
            'client_name' => ['nullable', 'string', 'max:150'],
            'client_email' => ['nullable', 'email', 'max:150'],
            'client_company' => ['nullable', 'string', 'max:150'],
            'recurrence_frequency' => ['nullable', 'string', 'in:none,daily,weekly,biweekly,monthly'],
            'reminder_minutes' => ['nullable', 'integer', 'in:0,10,15,30,60,1440'],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => ['exists:users,id'],
        ]);

        $startTime = Carbon::parse($validated['start_time']);
        $endTime = Carbon::parse($validated['end_time']);

        // Check Room Conflict
        if (!empty($validated['room_id'])) {
            $conflict = Meeting::where('room_id', $validated['room_id'])
                ->where('status', '!=', 'cancelled')
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                })
                ->exists();

            if ($conflict) {
                return back()->withInput()->withErrors([
                    'room_id' => 'The selected room is already booked for this time slot. Please pick another room or adjust the time.',
                ]);
            }
        }

        $meeting = DB::transaction(function () use ($validated, $user, $startTime, $endTime) {
            $metadata = [];
            if (!empty($validated['is_client_meeting'])) {
                $metadata['is_client_meeting'] = true;
                $metadata['client_name'] = $validated['client_name'] ?? null;
                $metadata['client_email'] = $validated['client_email'] ?? null;
                $metadata['client_company'] = $validated['client_company'] ?? null;
            }

            $recurrence = null;
            if (!empty($validated['recurrence_frequency']) && $validated['recurrence_frequency'] !== 'none') {
                $recurrence = [
                    'frequency' => $validated['recurrence_frequency'],
                    'interval' => 1,
                ];
            }

            $meeting = Meeting::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'organizer_id' => $user->id,
                'department_id' => $validated['department_id'] ?? $user->department_id,
                'room_id' => $validated['room_id'] ?? null,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'timezone' => $validated['timezone'] ?? 'UTC',
                'status' => 'scheduled',
                'type' => $validated['type'],
                'meeting_link' => $validated['meeting_link'] ?? null,
                'location' => $validated['location'] ?? null,
                'is_private' => $validated['is_private'] ?? false,
                'recurrence' => $recurrence,
                'metadata' => $metadata ?: null,
            ]);

            // Add Host
            MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'role' => 'host',
                'status' => 'accepted',
                'responded_at' => now(),
            ]);

            // Add Internal Participants
            if (!empty($validated['participant_ids'])) {
                foreach ($validated['participant_ids'] as $participantId) {
                    if ($participantId != $user->id) {
                        MeetingParticipant::create([
                            'meeting_id' => $meeting->id,
                            'user_id' => $participantId,
                            'role' => 'attendee',
                            'status' => 'pending',
                        ]);
                    }
                }
            }

            // Create Scheduled Reminder if selected
            if (!empty($validated['reminder_minutes']) && $validated['reminder_minutes'] > 0) {
                $remindAt = $startTime->copy()->subMinutes((int) $validated['reminder_minutes']);
                Reminder::create([
                    'user_id' => $user->id,
                    'title' => "Upcoming Meeting: {$meeting->title}",
                    'message' => "Your meeting starts in {$validated['reminder_minutes']} minutes.",
                    'remindable_type' => Meeting::class,
                    'remindable_id' => $meeting->id,
                    'remind_at' => $remindAt,
                    'channel' => 'in_app',
                    'status' => 'pending',
                    'payload' => ['meeting_id' => $meeting->id],
                ]);
            }

            // Audit Trail
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'meeting_created',
                'auditable_type' => Meeting::class,
                'auditable_id' => $meeting->id,
                'description' => "Meeting '{$meeting->title}' created by {$user->name}.",
                'new_values' => $meeting->toArray(),
            ]);

            return $meeting;
        });

        return redirect()->route('meetings.show', $meeting)->with('success', 'Meeting scheduled successfully.');
    }

    /**
     * Display the specified meeting details.
     */
    public function show(Meeting $meeting): View
    {
        $user = Auth::user();

        // Permission check
        if (!$user->isSuperAdmin() && $meeting->is_private) {
            $isParticipant = $meeting->participants()->where('users.id', $user->id)->exists();
            if ($meeting->organizer_id !== $user->id && !$isParticipant) {
                abort(403, 'Unauthorized access to private meeting.');
            }
        }

        $meeting->load(['organizer', 'department', 'room', 'participants', 'tasks.assignee', 'reminders', 'auditLogs.user']);

        $canManage = $user->isSuperAdmin() || $meeting->organizer_id === $user->id;

        return view('meetings.show', compact('meeting', 'canManage', 'user'));
    }

    /**
     * Show the form for editing the specified meeting.
     */
    public function edit(Meeting $meeting): View
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin() && $meeting->organizer_id !== $user->id) {
            abort(403, 'Unauthorized to edit this meeting.');
        }

        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $rooms = Room::where('is_active', true)->orderBy('name')->get();
        $users = User::where('approval_status', User::STATUS_APPROVED)->where('is_active', true)->orderBy('name')->get();

        $selectedParticipants = $meeting->participants()->pluck('users.id')->toArray();

        return view('meetings.edit', compact('meeting', 'departments', 'rooms', 'users', 'selectedParticipants'));
    }

    /**
     * Update the specified meeting.
     */
    public function update(Request $request, Meeting $meeting): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin() && $meeting->organizer_id !== $user->id) {
            abort(403, 'Unauthorized to update this meeting.');
        }

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
            'is_client_meeting' => ['boolean'],
            'client_name' => ['nullable', 'string', 'max:150'],
            'client_email' => ['nullable', 'email', 'max:150'],
            'client_company' => ['nullable', 'string', 'max:150'],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => ['exists:users,id'],
        ]);

        $startTime = Carbon::parse($validated['start_time']);
        $endTime = Carbon::parse($validated['end_time']);

        // Check Room Conflict (excluding current meeting)
        if (!empty($validated['room_id'])) {
            $conflict = Meeting::where('room_id', $validated['room_id'])
                ->where('id', '!=', $meeting->id)
                ->where('status', '!=', 'cancelled')
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                })
                ->exists();

            if ($conflict) {
                return back()->withInput()->withErrors([
                    'room_id' => 'The selected room is already reserved for this time. Please select another room or adjust times.',
                ]);
            }
        }

        DB::transaction(function () use ($meeting, $validated, $user, $startTime, $endTime) {
            $metadata = $meeting->metadata ?? [];
            if (!empty($validated['is_client_meeting'])) {
                $metadata['is_client_meeting'] = true;
                $metadata['client_name'] = $validated['client_name'] ?? null;
                $metadata['client_email'] = $validated['client_email'] ?? null;
                $metadata['client_company'] = $validated['client_company'] ?? null;
            } else {
                unset($metadata['is_client_meeting'], $metadata['client_name'], $metadata['client_email'], $metadata['client_company']);
            }

            $oldValues = $meeting->toArray();

            $meeting->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'department_id' => $validated['department_id'] ?? null,
                'room_id' => $validated['room_id'] ?? null,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'timezone' => $validated['timezone'],
                'type' => $validated['type'],
                'meeting_link' => $validated['meeting_link'] ?? null,
                'location' => $validated['location'] ?? null,
                'is_private' => $validated['is_private'] ?? false,
                'metadata' => $metadata ?: null,
            ]);

            // Sync Participants while keeping organizer host record
            $participantsToSync = $validated['participant_ids'] ?? [];
            if (!in_array($meeting->organizer_id, $participantsToSync)) {
                $participantsToSync[] = $meeting->organizer_id;
            }

            $meeting->participants()->sync($participantsToSync);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'meeting_updated',
                'auditable_type' => Meeting::class,
                'auditable_id' => $meeting->id,
                'description' => "Meeting '{$meeting->title}' details updated by {$user->name}.",
                'old_values' => $oldValues,
                'new_values' => $meeting->fresh()->toArray(),
            ]);
        });

        return redirect()->route('meetings.show', $meeting)->with('success', 'Meeting updated successfully.');
    }

    /**
     * Cancel a meeting without hard deleting.
     */
    public function cancel(Request $request, Meeting $meeting): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin() && $meeting->organizer_id !== $user->id) {
            abort(403, 'Unauthorized to cancel this meeting.');
        }

        $meeting->update(['status' => 'cancelled']);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'meeting_cancelled',
            'auditable_type' => Meeting::class,
            'auditable_id' => $meeting->id,
            'description' => "Meeting '{$meeting->title}' was cancelled by {$user->name}.",
        ]);

        return redirect()->route('meetings.index')->with('success', 'Meeting has been marked as cancelled.');
    }

    /**
     * Remove the specified meeting from storage (soft delete).
     */
    public function destroy(Meeting $meeting): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->isSuperAdmin() && $meeting->organizer_id !== $user->id) {
            abort(403, 'Unauthorized to delete this meeting.');
        }

        $meeting->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'meeting_deleted',
            'auditable_type' => Meeting::class,
            'auditable_id' => $meeting->id,
            'description' => "Meeting '{$meeting->title}' was deleted by {$user->name}.",
        ]);

        return redirect()->route('meetings.index')->with('success', 'Meeting removed successfully.');
    }

    /**
     * RSVP status response from an invited participant.
     */
    public function rsvp(Request $request, Meeting $meeting): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:accepted,declined,tentative'],
            'response_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $participant = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            $participant->update([
                'status' => $validated['status'],
                'response_notes' => $validated['response_notes'] ?? null,
                'responded_at' => now(),
            ]);
        } else {
            MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'role' => 'attendee',
                'status' => $validated['status'],
                'response_notes' => $validated['response_notes'] ?? null,
                'responded_at' => now(),
            ]);
        }

        return back()->with('success', 'Your RSVP response has been recorded.');
    }
}
