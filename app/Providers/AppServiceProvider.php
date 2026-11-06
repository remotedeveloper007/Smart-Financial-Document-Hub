<?php

namespace App\Providers;

use App\Contracts\MalwareScanner;
use App\Services\Billing\PaymentProviderManager;
use App\Services\Billing\Providers\PayPalPaymentProvider;
use App\Services\Billing\Providers\RazorpayPaymentProvider;
use App\Services\Billing\Providers\StripePaymentProvider;
use App\Services\Security\ClamAvScanner;
use App\Services\Tenancy\TeamContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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
        $this->app->bind(MalwareScanner::class, ClamAvScanner::class);
        $this->app->scoped(TeamContext::class);
        $this->app->tag([
            StripePaymentProvider::class,
            RazorpayPaymentProvider::class,
            PayPalPaymentProvider::class,
        ], 'billing.payment-providers');
        $this->app->singleton(
            PaymentProviderManager::class,
            fn ($app) => new PaymentProviderManager($app->tagged('billing.payment-providers')),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        RateLimiter::for('ai', function (Request $request): Limit {
            $user = $request->user();
            $key = $user
                ? "team:{$user->team_id}:user:{$user->id}"
                : $request->ip();

            return Limit::perMinute((int) config('ai.requests_per_minute', 10))->by($key);
        });

        // Share authenticated user and simple authorization flags with Inertia
        Inertia::share([
            'auth' => function () {
                return [
                    'user' => Auth::user() ? Auth::user()->only(['id', 'name', 'email', 'team_id']) : null,
                ];
            },
            'can' => function () {
                return [
                    'isPlatformAdmin' => Gate::allows('viewPlatformAdmin'),
                    'viewAdmin' => Gate::allows('viewAdmin'),
                    'viewHorizon' => Gate::allows('viewHorizon'),
                ];
            },
        ]);
    }
}
