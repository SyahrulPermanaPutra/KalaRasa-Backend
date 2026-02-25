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
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
        'device_uuid' => 'required',
        'device_name' => 'required',
        'platform' => 'required',
    ]);

    $ssoBase = config('services.sso.url');
    
    if (!$ssoBase) {
        $ssoBase = env('SSO_URL', 'https://hub.jtv.co.id');
    }

    $loginUrl = rtrim($ssoBase, '/') . '/api/login';
    $loginUrl = str_replace('http://', 'https://', $loginUrl);

    try {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($loginUrl, [
            'email' => $request->email,
            'password' => $request->password,
            'device_uuid' => $request->device_uuid,
            'device_name' => $request->device_name,
            'platform' => $request->platform,
            'app_id' => $request->app_id ?? config('services.sso.app_id'),
        ]);

        \Log::info('SSO Response Status: ' . $response->status());
        \Log::info('SSO Response Body: ', ['body' => $response->json()]);

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

        // ✅ Gunakan null coalescing operator (??) untuk semua field
        $user = User::updateOrCreate(
            ['sso_id' => $ssoUser['id'] ?? null],
            [
                'name' => $ssoUser['name'] ?? ($ssoUser['nama'] ?? 'User'),
                'email' => $ssoUser['email'] ?? null,
                'phone' => $ssoUser['phone'] ?? ($ssoUser['no_hp'] ?? null),
                'gender' => $ssoUser['gender'] ?? ($ssoUser['jenis_kelamin'] ?? null),
                'birthdate' => $ssoUser['birthdate'] ?? ($ssoUser['tanggal_lahir'] ?? null),
                // ✅ Field role - default ke 'user' jika tidak ada
                'role' => isset($ssoUser['role']) 
                          ? (in_array($ssoUser['role'], ['admin', 'user']) ? $ssoUser['role'] : 'user')
                          : 'user',
            ]
        );

        // Simpan device information (jika tabel user_devices ada)
        try {
            if (class_exists(\App\Models\UserDevice::class)) {
                \App\Models\UserDevice::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'device_uuid' => $request->device_uuid,
                    ],
                    [
                        'app_id' => $request->app_id ?? config('services.sso.app_id'),
                        'device_name' => $request->device_name,
                        'platform' => $request->platform,
                        'last_login_at' => now(),
                    ]
                );
            }
        } catch (\Exception $e) {
            \Log::warning('Gagal menyimpan device info: ' . $e->getMessage());
            // Lanjutkan meskipun gagal menyimpan device
        }

        return response()->json([
            'success' => true,
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'token_type' => $data['token_type'] ?? 'Bearer',
            'expires_in' => $data['expires_in'] ?? null,
            'user' => $this->formatUser($user),
        ]);

    } catch (\Exception $e) {
        \Log::error('SSO Login Error: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'success' => false,
            'message' => 'Gagal terhubung ke server autentikasi',
            'error' => app()->environment('local') ? $e->getMessage() : null
        ], 500);
    }
}

    public function logout()
    {
        // Karena stateless, logout cukup dilakukan di frontend
        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->auth_user;

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
        $user = $request->auth_user;

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
            'role' => $user->role?->name ?? 'user', 
            'role_id' => $user->role_id, // ✅ Opsional: kirim juga id-nya
            'phone' => $user->phone,
            'gender' => $user->gender,
            'birthdate' => $user->birthdate,
            'points' => $user->points ?? 0,
            'avatar_url' => $user->avatar ? Storage::url($user->avatar) : null,
            'created_at' => $user->created_at,
        ];
    }
}