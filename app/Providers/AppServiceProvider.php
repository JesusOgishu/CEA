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
        // 🔥 FORZAR LAZY LOADING PARA ASANA SERVICE 🔥
        // Esto asegura que auth()->user() se resuelva ANTES de construir el servicio,
        // garantizando que el constructor obtenga al usuario correcto.
        $this->app->singleton(AsanaService::class, function ($app) {
            return new AsanaService();
        });

        // ... cualquier otro binding que tengas ...
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
