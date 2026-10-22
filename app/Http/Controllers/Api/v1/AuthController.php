<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
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
