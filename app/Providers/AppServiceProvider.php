<?php

namespace App\Providers;

use Carbon\Carbon;
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
        Vite::prefetch(concurrency: 3);
        Inertia::share([
            'permissions' => function () {
                return session('permissions', []);
            },
            'appName' => config('app.name'),
            'appVersion' => config('app.version'),
        ]);

        Carbon::serializeUsing(function ($carbon) {
            return $carbon->format('Y-m-d H:i:s');
        });
    }
}
