<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Provide active companies and departments for the mobile registration form.
     */
    public function registrationOptions(): JsonResponse
    {
        $companies = Company::where('status', Company::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $departments = Department::withoutGlobalScopes()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'company_id']);

        return response()->json([
            'data' => [
                'companies' => $companies,
                'departments' => $departments,
                'registration_types' => [
                    ['key' => 'employee', 'label' => 'Team Member / Staff'],
                    ['key' => 'company_super_admin', 'label' => 'New Company Super Admin (CEO)'],
                ],
            ],
            // Direct keys for flat mobile parsing
            'companies' => $companies,
            'departments' => $departments,
        ]);
    }

    /**
     * Submit user or company registration request via Mobile API.
     */
    public function register(Request $request): JsonResponse
    {
        $regType = $request->input('registration_type');
        if (! $regType) {
            $regType = $request->filled('company_name') ? 'company_super_admin' : 'employee';
        }

        $rules = [
            'registration_type' => ['nullable', 'string', 'in:company_super_admin,employee'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'title' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'registration_notes' => ['nullable', 'string', 'max:1000'],
        ];

        if ($regType === 'company_super_admin') {
            $rules['company_name'] = ['required', 'string', 'max:255'];
        } else {
            $rules['company_id'] = ['nullable', 'exists:companies,id'];
            $rules['department_id'] = ['nullable', 'exists:departments,id'];
        }

        $validated = $request->validate($rules);

        if ($regType === 'company_super_admin') {
            $company = Company::create([
                'name' => $validated['company_name'],
                'status' => Company::STATUS_PENDING,
            ]);

            $user = User::create([
                'company_id' => $company->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'title' => $validated['title'] ?: 'Chief Executive Officer (CEO)',
                'phone' => $validated['phone'] ?? null,
                'role' => User::ROLE_SUPER_ADMIN,
                'approval_status' => User::STATUS_PENDING,
                'registration_notes' => $validated['registration_notes'] ?? null,
                'is_active' => true,
                'timezone' => 'UTC',
            ]);

            $company->update(['created_by' => $user->id]);

            AuditLog::create([
                'company_id' => $company->id,
                'user_id' => null,
                'action' => 'company_registration_requested',
                'auditable_type' => Company::class,
                'auditable_id' => $company->id,
                'description' => "Company Super Admin registration requested for '{$company->name}' by {$user->name} ({$user->email}). Routed to Developer for approval.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'new_values' => [
                    'company_name' => $company->name,
                    'ceo_name' => $user->name,
                    'ceo_email' => $user->email,
                ],
            ]);

            return response()->json([
                'message' => "Your registration request for '{$company->name}' has been submitted! The Platform Developer will review and activate your company workspace shortly.",
                'account_status' => 'pending',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'approval_status' => User::STATUS_PENDING,
                ],
            ], 201);
        }

        $companyId = $validated['company_id'] ?? Company::where('status', Company::STATUS_ACTIVE)->value('id');

        $user = User::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'title' => $validated['title'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'role' => User::ROLE_EMPLOYEE,
            'approval_status' => User::STATUS_PENDING,
            'registration_notes' => $validated['registration_notes'] ?? null,
            'is_active' => true,
            'timezone' => 'UTC',
        ]);

        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => null,
            'action' => 'registration_requested',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'description' => "Registration request submitted by {$user->name} ({$user->email}) for company ID {$user->company_id}.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => [
                'name' => $user->name,
                'email' => $user->email,
                'department_id' => $user->department_id,
                'company_id' => $user->company_id,
            ],
        ]);

        return response()->json([
            'message' => 'Your staff registration request has been submitted successfully! Your Company Super Admin will review and approve your account shortly.',
            'account_status' => 'pending',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'company_id' => $user->company_id,
                'department_id' => $user->department_id,
                'approval_status' => User::STATUS_PENDING,
            ],
        ], 201);
    }
    /**
     * Authenticate mobile client and return Sanctum Bearer token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        /** @var User|null $user */
        $user = User::withoutGlobalScopes()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 401);
        }

        // Account status checks
        if ($user->approval_status === User::STATUS_PENDING) {
            return response()->json([
                'message' => 'Your account registration is pending administrator approval.',
                'account_status' => 'pending',
            ], 403);
        }

        if ($user->approval_status === User::STATUS_REJECTED) {
            return response()->json([
                'message' => $user->rejection_reason ?: 'Your account registration has been rejected.',
                'account_status' => 'rejected',
            ], 403);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Your account is currently deactivated.',
                'account_status' => 'inactive',
            ], 403);
        }

        if ($user->company_id && $user->company && ! $user->company->isActive()) {
            return response()->json([
                'message' => 'Your company workspace is inactive or suspended.',
                'account_status' => 'inactive',
            ], 403);
        }

        $deviceName = $validated['device_name'] ?? 'mobile_device';
        $token = $user->createToken($deviceName, ['mobile'])->plainTextToken;

        // Log successful API login
        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => 'api_login',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'description' => "User {$user->name} authenticated via Mobile API ({$deviceName}).",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['device' => $deviceName],
        ]);

        return response()->json([
            'token' => $token,
            'user' => $this->formatUser($user),
        ], 200);
    }

    /**
     * Return authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => $this->formatUser($user),
        ], 200);
    }

    /**
     * Revoke current access token on sign-out.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ], 200);
    }

    /**
     * Format user payload matching Flutter mobile app schema contract.
     */
    public function formatUser(User $user): array
    {
        $user->loadMissing(['company', 'department']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'role_label' => $user->role_label,
            'timezone' => $user->timezone ?? 'UTC',
            'phone' => $user->phone,
            'title' => $user->title,
            'avatar_url' => $user->avatar_url,
            'company' => $user->company ? [
                'id' => $user->company->id,
                'name' => $user->company->name,
                'slug' => $user->company->slug,
            ] : null,
            'department' => $user->department ? [
                'id' => $user->department->id,
                'name' => $user->department->name,
                'code' => $user->department->code,
            ] : null,
            'booking_url' => $user->getPublicBookingUrl(),
            'booking_username' => $user->username ?: (string) $user->id,
            'can_administer' => $user->isSuperAdmin(),
            'can_manage_rooms' => $user->isSuperAdmin() || $user->isManager(),
            'notification_preferences' => $user->notification_preferences ?: [
                'channels' => ['in_app' => true, 'email' => true],
                'categories' => ['meetings' => ['in_app' => true, 'email' => true]],
            ],
        ];
    }
}
