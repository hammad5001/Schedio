<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show login form.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Authenticate user.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'The provided credentials do not match our records.']);
        }

        // Validate approval status for non-Super Admins
        if (!$user->isSuperAdmin()) {
            if ($user->isPending()) {
                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => 'Your account registration request is currently pending Super Admin review. You will receive access once approved.']);
            }

            if ($user->isRejected()) {
                $reason = $user->rejection_reason ? ": {$user->rejection_reason}" : '.';
                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => "Your registration request was rejected{$reason} Please contact the Super Administrator."]);
            }

            if (!$user->is_active) {
                return back()->withInput($request->only('email'))
                    ->withErrors(['email' => 'Your account has been deactivated. Please contact your Super Administrator.']);
            }
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();

        // Record audit log for login
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'description' => "User {$user->name} ({$user->email}) logged into Schedio.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['role' => $user->role],
        ]);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Show registration request form.
     */
    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('auth.register', compact('departments'));
    }

    /**
     * Submit new user registration request.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'title' => ['nullable', 'string', 'max:150'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'registration_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = User::create([
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

        // Audit log registration request
        AuditLog::create([
            'user_id' => null,
            'action' => 'registration_requested',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'description' => "Registration request submitted by {$user->name} ({$user->email}).",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'new_values' => [
                'name' => $user->name,
                'email' => $user->email,
                'department_id' => $user->department_id,
            ],
        ]);

        return redirect()->route('login')->with(
            'status',
            'Your registration request has been submitted successfully! A Super Admin will review and approve your account shortly.'
        );
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'logout',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'description' => "User {$user->name} logged out.",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
