<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\Room;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display unified Schedio executive dashboard.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // System overview metrics
        $metrics = [
            'pending_requests' => $user->isSuperAdmin()
                ? User::where('approval_status', User::STATUS_PENDING)->where('role', '!=', User::ROLE_SUPER_ADMIN)->count()
                : 0,
            'total_users' => User::where('approval_status', User::STATUS_APPROVED)->count(),
            'upcoming_meetings' => Meeting::where('start_time', '>=', now())
                ->where('status', '!=', 'cancelled')
                ->count(),
            'available_rooms' => Room::where('is_active', true)->count(),
            'pending_tasks' => Task::where('status', '!=', 'completed')->count(),
        ];

        // Upcoming meetings for the current user
        $upcomingMeetings = Meeting::with(['organizer', 'room', 'department'])
            ->where('start_time', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->take(5)
            ->get();

        // Tasks assigned to user or created
        $myTasks = Task::with(['department', 'creator'])
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('assigned_to', $user->id)
                        ->orWhere('created_by', $user->id);
                });
            })
            ->where('status', '!=', 'completed')
            ->orderBy('due_date')
            ->take(5)
            ->get();

        // Recent system audit activities
        $recentActivities = AuditLog::with('user')
            ->latest()
            ->take(6)
            ->get();

        return view('dashboard', compact('user', 'metrics', 'upcomingMeetings', 'myTasks', 'recentActivities'));
    }
}
