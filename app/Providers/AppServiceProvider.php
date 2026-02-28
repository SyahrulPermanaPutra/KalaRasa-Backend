<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default user ke user id 1 untuk kebutuhan auth
        \Illuminate\Support\Facades\Auth::viaRequest('default', function ($request) {
            return \App\Models\User::find(1);
        });
    }
}