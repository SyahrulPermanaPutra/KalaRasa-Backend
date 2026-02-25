<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthSSO
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'message' => 'Unauthenticated',
                'error' => 'Token not provided'
            ], 401);
        }

        try {
            $ssoApiUrl = env('SSO_API_URL', 'http://localhost:8001/api');
            
            $response = Http::timeout(10)
                ->withToken($token)
                ->get($ssoApiUrl . '/me');
            
            // DEBUG: Log response dari SSO
            Log::info('SSO Response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $response->json()
            ]);
            
            if ($response->failed()) {
                return response()->json([
                    'message' => 'Invalid or expired token',
                    'error' => 'SSO validation failed'
                ], 401);
            }

            // Ambil data user dari response
            $responseData = $response->json();
            
            // DEBUG: Cek struktur response
            Log::info('Response Data Structure', [
                'has_user_key' => isset($responseData['user']),
                'keys' => array_keys($responseData ?? []),
                'data' => $responseData
            ]);
            
            // Handle berbagai format response dari SSO
            if (isset($responseData['user'])) {
                // Format: {"user": {...}}
                $userData = $responseData['user'];
            } elseif (isset($responseData['data'])) {
                // Format: {"data": {...}}
                $userData = $responseData['data'];
            } else {
                // Format: {...} (langsung user data)
                $userData = $responseData;
            }
            
            // Validasi userData tidak null
            if (!$userData || !is_array($userData)) {
                Log::error('Invalid user data from SSO', [
                    'userData' => $userData
                ]);
                
                return response()->json([
                    'message' => 'Invalid response from SSO',
                    'error' => 'User data is null or invalid'
                ], 500);
            }
            
            // Cari atau buat user di database lokal
            $user = \App\Models\User::updateOrCreate(
                [
                    'email' => $userData['email'], // KUNCI UTAMA
                ],
                [
                    'sso_id' => $userData['id'] ?? null,
                    'name'   => $userData['name'] ?? 'Unknown',
                    'role'   => $userData['role'] ?? 'user',
                    'sso_raw'=> $userData,
                ]
            );

            Log::info('User created/updated', [
                'user_id' => $user->id,
                'sso_raw' => $user->sso_raw
            ]);

            // Simpan user ke request
            $request->merge(['auth_user' => $user]);
            
            // Simpan ke session
            session([
                'user_id' => $user->id,
                'user_sso_raw' => $user->sso_raw,
                'user_email' => $user->email,
                'user_role' => $user->role
            ]);

            return $next($request);
            
        } catch (\Exception $e) {
            Log::error('SSO authentication error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Authentication failed',
                'error' => $e->getMessage(),
                'debug' => config('app.debug') ? [
                    'line' => $e->getLine(),
                    'file' => basename($e->getFile())
                ] : null
            ], 500);
        }
    }
}