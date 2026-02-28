<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak ditemukan'
            ], 401);
        }

        try {
            $ssoBase = env('SSO_URL');
            
            // Validasi token dengan memanggil /api/me
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get(rtrim($ssoBase, '/') . '/api/me');
            
            // Cek apakah response sukses (200 OK)
            if (!$response->successful()) {
                \Log::warning('Token invalid', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Token tidak valid atau telah kadaluarsa'
                ], 401);
            }

            // Response sukses, ambil data user
            $data = $response->json();
            
            // Dari response berbentuk {"user": {...}}
            $ssoUser = $data['user'] ?? $data;
            
            if (!$ssoUser || !isset($ssoUser['id'])) {
                \Log::error('Invalid user data from SSO', ['data' => $data]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Data user tidak valid dari SSO'
                ], 401);
            }
            
            // Sync user ke database lokal
            $user = User::updateOrCreate(
                ['sso_id' => $ssoUser['id']],
                [
                    'name' => $ssoUser['name'],
                    'email' => $ssoUser['email'],
                    'phone' => $ssoUser['phone'] ?? null,
                    'gender' => $ssoUser['gender'] ?? null,
                    'birthdate' => $ssoUser['birthdate'] ?? null,
                    'role' => $ssoUser['role'] ?? 'user',
                ]
            );
            
            // Attach user ke request
            $request->merge(['auth_user' => $user]);
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
            
            // Cek apakah user adalah admin
            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access only.'
                ], 403);
            }
            
        } catch (\Exception $e) {
            \Log::error('SSO validation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal terhubung ke server SSO'
            ], 500);
        }

        return $next($request);
    }
}