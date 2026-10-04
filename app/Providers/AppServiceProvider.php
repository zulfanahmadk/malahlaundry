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
            if (!array_key_exists('store', $view->getData())) {
                $view->with('store', app(\App\Services\StoreConfiguration::class)->read());
            }
        });
        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            $view->with('branches', \App\Models\Branch::orderBy('name')->get(['id', 'name', 'store_name', 'active']));
            $view->with('workspaceNotifications', app(\App\Services\OwnerNotifications::class)->all());
        });
    }
}
