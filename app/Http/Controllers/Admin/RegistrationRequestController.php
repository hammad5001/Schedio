<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegistrationRequestController extends Controller
{
    /**
     * Display listing of pending, approved, and rejected registration requests.
     */
    public function index(Request $request): View
    {
        $admin = Auth::user();
        $status = $request->query('status', User::STATUS_PENDING);
        $type = $request->query('type', 'all');

        $query = User::withoutGlobalScopes()->with(['company', 'department', 'approver'])
            ->where('role', '!=', User::ROLE_DEVELOPER);

        if (! $admin->isDeveloper()) {
            // Company Super Admin can only see registration requests for their own company
            $query->where('company_id', $admin->company_id)
                ->where('role', '!=', User::ROLE_SUPER_ADMIN);
        } else {
            // Platform Developer can view all or filter by type
            if ($type === 'companies') {
                $query->where('role', User::ROLE_SUPER_ADMIN);
            } elseif ($type === 'staff') {
                $query->where('role', '!=', User::ROLE_SUPER_ADMIN);
            }
        }

        if (in_array($status, [User::STATUS_PENDING, User::STATUS_APPROVED, User::STATUS_REJECTED], true)) {
            $query->where('approval_status', $status);
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        // Calculate count metrics scoped by admin authority
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

        // Developer company counts
        $pendingCompanyCount = $admin->isDeveloper()
            ? User::withoutGlobalScopes()->where('role', User::ROLE_SUPER_ADMIN)->where('approval_status', User::STATUS_PENDING)->count()
            : 0;

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('admin.registration-requests.index', compact('requests', 'counts', 'status', 'type', 'departments', 'admin', 'pendingCompanyCount'));
    }

    /**
     * Approve a pending user or company super admin registration request.
     */
    public function approve(Request $request, User $user): RedirectResponse
    {
        $admin = Auth::user();

        // Authorization check: non-developers can only approve requests in their own company
        if (! $admin->isDeveloper() && $user->company_id !== $admin->company_id) {
            abort(403, 'Unauthorized. You can only authorize staff in your own company.');
        }

        // Only Developer can approve a Company Super Admin
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

        // If approving a Company Super Admin, activate their company workspace!
        if ($user->isCompanySuperAdmin() && $user->company_id) {
            $company = Company::find($user->company_id);
            if ($company) {
                $company->update(['status' => Company::STATUS_ACTIVE]);
            }
        }

        // Audit log
        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => $admin->id,
            'action' => 'user_approved',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'description' => "{$admin->role_label} {$admin->name} approved registration for {$user->name} ({$user->email}) with role {$user->role}.",
            'old_values' => ['approval_status' => $oldStatus],
            'new_values' => [
                'approval_status' => User::STATUS_APPROVED,
                'role' => $user->role,
                'approved_by' => $admin->id,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $msg = $user->isCompanySuperAdmin()
            ? "Company workspace and Super Admin account for {$user->name} ({$user->company?->name}) has been activated successfully!"
            : "Registration request for {$user->name} was approved successfully.";

        return back()->with('success', $msg);
    }

    /**
     * Reject a user or company super admin registration request.
     */
    public function reject(Request $request, User $user): RedirectResponse
    {
        $admin = Auth::user();

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

        // If Company Super Admin was rejected, reject the company
        if ($user->isCompanySuperAdmin() && $user->company_id) {
            $company = Company::find($user->company_id);
            if ($company) {
                $company->update(['status' => Company::STATUS_REJECTED]);
            }
        }

        // Audit log
        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => $admin->id,
            'action' => 'user_rejected',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'description' => "{$admin->role_label} {$admin->name} rejected registration for {$user->name} ({$user->email}).",
            'old_values' => ['approval_status' => $oldStatus],
            'new_values' => [
                'approval_status' => User::STATUS_REJECTED,
                'rejection_reason' => $user->rejection_reason,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Registration request for {$user->name} was marked as rejected.");
    }

    /**
     * Revert and purge a rejected or mistaken registration request.
     * This permanently removes the applicant record and any pending orphan company workspace,
     * freeing up their email address so they can re-apply cleanly.
     */
    public function revert(Request $request, User $user): RedirectResponse
    {
        $admin = Auth::user();

        if (! $admin->isDeveloper() && $user->company_id !== $admin->company_id) {
            abort(403, 'Unauthorized. You can only manage staff in your own company.');
        }

        if ($user->isCompanySuperAdmin() && ! $admin->isDeveloper()) {
            abort(403, 'Unauthorized. Only Platform Developer can revert Company Super Admin applications.');
        }

        $userName = $user->name;
        $userEmail = $user->email;
        $companyId = $user->company_id;
        $isCompanySuperAdmin = $user->isCompanySuperAdmin();

        // Audit log before deleting
        AuditLog::create([
            'company_id' => $companyId,
            'user_id' => $admin->id,
            'action' => 'registration_reverted',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'description' => "{$admin->role_label} {$admin->name} reverted and purged the registration request for {$userName} ({$userEmail}). The email address has been freed to allow re-application.",
            'old_values' => [
                'approval_status' => $user->approval_status,
                'role' => $user->role,
                'email' => $userEmail,
                'rejection_reason' => $user->rejection_reason,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // If user was a Company Super Admin, remove the company if no other users exist
        if ($isCompanySuperAdmin && $companyId) {
            $otherUsersCount = User::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherUsersCount === 0) {
                $company = Company::find($companyId);
                if ($company) {
                    $company->delete();
                }
            }
        }

        // Permanently force-delete to release the unique email constraint
        $user->forceDelete();

        return back()->with('success', "Application for {$userName} ({$userEmail}) was successfully reverted. The email is now freed up and the user can re-apply.");
    }

    /**
     * Restore a rejected request back to the pending queue for direct approval or role adjustment.
     */
    public function restorePending(Request $request, User $user): RedirectResponse
    {
        $admin = Auth::user();

        if (! $admin->isDeveloper() && $user->company_id !== $admin->company_id) {
            abort(403, 'Unauthorized. You can only manage staff in your own company.');
        }

        if ($user->isCompanySuperAdmin() && ! $admin->isDeveloper()) {
            abort(403, 'Unauthorized. Only Platform Developer can restore Company Super Admin applications.');
        }

        $user->update([
            'approval_status' => User::STATUS_PENDING,
            'rejection_reason' => null,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        if ($user->isCompanySuperAdmin() && $user->company_id) {
            $company = Company::find($user->company_id);
            if ($company && $company->status === Company::STATUS_REJECTED) {
                $company->update(['status' => Company::STATUS_PENDING]);
            }
        }

        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => $admin->id,
            'action' => 'registration_restored_pending',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'description' => "{$admin->role_label} {$admin->name} restored {$user->name} ({$user->email}) to the pending queue.",
            'new_values' => ['approval_status' => User::STATUS_PENDING],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Registration request for {$user->name} has been restored to the Pending Queue.");
    }
}
