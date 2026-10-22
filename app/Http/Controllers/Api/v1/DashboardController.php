<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\Reminder;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Provide comprehensive executive mobile dashboard payload.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $today = Carbon::today();

        $meetingBaseQuery = Meeting::query()
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('organizer_id', $user->id)
                        ->orWhereHas('participants', fn($p) => $p->where('users.id', $user->id));
                });
            });

        $metrics = [
            'pending_requests' => $user->isSuperAdmin()
                ? User::where('approval_status', User::STATUS_PENDING)->where('role', '!=', User::ROLE_SUPER_ADMIN)->count()
                : 0,
            'today_meetings_count' => (clone $meetingBaseQuery)
                ->whereDate('start_time', $today)
                ->where('status', '!=', 'cancelled')
                ->count(),
            'total_users' => User::where('approval_status', User::STATUS_APPROVED)->count(),
            'upcoming_meetings' => (clone $meetingBaseQuery)
                ->where('start_time', '>=', now())
                ->where('status', '!=', 'cancelled')
                ->count(),
            'client_meetings' => (clone $meetingBaseQuery)
                ->where('metadata->is_client_meeting', true)
                ->where('status', '!=', 'cancelled')
                ->count(),
            'active_reminders' => Reminder::where('user_id', $user->id)
                ->where('status', 'pending')
                ->count(),
            'pending_tasks' => Task::when(!$user->isSuperAdmin(), fn($q) => $q->where(fn($sub) => $sub->where('assigned_to', $user->id)->orWhere('created_by', $user->id)))
                ->where('status', '!=', 'completed')
                ->count(),
            'unread_notifications' => Reminder::where('user_id', $user->id)
                ->where('status', 'pending')
                ->count(),
        ];

        // Today's specific schedule
        $todayMeetings = (clone $meetingBaseQuery)
            ->with(['organizer', 'department', 'room', 'participants'])
            ->whereDate('start_time', $today)
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->get();

        $todayEvents = Event::where(function ($q) use ($today) {
            $q->whereDate('start_time', '<=', $today)
              ->whereDate('end_time', '>=', $today);
        })
        ->where(function ($q) use ($user) {
            $q->where('is_company_wide', true)
              ->orWhere('user_id', $user->id)
              ->orWhere('department_id', $user->department_id);
        })
        ->get();

        // Upcoming meetings next 7 days
        $upcomingMeetings = (clone $meetingBaseQuery)
            ->with(['organizer', 'department', 'room', 'participants'])
            ->where('start_time', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->take(6)
            ->get();

        // Next immediate upcoming meeting
        $nextMeeting = (clone $meetingBaseQuery)
            ->with(['organizer', 'room', 'participants'])
            ->where('start_time', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->first();

        // Pending Tasks
        $pendingTasks = Task::with(['department', 'creator', 'assignee', 'meeting'])
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('assigned_to', $user->id)
                        ->orWhere('created_by', $user->id);
                });
            })
            ->where('status', '!=', 'completed')
            ->orderBy('due_date')
            ->take(6)
            ->get();

        // Reminders
        $upcomingReminders = Reminder::where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('remind_at')
            ->take(5)
            ->get();

        // Recent Activity
        $recentActivities = AuditLog::with('user')
            ->when(!$user->isSuperAdmin(), fn($q) => $q->where('user_id', $user->id))
            ->latest()
            ->take(6)
            ->get();

        $data = [
            'metrics' => $metrics,
            'today_meetings' => $todayMeetings->map(fn($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'start_time' => $m->start_time->toIso8601String(),
                'end_time' => $m->end_time->toIso8601String(),
                'status' => $m->status,
                'type' => $m->type,
                'room' => $m->room ? ['id' => $m->room->id, 'name' => $m->room->name] : null,
                'organizer' => $m->organizer ? ['id' => $m->organizer->id, 'name' => $m->organizer->name] : null,
            ]),
            'today_events' => $todayEvents->map(fn($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'category' => $e->category,
                'start_time' => $e->start_time->toIso8601String(),
                'end_time' => $e->end_time->toIso8601String(),
                'is_all_day' => $e->is_all_day,
                'color' => $e->color,
            ]),
            'upcoming_meetings' => $upcomingMeetings->map(fn($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'start_time' => $m->start_time->toIso8601String(),
                'end_time' => $m->end_time->toIso8601String(),
                'status' => $m->status,
                'organizer' => $m->organizer ? ['id' => $m->organizer->id, 'name' => $m->organizer->name] : null,
            ]),
            'next_meeting' => $nextMeeting ? [
                'id' => $nextMeeting->id,
                'title' => $nextMeeting->title,
                'start_time' => $nextMeeting->start_time->toIso8601String(),
                'end_time' => $nextMeeting->end_time->toIso8601String(),
                'status' => $nextMeeting->status,
                'room' => $nextMeeting->room ? ['id' => $nextMeeting->room->id, 'name' => $nextMeeting->room->name] : null,
            ] : null,
            'pending_tasks' => $pendingTasks->map(fn($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'priority' => $t->priority,
                'status' => $t->status,
                'due_date' => $t->due_date?->toIso8601String(),
            ]),
            'upcoming_reminders' => $upcomingReminders->map(fn($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'remind_at' => $r->remind_at?->toIso8601String(),
                'status' => $r->status,
            ]),
            'recent_activities' => $recentActivities->map(fn($a) => [
                'id' => $a->id,
                'action' => $a->action,
                'description' => $a->description,
                'created_at' => $a->created_at->toIso8601String(),
            ]),
        ];

        return response()->json([
            'data' => $data,
            'metrics' => $metrics,
            'today_meetings' => $data['today_meetings'],
            'today_events' => $data['today_events'],
            'upcoming_meetings' => $data['upcoming_meetings'],
            'next_meeting' => $data['next_meeting'],
            'pending_tasks' => $data['pending_tasks'],
            'upcoming_reminders' => $data['upcoming_reminders'],
        ]);
    }
}
