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
                $view->with('store', $view->name() === 'layouts.app' && auth()->user()?->isAdmin() ? ['name' => 'Malah Laundry'] : app(\App\Services\StoreConfiguration::class)->read());
            }
        });
        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            if (auth()->user()?->isAdmin()) {
                $view->with('workspaceNotifications', collect());
                $view->with('newTicketCount', \App\Models\SupportTicket::where('status', 'SUBMITTED')->count());
                return;
            }
            $view->with('branches', \App\Models\Branch::orderBy('name')->get(['id', 'name', 'store_name', 'active']));
            $view->with('workspaceNotifications', app(\App\Services\OwnerNotifications::class)->all());
        });
    }
}
