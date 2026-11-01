<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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
        $this->configureGates();
        $this->configureRateLimiting();
    }

    /**
     * Role-based permissions mirroring the legacy EMS / TMS / PMS responsibilities.
     */
    protected function configureGates(): void
    {
        Gate::define('manage-users', fn (User $user): bool => $user->hasRole(UserRole::Admin));

        Gate::define('manage-employees', fn (User $user): bool => $user->hasRole(
            UserRole::Admin, UserRole::HrTimekeeper, UserRole::HrPayroll,
        ));

        Gate::define('manage-timekeeping', fn (User $user): bool => $user->hasRole(
            UserRole::Admin, UserRole::HrTimekeeper,
        ));

        Gate::define('manage-payroll', fn (User $user): bool => $user->hasRole(
            UserRole::Admin, UserRole::HrPayroll, UserRole::Finance,
        ));

        Gate::define('endorse-filings', fn (User $user): bool => $user->employee?->headedDepartments()->exists() ?? false);
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)->by(
            Str::transliterate(Str::lower($request->string('email')).'|'.$request->ip()),
        ));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Hostinger terminates SSL at its proxy; always generate https:// links in production.
        URL::forceHttps(app()->isProduction());

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
