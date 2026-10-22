<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    /**
     * Return consolidated lookups for mobile app bootstrapping & offline caching.
     */
    public function index(Request $request): JsonResponse
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'description']);
        $rooms = Room::where('is_active', true)->orderBy('name')->get();
        $users = User::where('approval_status', User::STATUS_APPROVED)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $companies = Company::where('status', Company::STATUS_ACTIVE)->orderBy('name')->get(['id', 'name', 'slug']);

        $roles = [
            ['key' => 'super_admin', 'label' => 'Company Super Admin'],
            ['key' => 'manager', 'label' => 'Department Manager'],
            ['key' => 'employee', 'label' => 'Team Member / Staff'],
            ['key' => 'client', 'label' => 'Client / External Partner'],
        ];

        $taskStatuses = [
            ['key' => 'todo', 'label' => 'To Do'],
            ['key' => 'pending', 'label' => 'Pending'],
            ['key' => 'in_progress', 'label' => 'In Progress'],
            ['key' => 'review', 'label' => 'In Review'],
            ['key' => 'completed', 'label' => 'Completed'],
            ['key' => 'cancelled', 'label' => 'Cancelled'],
        ];

        $taskPriorities = [
            ['key' => 'low', 'label' => 'Low Priority'],
            ['key' => 'medium', 'label' => 'Medium Priority'],
            ['key' => 'high', 'label' => 'High Priority'],
            ['key' => 'urgent', 'label' => 'Urgent Priority'],
        ];

        $eventCategories = [
            ['key' => 'company_holiday', 'label' => 'Company Holiday', 'color' => '#ec4899'],
            ['key' => 'training', 'label' => 'Training & Development', 'color' => '#8b5cf6'],
            ['key' => 'milestone', 'label' => 'Milestone / Deliverable', 'color' => '#f59e0b'],
            ['key' => 'deadline', 'label' => 'Project Deadline', 'color' => '#ef4444'],
            ['key' => 'out_of_office', 'label' => 'Out of Office', 'color' => '#64748b'],
            ['key' => 'general', 'label' => 'General Event', 'color' => '#3b82f6'],
        ];

        $pipelineStages = [
            ['key' => 'discovery', 'label' => 'Discovery', 'aliases' => ['lead', 'contacted']],
            ['key' => 'scheduled', 'label' => 'Scheduled', 'aliases' => ['meeting_scheduled']],
            ['key' => 'paperwork_onboarding', 'label' => 'Paperwork / Onboarding', 'aliases' => ['proposal', 'proposal_sent']],
            ['key' => 'onboarded', 'label' => 'Onboarded', 'aliases' => ['won']],
            ['key' => 'deadend', 'label' => 'Deadend', 'aliases' => ['lost']],
            ['key' => 'pause', 'label' => 'Paused', 'aliases' => ['paused']],
        ];

        $data = [
            'departments' => $departments,
            'rooms' => $rooms->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'code' => $r->code,
                'location' => $r->location,
                'capacity' => $r->capacity,
                'color' => $r->color,
                'amenities' => $r->amenities ?: [],
            ]),
            'users' => $users->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'role_label' => $u->role_label,
                'avatar_url' => $u->avatar_url,
                'department_id' => $u->department_id,
            ]),
            'companies' => $companies,
            'roles' => $roles,
            'task_statuses' => $taskStatuses,
            'task_priorities' => $taskPriorities,
            'event_categories' => $eventCategories,
            'pipeline_stages' => $pipelineStages,
            'client_statuses' => $pipelineStages,
        ];

        return response()->json([
            'data' => $data,
            // Also expose top-level keys for direct client consumption
            'departments' => $data['departments'],
            'rooms' => $data['rooms'],
            'users' => $data['users'],
            'companies' => $companies,
            'roles' => $roles,
            'task_statuses' => $taskStatuses,
            'task_priorities' => $taskPriorities,
            'event_categories' => $eventCategories,
            'pipeline_stages' => $pipelineStages,
            'client_statuses' => $pipelineStages,
        ]);
    }
    /**
     * List active company departments.
     */
    public function departments(Request $request): JsonResponse
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return response()->json([
            'data' => $departments->map(fn($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
                'description' => $d->description,
            ]),
        ]);
    }

    /**
     * List active meeting rooms.
     */
    public function rooms(Request $request): JsonResponse
    {
        $rooms = Room::where('is_active', true)->orderBy('name')->get();

        return response()->json([
            'data' => $rooms->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'code' => $r->code,
                'location' => $r->location,
                'capacity' => $r->capacity,
                'color' => $r->color,
                'amenities' => $r->amenities ?: [],
            ]),
        ]);
    }

    /**
     * List active approved team members.
     */
    public function users(Request $request): JsonResponse
    {
        $users = User::where('approval_status', User::STATUS_APPROVED)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $users->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'role_label' => $u->role_label,
                'avatar_url' => $u->avatar_url,
                'department_id' => $u->department_id,
            ]),
        ]);
    }
}
