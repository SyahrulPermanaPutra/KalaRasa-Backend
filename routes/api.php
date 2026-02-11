<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh-token', [AuthController::class, 'refreshToken']);

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/devices', [AuthController::class, 'devices']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-device', [AuthController::class, 'logoutDevice']);
});

