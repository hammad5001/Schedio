<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super admin always bypasses approval requirement
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user is approved
        if (!$user->isApproved() || !$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $user->isPending()
                ? 'Your registration request is currently pending Super Admin review.'
                : ($user->isRejected()
                    ? 'Your registration request was rejected' . ($user->rejection_reason ? ': ' . $user->rejection_reason : '.')
                    : 'Your account is currently inactive. Please contact your Super Administrator.');

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
