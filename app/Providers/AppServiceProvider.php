<?php

namespace App\Providers;

use App\Contracts\MalwareScanner;
use App\Services\Security\ClamAvScanner;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MalwareScanner::class, ClamAvScanner::class);
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
                    'viewAdmin' => Gate::allows('viewAdmin'),
                    'viewHorizon' => Gate::allows('viewHorizon'),
                ];
            },
        ]);
    }
}
