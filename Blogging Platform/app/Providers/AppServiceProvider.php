<?php

namespace App\Providers;

use App\Auth\CachedUserProvider;
use Illuminate\Support\Facades\Auth;
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
        Auth::provider('cached_eloquent', function ($app, array $config) {
            return new CachedUserProvider(
                $app['hash'],
                $config['model'],
                $config['cache_store'] ?? null,
                $config['cache_ttl'] ?? 300
            );
        });
    }
}
