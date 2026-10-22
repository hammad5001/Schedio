<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\User;
use App\Services\ConflictDetectionService;
use App\Services\ReminderEngineService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class PublicBookingController extends Controller
{
    public function __construct(
        protected ConflictDetectionService $conflictService,
        protected ReminderEngineService $reminderService
    ) {}

    protected function resolveHost(string $identifier): User
    {
        $query = User::where('is_active', true)
            ->where('approval_status', User::STATUS_APPROVED);

        $host = (clone $query)->where('username', $identifier)->first();

        if (! $host && is_numeric($identifier)) {
            $host = (clone $query)->where('id', (int) $identifier)->first();
        }

        if (! $host) {
            $host = (clone $query)->where('email', 'like', "{$identifier}@%")->first();
        }

        if (! $host) {
            abort(404, 'The requested booking calendar was not found or is currently inactive.');
        }

        return $host;
    }

    /**
     * Get public host booking details.
     */
    public function show(string $username): JsonResponse
    {
        $host = $this->resolveHost($username);
        $durations = $host->getBookingDurations();

        return response()->json([
            'data' => [
                'id' => $host->id,
                'name' => $host->name,
                'title' => $host->title,
                'avatar_url' => $host->avatar_url,
                'timezone' => $host->timezone ?: 'UTC',
                'booking_headline' => $host->booking_headline ?: "Book a 1-on-1 meeting with {$host->name}",
                'booking_bio' => $host->booking_bio,
                'durations' => $durations,
                'default_duration' => $durations[0] ?? 30,
            ],
        ]);
    }

    /**
     * Get available public slots for a given date and duration.
     */
    public function slots(Request $request, string $username): JsonResponse
    {
        $host = $this->resolveHost($username);

        $request->validate([
            'date' => ['required', 'date'],
            'duration' => ['nullable', 'integer', 'in:15,30,45,60,90'],
        ]);

        $tz = $host->timezone ?: 'UTC';
        $targetDate = Carbon::parse($request->input('date'), $tz);
        $duration = (int) ($request->input('duration') ?? 30);

        $startWindow = $targetDate->copy()->setTime(9, 0);
        $endWindow = $targetDate->copy()->setTime(17, 0);
        $stepMinutes = 30;

        $availableSlots = [];
        $cursor = $startWindow->copy();

        while ($cursor->copy()->addMinutes($duration)->lte($endWindow)) {
            $slotStart = $cursor->copy();
            $slotEnd = $cursor->copy()->addMinutes($duration);

            if ($slotStart->isPast()) {
                $cursor->addMinutes($stepMinutes);
                continue;
            }

            $check = $this->conflictService->checkAvailability(
                $slotStart,
                $slotEnd,
                null,
                [$host->id]
            );

            if (! $check['has_conflicts']) {
                $availableSlots[] = [
                    'start_time' => $slotStart->toIso8601String(),
                    'end_time' => $slotEnd->toIso8601String(),
                    'formatted_time' => $slotStart->format('h:i a'),
                    'time_24' => $slotStart->format('H:i'),
                ];
            }

            $cursor->addMinutes($stepMinutes);
        }

        return response()->json([
            'date' => $targetDate->format('Y-m-d'),
            'duration' => $duration,
            'slots' => $availableSlots,
            'total_available' => count($availableSlots),
        ]);
    }

    /**
     * Book appointment via public booking endpoint.
     */
    public function book(Request $request, string $username): JsonResponse
    {
        $host = $this->resolveHost($username);

        $validated = $request->validate([
            'client_name' => ['required', 'string', 'max:150'],
            'client_email' => ['required', 'email', 'max:150'],
            'client_company' => ['nullable', 'string', 'max:150'],
            'start_time' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'in:15,30,45,60,90'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $startTime = Carbon::parse($validated['start_time']);
        $duration = (int) $validated['duration_minutes'];
        $endTime = $startTime->copy()->addMinutes($duration);

        // Conflict check
        $check = $this->conflictService->checkAvailability($startTime, $endTime, null, [$host->id]);
        if ($check['has_conflicts']) {
            return response()->json([
                'message' => 'Sorry, this time slot was just booked by someone else. Please choose another slot.',
                'errors' => ['start_time' => ['Sorry, this time slot was just booked by someone else. Please choose another slot.']],
            ], 422);
        }

        $meeting = DB::transaction(function () use ($validated, $host, $startTime, $endTime, $duration) {
            $meetingLink = $host->default_meeting_link ?: ('https://meet.schedio.com/room-' . strtolower(Str::random(10)));

            $meeting = Meeting::create([
                'company_id' => $host->company_id,
                'title' => "Consultation: {$validated['client_name']} & {$host->name}",
                'description' => $validated['notes'] ?? "Public booking via client scheduling portal.",
                'organizer_id' => $host->id,
                'department_id' => $host->department_id,
                'room_id' => null,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'timezone' => $host->timezone ?: 'UTC',
                'status' => 'scheduled',
                'type' => 'virtual',
                'meeting_link' => $meetingLink,
                'location' => 'Video Conference',
                'is_private' => false,
                'metadata' => [
                    'is_client_meeting' => true,
                    'client_name' => $validated['client_name'],
                    'client_email' => $validated['client_email'],
                    'client_company' => $validated['client_company'] ?? null,
                    'duration_minutes' => $duration,
                    'booked_via' => 'mobile_public_booking',
                ],
            ]);

            MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => $host->id,
                'role' => 'host',
                'status' => 'accepted',
                'responded_at' => now(),
            ]);

            $this->reminderService->scheduleMeetingReminders($meeting, 15);

            AuditLog::create([
                'company_id' => $host->company_id,
                'user_id' => null,
                'action' => 'public_booking_scheduled',
                'auditable_type' => Meeting::class,
                'auditable_id' => $meeting->id,
                'description' => "Client {$validated['client_name']} booked meeting with {$host->name}.",
                'ip_address' => request()->ip(),
            ]);

            return $meeting;
        });

        $calendarExportUrl = URL::signedRoute('booking.export-ical', [
            'username' => $username,
            'meeting' => $meeting->id,
        ]);

        return response()->json([
            'message' => 'Appointment booked successfully.',
            'data' => [
                'meeting_id' => $meeting->id,
                'title' => $meeting->title,
                'start_time' => $meeting->start_time?->toIso8601String(),
                'end_time' => $meeting->end_time?->toIso8601String(),
                'meeting_link' => $meeting->meeting_link,
                'calendar_export_url' => $calendarExportUrl,
            ],
        ], 201);
    }
}
