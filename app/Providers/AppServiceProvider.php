<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

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
        $this->configureRateLimiting();

        Vite::prefetch(concurrency: 3);
        Inertia::share([
            'flash' => function () {
                return session('flash') ?? [];
            }
        ]);
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('auth-submit', fn (Request $request) => [
            Limit::perMinute(5)->by($this->rateLimitKey($request, 'email')),
        ]);

        RateLimiter::for('otp-submit', fn (Request $request) => [
            Limit::perMinutes(5, 3)->by($this->rateLimitKey($request, 'email')),
        ]);

        RateLimiter::for('password-reset-submit', fn (Request $request) => [
            Limit::perMinutes(10, 3)->by($this->rateLimitKey($request, 'email')),
        ]);

        RateLimiter::for('uploads', fn (Request $request) => [
            Limit::perMinute(5)->by($this->rateLimitKey($request)),
        ]);

        RateLimiter::for('writes', fn (Request $request) => [
            Limit::perMinute(30)->by($this->rateLimitKey($request)),
        ]);

        RateLimiter::for('state-actions', fn (Request $request) => [
            Limit::perMinute(10)->by($this->rateLimitKey($request)),
        ]);

        RateLimiter::for('exports', fn (Request $request) => [
            Limit::perMinute(3)->by($this->rateLimitKey($request)),
        ]);

        RateLimiter::for('downloads', fn (Request $request) => [
            Limit::perMinute(30)->by($this->rateLimitKey($request)),
        ]);

        RateLimiter::for('public-api', fn (Request $request) => [
            Limit::perMinute(60)->by($request->ip()),
        ]);

        RateLimiter::for('sensitive-actions', fn (Request $request) => [
            Limit::perMinute(10)->by($this->rateLimitKey($request)),
        ]);
    }

    private function rateLimitKey(Request $request, ?string $input = null): string
    {
        $userKey = $request->user()?->getAuthIdentifier();
        $inputKey = $input ? strtolower((string) $request->input($input)) : null;

        return $userKey
            ? 'user:'.$userKey
            : 'guest:'.($inputKey ?: $request->ip());
    }
}
