<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ValidatePassportToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak ditemukan'
            ], 401);
        }

        try {
            // Cek cache dulu untuk performa
            $cacheKey = 'user_token_' . md5($token);
            $user = Cache::remember($cacheKey, 300, function () use ($token) {
                // Validasi token ke Auth Service menggunakan Guzzle
                $client = new Client();
                
                try {
                    $response = $client->request('GET', env('AUTH_SERVICE_URL') . '/api/user', [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $token,
                            'Accept' => 'application/json',
                        ],
                        'timeout' => 10,
                    ]);

                    $statusCode = $response->getStatusCode();
                    
                    if ($statusCode === 200) {
                        $body = $response->getBody()->getContents();
                        return json_decode($body, true);
                    }

                    return null;

                } catch (RequestException $e) {
                    // Token invalid atau expired
                    return null;
                }
            });

            if (!$user) {
                Cache::forget($cacheKey); // Hapus cache jika token invalid
                
                return response()->json([
                    'success' => false,
                    'message' => 'Token tidak valid atau sudah expired'
                ], 401);
            }

            // Attach user data ke request
            $request->merge(['auth_user' => $user]);
            
            // Set user resolver (agar bisa pakai $request->user())
            $request->setUserResolver(function () use ($user) {
                return (object) $user;
            });

            return $next($request);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal validasi token',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}