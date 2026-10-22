<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MeetingNote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeetingNoteController extends Controller
{
    /**
     * Retrieve a specific meeting note.
     */
    public function show(Request $request, int|string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $note = MeetingNote::with(['author', 'meeting'])->findOrFail($id);

        $this->authorizeNoteAccess($note, $user);

        return response()->json([
            'data' => $this->formatNote($note),
        ]);
    }

    /**
     * Update an existing meeting note.
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $note = MeetingNote::with(['author', 'meeting'])->findOrFail($id);

        if (!$user->isSuperAdmin() && $note->user_id !== $user->id && $note->meeting?->organizer_id !== $user->id) {
            abort(403, 'Unauthorized to update this meeting note.');
        }

        $validated = $request->validate([
            'agenda' => ['nullable', 'string'],
            'discussion' => ['nullable', 'string'],
            'decisions' => ['nullable', 'string'],
            'action_items' => ['nullable', 'string'],
            'follow_up_date' => ['nullable', 'date'],
            'attachments' => ['nullable', 'array'],
            'attachments_text' => ['nullable', 'string'],
        ]);

        $attachments = $validated['attachments'] ?? [];
        if (!empty($validated['attachments_text'])) {
            $lines = preg_split('/[\r\n,]+/', $validated['attachments_text']);
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed) {
                    $attachments[] = [
                        'name' => filter_var($trimmed, FILTER_VALIDATE_URL) ? (parse_url($trimmed, PHP_URL_HOST) ?? 'External Link') : $trimmed,
                        'url' => filter_var($trimmed, FILTER_VALIDATE_URL) ? $trimmed : null,
                        'text' => $trimmed,
                    ];
                }
            }
        }

        $oldValues = $note->toArray();

        $note->update([
            'agenda' => array_key_exists('agenda', $validated) ? $validated['agenda'] : $note->agenda,
            'discussion' => array_key_exists('discussion', $validated) ? $validated['discussion'] : $note->discussion,
            'decisions' => array_key_exists('decisions', $validated) ? $validated['decisions'] : $note->decisions,
            'action_items' => array_key_exists('action_items', $validated) ? $validated['action_items'] : $note->action_items,
            'follow_up_date' => !empty($validated['follow_up_date']) ? Carbon::parse($validated['follow_up_date']) : $note->follow_up_date,
            'attachments' => !empty($attachments) ? $attachments : $note->attachments,
        ]);

        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => 'meeting_note_updated',
            'auditable_type' => MeetingNote::class,
            'auditable_id' => $note->id,
            'description' => "Meeting note #{$note->id} updated via Mobile API by {$user->name}.",
            'old_values' => $oldValues,
            'new_values' => $note->fresh()->toArray(),
        ]);

        return response()->json([
            'message' => 'Meeting note updated successfully.',
            'data' => $this->formatNote($note->fresh(['author', 'meeting'])),
        ]);
    }

    /**
     * Delete a meeting note.
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $note = MeetingNote::with('meeting')->findOrFail($id);

        if (!$user->isSuperAdmin() && $note->user_id !== $user->id && $note->meeting?->organizer_id !== $user->id) {
            abort(403, 'Unauthorized to delete this meeting note.');
        }

        $meetingTitle = $note->meeting?->title;
        $note->delete();

        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => 'meeting_note_deleted',
            'auditable_type' => MeetingNote::class,
            'auditable_id' => $id,
            'description' => "Meeting note removed from '{$meetingTitle}' by {$user->name}.",
        ]);

        return response()->json([
            'message' => 'Meeting note deleted successfully.',
        ]);
    }

    protected function formatNote(MeetingNote $note): array
    {
        return [
            'id' => $note->id,
            'meeting_id' => $note->meeting_id,
            'user_id' => $note->user_id,
            'agenda' => $note->agenda,
            'discussion' => $note->discussion,
            'decisions' => $note->decisions,
            'action_items' => $note->action_items,
            'follow_up_date' => $note->follow_up_date?->toIso8601String(),
            'attachments' => $note->attachments ?: [],
            'author' => $note->author ? [
                'id' => $note->author->id,
                'name' => $note->author->name,
                'email' => $note->author->email,
                'avatar_url' => $note->author->avatar_url,
            ] : null,
            'meeting' => $note->meeting ? [
                'id' => $note->meeting->id,
                'title' => $note->meeting->title,
                'start_time' => $note->meeting->start_time->toIso8601String(),
            ] : null,
            'created_at' => $note->created_at->toIso8601String(),
            'updated_at' => $note->updated_at->toIso8601String(),
        ];
    }

    protected function authorizeNoteAccess(MeetingNote $note, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $meeting = $note->meeting;
        if (! $meeting) {
            return;
        }

        if ($meeting->organizer_id === $user->id || $note->user_id === $user->id) {
            return;
        }

        $isParticipant = $meeting->participants()->where('users.id', $user->id)->exists();
        if (!$isParticipant) {
            abort(403, 'Unauthorized access to this meeting note.');
        }
    }
}
