<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
     ->withMiddleware(function (Middleware $middleware): void {
        // Daftarkan semua alias middleware di sini
        // $middleware->alias([
        //     'admin' => AdminMiddleware::class,
        // ]);

        // Register middleware alias
        $middleware->alias([
            // 'passport.validate' => \App\Http\Middleware\ValidatePassportToken::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
    