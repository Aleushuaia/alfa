<?php

namespace App\Providers;

use App\Repositories\DashboardRepository;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar el DashboardRepository como singleton
        $this->app->singleton(DashboardRepository::class, function ($app) {
            return new DashboardRepository();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forzar HTTPS en producción
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
