<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRegistrationController extends Controller
{
    /**
     * List user and company registration requests for administrators.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        if (! $admin->isSuperAdmin() && ! $admin->isDeveloper()) {
            abort(403, 'Unauthorized. Administrative credentials required.');
        }

        $status = $request->query('status', User::STATUS_PENDING);
        $type = $request->query('type', 'all');
        $perPage = (int) $request->query('per_page', 25);

        $query = User::withoutGlobalScopes()->with(['company', 'department', 'approver'])
            ->where('role', '!=', User::ROLE_DEVELOPER);

        if (! $admin->isDeveloper()) {
            $query->where('company_id', $admin->company_id)
                ->where('role', '!=', User::ROLE_SUPER_ADMIN);
        } else {
            if ($type === 'companies') {
                $query->where('role', User::ROLE_SUPER_ADMIN);
            } elseif ($type === 'staff') {
                $query->where('role', '!=', User::ROLE_SUPER_ADMIN);
            }
        }

        if (in_array($status, [User::STATUS_PENDING, User::STATUS_APPROVED, User::STATUS_REJECTED], true)) {
            $query->where('approval_status', $status);
        }

        $paginated = $query->latest()->paginate($perPage);

        // Counts
        $countsQuery = User::withoutGlobalScopes()->where('role', '!=', User::ROLE_DEVELOPER);
        if (! $admin->isDeveloper()) {
            $countsQuery->where('company_id', $admin->company_id)
                ->where('role', '!=', User::ROLE_SUPER_ADMIN);
        } elseif ($type === 'companies') {
            $countsQuery->where('role', User::ROLE_SUPER_ADMIN);
        } elseif ($type === 'staff') {
            $countsQuery->where('role', '!=', User::ROLE_SUPER_ADMIN);
        }

        $counts = [
            'pending' => (clone $countsQuery)->where('approval_status', User::STATUS_PENDING)->count(),
            'approved' => (clone $countsQuery)->where('approval_status', User::STATUS_APPROVED)->count(),
            'rejected' => (clone $countsQuery)->where('approval_status', User::STATUS_REJECTED)->count(),
        ];

        return response()->json([
            'data' => collect($paginated->items())->map(fn($u) => $this->formatRequest($u)),
            'counts' => $counts,
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
     * Provide assignment options (roles and departments) for a registration request.
     */
    public function options(Request $request, int|string $id): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        if (! $admin->isSuperAdmin() && ! $admin->isDeveloper()) {
            abort(403, 'Unauthorized.');
        }

        $user = User::withoutGlobalScopes()->with(['company', 'department'])->findOrFail($id);

        if (! $admin->isDeveloper() && $user->company_id !== $admin->company_id) {
            abort(403, 'Unauthorized to view requests from other companies.');
        }

        $departments = Department::where('is_active', true)
            ->when(!$admin->isDeveloper(), fn($q) => $q->where('company_id', $admin->company_id))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $roles = [
            ['key' => 'super_admin', 'label' => 'Company Super Admin'],
            ['key' => 'manager', 'label' => 'Department Manager'],
            ['key' => 'employee', 'label' => 'Team Member / Staff'],
            ['key' => 'client', 'label' => 'Client / External Partner'],
        ];

        return response()->json([
            'data' => [
                'user' => $this->formatRequest($user),
                'roles' => $roles,
                'departments' => $departments,
            ],
            'user' => $this->formatRequest($user),
            'roles' => $roles,
            'departments' => $departments,
        ]);
    }

    /**
     * Approve a registration request.
     */
    public function approve(Request $request, int|string $id): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        if (! $admin->isSuperAdmin() && ! $admin->isDeveloper()) {
            abort(403, 'Unauthorized.');
        }

        $user = User::withoutGlobalScopes()->with(['company'])->findOrFail($id);

        if (! $admin->isDeveloper() && $user->company_id !== $admin->company_id) {
            abort(403, 'Unauthorized. You can only authorize staff in your own company.');
        }

        if ($user->isCompanySuperAdmin() && ! $admin->isDeveloper()) {
            abort(403, 'Unauthorized. Only Platform Developer can authorize new Company Super Admins.');
        }

        $validated = $request->validate([
            'role' => ['nullable', 'string', 'in:super_admin,manager,employee,client'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'title' => ['nullable', 'string', 'max:150'],
        ]);

        $oldStatus = $user->approval_status;
        $roleToSet = $validated['role'] ?? $user->role;

        $user->update([
            'role' => $roleToSet,
            'department_id' => $validated['department_id'] ?? $user->department_id,
            'title' => $validated['title'] ?? $user->title,
            'approval_status' => User::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'rejection_reason' => null,
            'is_active' => true,
        ]);

        if ($user->isCompanySuperAdmin() && $user->company_id) {
            $company = Company::find($user->company_id);
            if ($company) {
                $company->update(['status' => Company::STATUS_ACTIVE]);
            }
        }

        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => $admin->id,
            'action' => 'user_approved',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'description' => "{$admin->role_label} {$admin->name} approved registration for {$user->name} ({$user->email}) via Mobile API.",
            'old_values' => ['approval_status' => $oldStatus],
            'new_values' => [
                'approval_status' => User::STATUS_APPROVED,
                'role' => $user->role,
                'approved_by' => $admin->id,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => "Registration request for {$user->name} was approved successfully.",
            'data' => $this->formatRequest($user->fresh(['company', 'department', 'approver'])),
        ]);
    }

    /**
     * Reject a registration request.
     */
    public function reject(Request $request, int|string $id): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        if (! $admin->isSuperAdmin() && ! $admin->isDeveloper()) {
            abort(403, 'Unauthorized.');
        }

        $user = User::withoutGlobalScopes()->with(['company'])->findOrFail($id);

        if (! $admin->isDeveloper() && $user->company_id !== $admin->company_id) {
            abort(403, 'Unauthorized. You can only manage staff in your own company.');
        }

        if ($user->isCompanySuperAdmin() && ! $admin->isDeveloper()) {
            abort(403, 'Unauthorized. Only Platform Developer can reject Company Super Admins.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $oldStatus = $user->approval_status;

        $user->update([
            'approval_status' => User::STATUS_REJECTED,
            'rejection_reason' => $validated['rejection_reason'] ?? 'Registration criteria not met.',
            'approved_at' => null,
            'approved_by' => $admin->id,
        ]);

        if ($user->isCompanySuperAdmin() && $user->company_id) {
            $company = Company::find($user->company_id);
            if ($company) {
                $company->update(['status' => Company::STATUS_REJECTED]);
            }
        }

        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => $admin->id,
            'action' => 'user_rejected',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'description' => "{$admin->role_label} {$admin->name} rejected registration for {$user->name} ({$user->email}) via Mobile API.",
            'old_values' => ['approval_status' => $oldStatus],
            'new_values' => [
                'approval_status' => User::STATUS_REJECTED,
                'rejection_reason' => $user->rejection_reason,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => "Registration request for {$user->name} was marked as rejected.",
            'data' => $this->formatRequest($user->fresh(['company', 'department'])),
        ]);
    }

    protected function formatRequest(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'role_label' => $u->role_label,
            'title' => $u->title,
            'phone' => $u->phone,
            'approval_status' => $u->approval_status,
            'registration_notes' => $u->registration_notes,
            'rejection_reason' => $u->rejection_reason,
            'company' => $u->company ? [
                'id' => $u->company->id,
                'name' => $u->company->name,
                'status' => $u->company->status,
            ] : null,
            'department' => $u->department ? [
                'id' => $u->department->id,
                'name' => $u->department->name,
            ] : null,
            'approver' => $u->approver ? [
                'id' => $u->approver->id,
                'name' => $u->approver->name,
            ] : null,
            'approved_at' => $u->approved_at?->toIso8601String(),
            'created_at' => $u->created_at?->toIso8601String(),
        ];
    }
}
