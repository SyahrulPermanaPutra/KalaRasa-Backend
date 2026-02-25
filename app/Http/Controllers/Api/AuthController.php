<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function login(Request $request)
{
    // 1. Validasi input
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
        'device_uuid' => 'required',
        'device_name' => 'required',
        'platform' => 'required',
    ]);

    try {
        $ssoBase = env('SSO_URL');
        if (empty($ssoBase)) {
            return response()->json([
                'success' => false,
                'message' => 'SSO API URL belum dikonfigurasi'
            ], 500);
        }

        $loginUrl = rtrim($ssoBase, '/') . '/api/login';
        
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($loginUrl, [
            'email' => $request->email,
            'password' => $request->password,
            'device_uuid' => $request->device_uuid,
            'device_name' => $request->device_name,
            'platform' => $request->platform,
            'app_id' => env('APP_ID'),
        ]);

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => $response->json('message') ?? 'Login gagal di SSO',
            ], 401);
        }

        $data = $response->json();

        if (!isset($data['access_token'])) {
            \Log::error('Access token tidak ditemukan', ['response' => $data]);
            return response()->json([
                'success' => false,
                'message' => 'Token tidak ditemukan dari SSO',
                'debug' => $data
            ], 401);
        }

        $ssoUser = $data['user'] ?? null;

        if (!$ssoUser) {
            return response()->json([
                'success' => false,
                'message' => 'Data user tidak ditemukan dari SSO'
            ], 401);
        }
        
        // HAPUS BAGIAN INI - Tidak perlu menyimpan token
        /*
        // 3. Simpan atau update user di database lokal
        $user = User::updateOrCreate(
            ['sso_id' => $data['user']['id']],
            [
                'name' => $data['user']['name'],
                'email' => $data['user']['email'],
                'phone' => $data['user']['phone'] ?? null,
                'role' => $data['user']['role'] ?? 'user',
                'api_token' => $data['access_token'], // HAPUS INI
            ]
        );
        */
        
        // GANTI DENGAN INI - Hanya simpan data user tanpa token
        $user = User::updateOrCreate(
            ['sso_id' => $data['user']['id']],
            [
                'name' => $data['user']['name'],
                'email' => $data['user']['email'],
                'phone' => $data['user']['phone'] ?? null,
                'role' => $data['user']['role'] ?? 'user',
                // api_token TIDAK perlu disimpan
            ]
        );

        // 4. Return response dengan data dari SSO
        return response()->json([
            'success' => true,
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'token_type' => $data['token_type'],
            'expires_in' => $data['expires_in'],
            'user' => $this->formatUser($user) // Gunakan formatter yang sama
        ]);

    } catch (\Exception $e) {
        \Log::error('Login error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Gagal terhubung ke server SSO'
        ], 500);
    }
}

    public function logout(Request $request)
{
    // Beritahu SSO bahwa user logout
    try {
        $ssoBase = env('SSO_URL');
        
        Http::withHeaders([
            'Authorization' => 'Bearer ' . $request->bearerToken()
        ])->post(rtrim($ssoBase, '/') . '/api/logout');
        
    } catch (\Exception $e) {
        \Log::warning('Logout SSO error: ' . $e->getMessage());
        // Abaikan error, yang penting client sudah hapus token
    }

    return response()->json([
        'success' => true,
        'message' => 'Logout berhasil'
    ]);
}
    public function refresh(Request $request)
    {
        $refreshToken = $request->input('refresh_token');
        
        if (!$refreshToken) {
            return response()->json([
                'success' => false,
                'message' => 'Refresh token required'
            ], 400);
        }
        
        try {
            $ssoBase = config('services.sso.url') ?? env('SSO_URL', 'https://hub.jtv.co.id');
            $response = Http::post(rtrim($ssoBase, '/') . '/api/refresh', [
                'refresh_token' => $refreshToken
            ]);
            
            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to refresh token'
                ], $response->status());
            }
            
            $data = $response->json();
            
            return response()->json([
                'success' => true,
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'token_type' => $data['token_type'] ?? 'Bearer',
                'expires_in' => $data['expires_in'] ?? null,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Refresh token error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh token'
            ], 500);
        }
    }

    public function profile(Request $request)
    {
        $user = $request->user() ?? $request->auth_user;
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $approvedRecipesCount = \App\Models\Recipe::where('created_by', $user->id)
            ->where('status', 'approved')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->formatUser($user),
                'stats' => [
                    'points' => $user->points ?? 0,
                    'approved_recipes' => $approvedRecipesCount,
                    'point_per_recipe' => config('points.recipe_approved', 10),
                ]
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->auth_user ?? $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:L,P',
            'birthdate' => 'nullable|date',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'gender' => $request->gender,
            'birthdate' => $request->birthdate,
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil diupdate',
            'data' => $this->formatUser($user)
        ]);
    }

    private function formatUser($user)
    {
        return [
            'id' => $user->id,
            'sso_id' => $user->sso_id,
            'name' => $user->name,
            'email' => $user->email,
            // Jika menggunakan relasi role, pastikan sudah didefinisikan di model User
            'role' => $user->role ?? 'user',
            'phone' => $user->phone,
            'gender' => $user->gender,
            'birthdate' => $user->birthdate,
            'points' => $user->points ?? 0,
            'avatar_url' => $user->avatar ? Storage::url($user->avatar) : null,
            'created_at' => $user->created_at,
        ];
    }
}