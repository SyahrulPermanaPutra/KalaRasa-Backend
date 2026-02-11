<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    | - Simpan device per app
    | - Revoke token lama (device + app saja)
    | - Minta token baru ke Passport (server 8001)
    */

    public function login(Request $request)
    {
        $request->validate([
            'email'        => 'required|email',
            'password'     => 'required',
            'device_uuid'  => 'required',
            'device_name'  => 'required',
            'platform'     => 'required',
            'app_id'       => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah'
            ], 401);
        }

        /*
        |--------------------------------------------------
        | 1. SIMPAN DEVICE PER APP
        |--------------------------------------------------
        */
        UserDevice::updateOrCreate(
            [
                'user_id'     => $user->id,
                'device_uuid' => $request->device_uuid,
                'app_id'      => $request->app_id
            ],
            [
                'device_name'   => $request->device_name,
                'platform'      => $request->platform,
                'last_login_at' => now()
            ]
        );

        /*
        |--------------------------------------------------
        | 2. REVOKE TOKEN LAMA (DEVICE + APP SAJA)
        |--------------------------------------------------
        */
        $tokenName = $request->device_uuid . '_' . $request->app_id;

        DB::table('oauth_access_tokens')
            ->where('user_id', $user->id)
            ->where('name', $tokenName)
            ->update(['revoked' => true]);

        /*
        |--------------------------------------------------
        | 3. MINTA TOKEN KE PASSPORT (SERVER 8001)
        |--------------------------------------------------
        */
        $http = app()->make(\GuzzleHttp\Client::class);

        try {
            $response = $http->post('http://127.0.0.1:8002/oauth/token', [
                'timeout' => 5,
                'form_params' => [
                    'grant_type'    => 'password',
                    'client_id'     => env('PASSPORT_PASSWORD_CLIENT_ID'),
                    'client_secret' => env('PASSPORT_PASSWORD_CLIENT_SECRET'),
                    'username'      => $request->email,
                    'password'      => $request->password,
                    'scope'         => '',
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);

            /*
            |--------------------------------------------------
            | 4. TAG TOKEN DENGAN DEVICE + APP
            |--------------------------------------------------
            */
            DB::table('oauth_access_tokens')
                ->where('id', $data['access_token'])
                ->update([
                    'name' => $tokenName
                ]);

            return response()->json([
                'user'          => $user,
                'access_token'  => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'token_type'    => $data['token_type'],
                'expires_in'    => $data['expires_in'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Login gagal',
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ME
    |--------------------------------------------------------------------------
    */

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LIST DEVICES (PER DEVICE + PER APP)
    |--------------------------------------------------------------------------
    */

public function devices(Request $request)
{
    $user = $request->user();
    $currentTokenId = optional($user->token())->id;

    $devices = UserDevice::where('user_id', $user->id)
        ->orderByDesc('last_login_at')
        ->get()
        ->map(function ($device) use ($user, $currentTokenId) {

            $tokenName = $device->device_uuid . '_' . $device->app_id;

            $token = DB::table('oauth_access_tokens')
                ->where('user_id', $user->id)
                ->where('name', $tokenName)
                ->where('revoked', false)
                ->latest('created_at')
                ->first();

            return [
                'device_uuid'        => $device->device_uuid,
                'app_id'             => $device->app_id,
                'device_name'        => $device->device_name,
                'platform'           => $device->platform,
                'last_login_at'      => $device->last_login_at,
                'token_created_at'   => $token->created_at ?? null,
                'token_id'           => $token->id ?? null,
                'is_current_device'  => ($token && $token->id == $currentTokenId)
            ];
        });

    return response()->json([
        'devices' => $devices
    ]);
}


    /*
    |--------------------------------------------------------------------------
    | LOGOUT CURRENT DEVICE
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Token tidak valid'
            ], 401);
        }

        $token = $user->token();

        if ($token) {
            $token->revoke();
        }

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LOGOUT DEVICE TERTENTU (PER APP)
    |--------------------------------------------------------------------------
    */

    public function logoutDevice(Request $request)
    {
        $request->validate([
            'device_uuid' => 'required',
            'app_id'      => 'required'
        ]);

        $user = $request->user();

        $tokenName = $request->device_uuid . '_' . $request->app_id;

        DB::table('oauth_access_tokens')
            ->where('user_id', $user->id)
            ->where('name', $tokenName)
            ->update(['revoked' => true]);

        UserDevice::where('user_id', $user->id)
            ->where('device_uuid', $request->device_uuid)
            ->where('app_id', $request->app_id)
            ->delete();

        return response()->json([
            'message' => 'Device berhasil logout'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | REFRESH TOKEN (SERVER 8001)
    |--------------------------------------------------------------------------
    */

    public function refreshToken(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required'
        ]);

        $http = app()->make(\GuzzleHttp\Client::class);

        try {
            $response = $http->post('http://127.0.0.1:8002/oauth/token', [
                'timeout' => 5,
                'form_params' => [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $request->refresh_token,
                    'client_id'     => env('PASSPORT_PASSWORD_CLIENT_ID'),
                    'client_secret' => env('PASSPORT_PASSWORD_CLIENT_SECRET'),
                    'scope'         => '',
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);

            return response()->json([
                'access_token'  => $data['access_token'],
                'refresh_token' => $data['refresh_token'],
                'token_type'    => $data['token_type'],
                'expires_in'    => $data['expires_in'] ?? null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Refresh token gagal',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
