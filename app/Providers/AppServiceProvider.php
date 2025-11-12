<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AsanaService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        
        $this->app->singleton(AsanaService::class, function ($app) {
            return new AsanaService();
        });

        
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
