<?php
// This file was missing, so we create Kernel.php for registering middleware
namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        // ...existing middleware...
        'auth.sso' => \App\Http\Middleware\AuthenticateWithSSO::class,
        'is.admin' => \App\Http\Middleware\EnsureAdmin::class,
    ];
}