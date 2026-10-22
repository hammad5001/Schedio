<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Task;
use App\Models\User;
use App\Services\NotificationDispatchService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        protected NotificationDispatchService $notificationService
    ) {}

    /**
     * Get paginated list of tasks.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $status = $request->query('status');
        $priority = $request->query('priority');
        $meetingId = $request->query('meeting_id');
        $perPage = (int) $request->query('per_page', 25);

        $query = Task::with(['assignee', 'creator', 'department', 'meeting'])
            ->latest();

        if (! $user->isSuperAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id);
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        if ($meetingId) {
            $query->where('meeting_id', $meetingId);
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => collect($paginated->items())->map(fn($t) => $this->formatTask($t)),
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
     * Show task details.
     */
    public function show(Request $request, Task $task): JsonResponse
    {
        $this->authorizeTaskAccess($task, $request->user());
        $task->loadMissing(['assignee', 'creator', 'department', 'meeting']);

        return response()->json([
            'data' => $this->formatTask($task),
        ]);
    }

    /**
     * Create a task.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['required', 'exists:users,id'],
            'meeting_id' => ['nullable', 'exists:meetings,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'priority' => ['required', 'string', 'in:low,medium,high,urgent'],
            'due_date' => ['nullable', 'date'],
        ]);

        $task = Task::create([
            'company_id' => $user->company_id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'assigned_to' => $validated['assigned_to'],
            'created_by' => $user->id,
            'meeting_id' => $validated['meeting_id'] ?? null,
            'department_id' => $validated['department_id'] ?? $user->department_id,
            'priority' => $validated['priority'],
            'status' => 'pending',
            'due_date' => !empty($validated['due_date']) ? Carbon::parse($validated['due_date']) : null,
        ]);

        $this->notificationService->notifyTaskActivity($task, 'assigned', $user);

        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => 'task_created',
            'auditable_type' => Task::class,
            'auditable_id' => $task->id,
            'description' => "Task '{$task->title}' assigned to user #{$task->assigned_to}.",
        ]);

        $task->load(['assignee', 'creator', 'meeting']);

        return response()->json([
            'message' => 'Task created successfully.',
            'data' => $this->formatTask($task),
        ], 201);
    }

    /**
     * Transition task status.
     */
    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $this->authorizeTaskAccess($task, $request->user());

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:todo,pending,in_progress,review,completed,cancelled'],
        ]);

        $task->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'completed' ? now() : null,
        ]);

        $this->notificationService->notifyTaskActivity($task, 'status_changed', $request->user());

        return response()->json([
            'message' => 'Task status updated.',
            'data' => $this->formatTask($task),
        ]);
    }

    protected function formatTask(Task $t): array
    {
        return [
            'id' => $t->id,
            'title' => $t->title,
            'description' => $t->description,
            'status' => $t->status,
            'priority' => $t->priority,
            'due_date' => $t->due_date?->toIso8601String(),
            'completed_at' => $t->completed_at?->toIso8601String(),
            'meeting_id' => $t->meeting_id,
            'assignee' => $t->assignee ? [
                'id' => $t->assignee->id,
                'name' => $t->assignee->name,
                'email' => $t->assignee->email,
            ] : null,
            'creator' => $t->creator ? [
                'id' => $t->creator->id,
                'name' => $t->creator->name,
            ] : null,
            'meeting' => $t->meeting ? [
                'id' => $t->meeting->id,
                'title' => $t->meeting->title,
            ] : null,
        ];
    }

    protected function authorizeTaskAccess(Task $task, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        if ($task->assigned_to !== $user->id && $task->created_by !== $user->id) {
            abort(403, 'Unauthorized access to task.');
        }
    }
}
