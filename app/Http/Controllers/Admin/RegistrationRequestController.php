<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
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
        $status = $request->query('status', User::STATUS_PENDING);

        $query = User::with(['department', 'approver'])
            ->where('role', '!=', User::ROLE_SUPER_ADMIN);

        if (in_array($status, [User::STATUS_PENDING, User::STATUS_APPROVED, User::STATUS_REJECTED], true)) {
            $query->where('approval_status', $status);
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        $counts = [
            'pending' => User::where('approval_status', User::STATUS_PENDING)->where('role', '!=', User::ROLE_SUPER_ADMIN)->count(),
            'approved' => User::where('approval_status', User::STATUS_APPROVED)->where('role', '!=', User::ROLE_SUPER_ADMIN)->count(),
            'rejected' => User::where('approval_status', User::STATUS_REJECTED)->where('role', '!=', User::ROLE_SUPER_ADMIN)->count(),
        ];

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('admin.registration-requests.index', compact('requests', 'counts', 'status', 'departments'));
    }

    /**
     * Approve a pending user registration request.
     */
    public function approve(Request $request, User $user): RedirectResponse
    {
        $admin = Auth::user();

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:manager,employee,client'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'title' => ['nullable', 'string', 'max:150'],
        ]);

        $oldStatus = $user->approval_status;

        $user->update([
            'role' => $validated['role'],
            'department_id' => $validated['department_id'] ?? $user->department_id,
            'title' => $validated['title'] ?? $user->title,
            'approval_status' => User::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'rejection_reason' => null,
            'is_active' => true,
        ]);

        // Audit log
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'user_approved',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'description' => "Super Admin {$admin->name} approved registration for {$user->name} ({$user->email}) with role {$user->role}.",
            'old_values' => ['approval_status' => $oldStatus],
            'new_values' => [
                'approval_status' => User::STATUS_APPROVED,
                'role' => $user->role,
                'approved_by' => $admin->id,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Registration request for {$user->name} was approved successfully.");
    }

    /**
     * Reject a user registration request.
     */
    public function reject(Request $request, User $user): RedirectResponse
    {
        $admin = Auth::user();

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

        // Audit log
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'user_rejected',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'description' => "Super Admin {$admin->name} rejected registration for {$user->name} ({$user->email}).",
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
}
