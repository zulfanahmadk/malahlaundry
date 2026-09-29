<?php

namespace App\Providers;

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
        \Illuminate\Support\Facades\View::composer(['layouts.app', 'nota.public', 'auth.login'], function ($view) {
            $view->with('store', app(\App\Services\StoreConfiguration::class)->read());
        });
    }
}
