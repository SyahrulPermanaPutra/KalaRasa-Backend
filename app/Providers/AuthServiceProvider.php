<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot()
    {
        $this->registerPolicies();

        // Register Passport routes if the package and method are available.
        if (class_exists(\Laravel\Passport\Passport::class) && method_exists(\Laravel\Passport\Passport::class, 'routes')) {
            \Laravel\Passport\Passport::routes();
        }
    }
}
