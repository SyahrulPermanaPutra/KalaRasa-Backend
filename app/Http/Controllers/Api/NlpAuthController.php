<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NlpAuthController extends Controller
{
    /**
     * Verify token endpoint for NLP Python API
     * POST /api/verify-token
     */
    public function verifyToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $token = $request->input('token');

        // Find token using Sanctum
        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

        if (!$accessToken || !$accessToken->tokenable) {
            return response()->json([
                'valid' => false,
                'error' => 'Invalid token'
            ], 401);
        }

        // Check if token is expired
        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return response()->json([
                'valid' => false,
                'error' => 'Token expired'
            ], 401);
        }

        $user = $accessToken->tokenable;

        return response()->json([
            'valid' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }
}