<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Carbon\Carbon;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    | - Simpan device per app
    | - Revoke token lama device+app
    | - Minta token baru ke Passport
    | - Tag token dengan device_uuid + app_id
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

        $tokenName = $request->device_uuid . '_' . $request->app_id;

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
        | 2. REVOKE TOKEN LAMA DEVICE + APP INI SAJA
        |--------------------------------------------------
        */
        DB::table('oauth_access_tokens')
            ->where('user_id', $user->id)
            ->where('name', $tokenName)
            ->update(['revoked' => true]);

        /*
        |--------------------------------------------------
        | 3. MINTA TOKEN BARU KE PASSPORT (SERVER 8002)
        |--------------------------------------------------
        */
        $http = app()->make(\GuzzleHttp\Client::class);

        try {
            $response = $http->post('http://127.0.0.1:8002/oauth/token', [
                'timeout' => 10,
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
            | 4. AMBIL TOKEN TERBARU MILIK USER
            |--------------------------------------------------
            */
            $latestToken = DB::table('oauth_access_tokens')
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->first();

            /*
            |--------------------------------------------------
            | 5. TAG TOKEN DENGAN device_uuid + app_id
            |--------------------------------------------------
            */
            if ($latestToken) {
                DB::table('oauth_access_tokens')
                    ->where('id', $latestToken->id)
                    ->update([
                        'name' => $tokenName
                    ]);
            }

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
                'error'   => $e->getMessage()
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
    | LIST DEVICES AKTIF
    |--------------------------------------------------------------------------
    */
    public function devices(Request $request)
    {
        $user = $request->user();
        $currentTokenId = $user->token()->id;

        $devices = DB::table('oauth_access_tokens as t')
            ->join('user_devices as d', function ($join) use ($user) {
                $join->on('d.device_uuid', '=', DB::raw("SUBSTRING_INDEX(t.name, '_', 1)"))
                     ->where('d.user_id', '=', $user->id);
            })
            ->where('t.user_id', $user->id)
            ->where('t.revoked', false)
            ->select(
                'd.device_uuid',
                'd.device_name',
                'd.platform',
                'd.app_id',
                'd.last_login_at',
                't.created_at as token_created_at',
                't.id as token_id'
            )
            ->orderByDesc('d.last_login_at')
            ->get()
            ->map(function ($device) use ($currentTokenId) {
                $device->is_current_device = $device->token_id == $currentTokenId;
                return $device;
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
        $token = $request->user()->token();

        if ($token) {
            $token->revoke();
        }

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LOGOUT DEVICE TERTENTU
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
    | REFRESH TOKEN
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
                'timeout' => 10,
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

    public function boot()
{
    $this->registerPolicies();

    Passport::tokensExpireIn(Carbon::now()->addMinutes(60));
    Passport::refreshTokensExpireIn(Carbon::now()->addDays(30));
}

}
