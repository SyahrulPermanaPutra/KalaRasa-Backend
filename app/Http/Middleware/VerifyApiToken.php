<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyApiToken
{
    /**
     * Handle an incoming request for NLP Python API verification
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get token from header
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'valid' => false,
                'error' => 'Token missing'
            ], 401);
        }

        // Verify token using Sanctum
        $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

        if (!$user || !$user->tokenable) {
            return response()->json([
                'valid' => false,
                'error' => 'Invalid token'
            ], 401);
        }

        // Check if token is expired (optional)
        if ($user->expires_at && $user->expires_at->isPast()) {
            return response()->json([
                'valid' => false,
                'error' => 'Token expired'
            ], 401);
        }

        // Attach user to request
        $request->merge(['verified_user' => $user->tokenable]);

        return $next($request);
    }
}