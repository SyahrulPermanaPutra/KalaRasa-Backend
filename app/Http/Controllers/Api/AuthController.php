<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(6)],
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:L,P',
            'birth_date' => 'nullable|date',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'gender' => $request->gender,
            'birth_date' => $request->birth_date,
            'role' => 'user',
            'email_verified_at' => null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;
        
        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil',
            'data' => [
                'user' => $this->formatUserResponse($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 201);
    }

    public function login(Request $request)
    {

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_uuid' => 'required',
            'device_name' => 'required',
            'platform' => 'required',
            // 'app_id' tidak perlu divalidasi dari input user
        ]);

        $ssoBase = env('API_SSO_URL');
        if (empty($ssoBase)) {
            return back()->withErrors(['login' => 'SSO API URL belum dikonfigurasi. Silakan set API_SSO_URL di .env']);
        }

        $loginUrl = rtrim($ssoBase, '/') . '/api/login';

        $response = Http::post($loginUrl, [
            'email' => $request->email,
            'password' => $request->password,
            'device_uuid' => $request->device_uuid,
            'device_name' => $request->device_name,
            'platform' => $request->platform,
            'app_id' => env('APP_ID'),
        ]);

        if ($response->successful() && isset($response['access_token'])) {
            session(['access_token' => $response['access_token']]);
            session(['refresh_token' => $response['refresh_token'] ?? null]);
            // Simpan data user dari response SSO ke session jika tersedia
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
                // ✅ UBAH: Role langsung dari kolom, bukan relasi
                // Ambil role dari SSO, default 'user' jika tidak ada
                $role = $sso['role'] ?? 'user';
                
                // Validasi role hanya 'admin' atau 'user'
                if (!in_array($role, ['admin', 'user'])) {
                    $role = 'user'; // Default ke user jika role tidak valid
                }
                
                $user->role = $role;
                $user->save();

                $user->save();

                // If user has no role, assign default 'customer'
                // if (empty($user->role_id)) {
                //     $customerRole = Role::firstOrCreate(
                //         ['name' => 'customer'],
                //         ['display_name' => 'Customer']
                //     );
                //     $user->role_id = $customerRole->id;
                //     $user->save();
                //     session(['user_role' => $customerRole->name]);
                // } else {
                //     // ensure session reflects assigned role
                //     if ($user->role) {
                //         session(['user_role' => $user->role->name]);
                //     }
                // }

                // Store sso id in session (now that it's available)
                session(['user_sso_id' => $ssoId]);

                // Set some handy session values
                session(['user_gender' => $gender]);
                session(['user_birthdate' => $birthdate]);
                session(['user_phone' => $phone]);
            }
            return redirect('/');
        } else {
            $error = $response->json('message') ?? 'Login gagal!';
            return back()->withErrors(['login' => $error]);
        }
    }

    public function logout(Request $request)
    {
        session()->forget(['access_token', 'refresh_token']);
        return redirect('/login');
    }

    public function profile(Request $request)
    {
        // Ambil user dari middleware auth.sso
        $user = $request->auth_user;
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Hitung total resep yang diapprove
        $approvedRecipesCount = \App\Models\Recipe::where('created_by', $user->id)
            ->where('status', 'approved')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'stats' => [
                    'points' => $user->points ?? 0,
                    'approved_recipes' => $approvedRecipesCount,
                    'point_per_recipe' => config('points.recipe_approved', 10)
                ]
            ]
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:L,P',
            'birth_date' => 'nullable|date',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->gender = $request->gender;
        $user->birth_date = $request->birth_date;

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Store new avatar
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $avatarPath;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil diupdate',
            'data' => $this->formatUserResponse($user)
        ]);
    }

    /**
     * Format user response dengan avatar URL
     */
    private function formatUserResponse($user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'birth_date' => $user->birth_date,
            'avatar' => $user->avatar,
            'avatar_url' => $user->avatar ? Storage::url($user->avatar) : null,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}