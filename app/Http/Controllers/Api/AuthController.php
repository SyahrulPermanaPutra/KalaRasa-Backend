<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

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
            Log::error('Access token tidak ditemukan', ['response' => $data]);
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
        
        // GANTI DENGAN INI - Hanya simpan data user tanpa token
        if (isset($response['user'])) {
                $sso = $response['user'];

                session(['user_name' => $sso['name'] ?? 'User']);
                session(['user_email' => $sso['email'] ?? 'user@example.com']);

                // Sync to database (create or update)
                $ssoId = $sso['id'] ?? null;
                $email = $sso['email'] ?? null;

                $phone = $sso['phone'] ?? $sso['phone_number'] ?? $sso['no_telp'] ?? null;
                $gender = $sso['gender'] ?? null;
                $birthdate = $sso['birthdate'] ?? null;

                $user = null;
                if ($ssoId) {
                    $user = User::where('sso_id', $ssoId)->first();
                }
                if (!$user && $email) {
                    $user = User::where('email', $email)->first();
                }

                if (!$user) {
                    $user = new User();
                }

                $user->sso_id = $ssoId ?? $user->sso_id;
                $user->name = $sso['name'] ?? $user->name;
                $user->email = $sso['email'] ?? $user->email;
                $user->phone = $phone ?? $user->phone;
                $user->gender = $gender ?? $user->gender;
                if ($birthdate) {
                    // try to store as Y-m-d; leave raw if not parseable
                    $user->birthdate = $birthdate;
                }
                $user->sso_raw = $sso;

                // assign role if provided by SSO
                if (!empty($sso['role'])) {
                    $roleName = $sso['role'];
                    $role = Role::firstOrCreate(['name' => $roleName]);
                    $user->role_id = $role->id;
                    session(['user_role' => $roleName]);
                }

                $user->save();

                // If user has no role, assign default 'customer'
                if (empty($user->role_id)) {
                    $customerRole = Role::firstOrCreate(
                        ['name' => 'customer'],
                        ['display_name' => 'Customer']
                    );
                    $user->role_id = $customerRole->id;
                    $user->save();
                    session(['user_role' => $customerRole->name]);
                } else {
                    // ensure session reflects assigned role
                    if ($user->role) {
                        session(['user_role' => $user->role->name]);
                    }
                }

                // Store sso id in session (now that it's available)
                session(['user_sso_id' => $ssoId]);

                // Set some handy session values
                session(['user_gender' => $gender]);
                session(['user_birthdate' => $birthdate]);
                session(['user_phone' => $phone]);
            }

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
        Log::error('Login error: ' . $e->getMessage());
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
        Log::warning('Logout SSO error: ' . $e->getMessage());
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
            $response = Http::post(rtrim($ssoBase, '/') . '/api/refresh-token', [
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
            Log::error('Refresh token error: ' . $e->getMessage());
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
        try {
            // =========================
            // 1️⃣ GET AUTHENTICATED USER (dari middleware)
            // =========================
            $user = $request->auth_user;

            // =========================
            // 2️⃣ VALIDASI INPUT
            // =========================
            $validated = $request->validate([
                'name'      => 'required|string|max:255',
                'phone'     => 'nullable|string|max:20',
                'address'   => 'nullable|string|max:255',
                'gender'    => 'nullable|in:pria,wanita',
                'birthdate' => 'nullable|date',
            ]);

            // =========================
            // 3️⃣ UPDATE KE SSO
            // =========================
            $token = $request->bearerToken();
            $ssoBase = rtrim(env('SSO_URL'), '/');

            $updateSSO = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json'
            ])->post($ssoBase . '/api/update-profile', $validated); // ✅ Ganti PUT → POST

            Log::info('SSO Update Profile', [
                'status' => $updateSSO->status(),
                'body'   => $updateSSO->body()
            ]);

            if (!$updateSSO->successful()) {
                $errorBody = $updateSSO->json();
                
                return response()->json([
                    'success' => false,
                    'message' => $errorBody['message'] ?? 'Gagal update ke SSO',
                    'errors'  => $errorBody['errors'] ?? null,
                ], $updateSSO->status());
            }

            // =========================
            // 4️⃣ UPDATE DATABASE LOKAL
            // =========================
            // Hanya update field yang dikirim
            $updateData = array_filter($validated, function ($key) use ($request) {
                return $request->has($key);
            }, ARRAY_FILTER_USE_KEY);

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            // =========================
            // 5️⃣ RETURN RESPONSE
            // =========================
            return response()->json([
                'success' => true,
                'message' => 'Profile berhasil diupdate',
                'data'    => $this->formatUser($user->fresh())
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Update Profile Error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function formatUser($user)
    {
        return [
            'id' => $user->id,
            'sso_id' => $user->sso_id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ?? 'user',
            'phone' => $user->phone,
            'gender' => $user->gender,
            'birthdate' => $user->birthdate,
            'points' => $user->points ?? 0,
            'created_at' => $user->created_at,
        ];
    }

    public function resetPassword(Request $request)
    {
        try {
            // =========================
            // 1️⃣ GET AUTHENTICATED USER (dari middleware)
            // =========================
            $user = $request->auth_user;

            // =========================
            // 2️⃣ VALIDASI INPUT
            // =========================
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => [
                    'required', 
                    'string', 
                    'confirmed', 
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{8,}$/'
                ],
            ], [
                'new_password.regex' => 'Minimal 8 karakter, 1 huruf kecil, 1 huruf kapital, dan 1 simbol.',
                'new_password.confirmed' => 'Konfirmasi password tidak cocok.',
            ]);

            // =========================
            // 3️⃣ RESET PASSWORD KE SSO
            // =========================
            $token = $request->bearerToken();
            $ssoBase = rtrim(env('SSO_URL'), '/');

            $resetSSO = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json'
            ])->post($ssoBase . '/api/reset-password', [
                'current_password' => $request->current_password,
                'new_password' => $request->new_password,
                'new_password_confirmation' => $request->new_password_confirmation,
            ]);

            Log::info('SSO Reset Password', [
                'status' => $resetSSO->status(),
                'body'   => $resetSSO->body()
            ]);

            if (!$resetSSO->successful()) {
                $errorBody = $resetSSO->json();
                
                return response()->json([
                    'success' => false,
                    'message' => $errorBody['message'] ?? 'Gagal mengubah password',
                    'errors'  => $errorBody['errors'] ?? null,
                ], $resetSSO->status());
            }

            // =========================
            // 4️⃣ RETURN SUCCESS RESPONSE
            // =========================
            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diubah'
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Reset Password Error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}