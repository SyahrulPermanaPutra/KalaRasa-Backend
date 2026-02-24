<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Fast check via session
        if (session('user_role') === 'admin') {
            return $next($request);
        }

        // Fallback: try to resolve from database using sso_id or email in session
        $ssoId = session('user_sso_id');
        $email = session('user_email');

        $user = null;
        if ($ssoId) {
            $user = User::where('sso_id', $ssoId)->first();
        }
        if (!$user && $email) {
            $user = User::where('email', $email)->first();
        }

        if ($user) {
        // Set default jika role null (optional)
        if (is_null($user->role)) {
            $user->role = 'user'; // atau 'customer'
            $user->save();
        }
            // Simpan role ke session
            session(['user_role' => $user->role]);

            // Check jika role adalah admin
            if ($user->role === 'admin') {
                return $next($request);
            }
        }

        abort(403, 'Forbidden');
    }
}
