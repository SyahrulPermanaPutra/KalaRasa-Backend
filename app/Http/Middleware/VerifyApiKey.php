<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyApiKey
{
    /**
     * Handle an incoming request from NLP Python API
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key');
        
        if (!$apiKey) {
            return response()->json([
                'error' => 'API key missing'
            ], 401);
        }

        // Verify API key
        $validApiKey = env('LARAVEL_API_KEY', 'c2e8ebb9f3c6d1539a68f94094d1ab75b5e34697a1bbd9b5f037d660e1a1abc3');

        if ($apiKey !== $validApiKey) {
            return response()->json([
                'error' => 'Invalid API key'
            ], 401);
        }

        return $next($request);
    }
}