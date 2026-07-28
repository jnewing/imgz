<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    /**
     * Define the upload rate limiter: a short burst limit plus a daily count
     * quota, keyed by user (authenticated) or IP (guest).
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('uploads', function (Request $request): array {
            $user = $request->user();
            $limits = config('imgz.throttle.'.($user ? 'user' : 'guest'));
            $key = $user ? 'user:'.$user->id : 'ip:'.$request->ip();

            return [
                Limit::perMinute((int) $limits['per_minute'])->by("uploads:min:{$key}"),
                Limit::perDay((int) $limits['per_day'])->by("uploads:day:{$key}"),
            ];
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
