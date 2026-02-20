<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind custom RegisterResponse to override default Fortify behavior
        $this->app->singleton(\Laravel\Fortify\Contracts\RegisterResponse::class, \App\Http\Responses\RegisterResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Override RegisteredUserController to prevent auto login after registration
        $this->app->extend(\Laravel\Fortify\Http\Controllers\RegisteredUserController::class, function ($controller, $app) {
            return new class($app[\Illuminate\Contracts\Auth\StatefulGuard::class]) extends \Laravel\Fortify\Http\Controllers\RegisteredUserController {
                public function store(\Illuminate\Http\Request $request, \Laravel\Fortify\Contracts\CreatesNewUsers $creator): \Laravel\Fortify\Contracts\RegisterResponse
                {
                    if (config('fortify.lowercase_usernames') && $request->has(\Laravel\Fortify\Fortify::username())) {
                        $request->merge([
                            \Laravel\Fortify\Fortify::username() => \Illuminate\Support\Str::lower($request->{\Laravel\Fortify\Fortify::username()}),
                        ]);
                    }
                    event(new \Illuminate\Auth\Events\Registered($user = $creator->create($request->all())));
                    // Do NOT login user here! Jangan regenerate session!
                    return app(\Laravel\Fortify\Contracts\RegisterResponse::class);
                }
            };
        });

        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();

        // Set flag session setelah register, dan pastikan user tidak auto login
        \Event::listen(\Laravel\Fortify\Events\Registered::class, function ($event) {
            // Logout user jika sudah login otomatis oleh Fortify
            if (auth()->check()) {
                auth()->logout();
            }
            // Set flag session untuk akses satu kali halaman sukses
            session(['register_success' => true]);
        });
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('pages::auth.login'));
        Fortify::verifyEmailView(fn () => view('pages::auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('pages::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('pages::auth.confirm-password'));
        Fortify::registerView(fn () => view('pages::auth.register'));
        Fortify::resetPasswordView(fn () => view('pages::auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('pages::auth.forgot-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
