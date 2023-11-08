<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

// use App\Models\PositionSystemRole;
// use App\Observers\PositionSystemRoleObserver;


use App\Services\RequestLogger;

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
        $this->app->singleton(RequestLogger::class, function ($app) {
            return new RequestLogger();
        });

        Schema::defaultStringLength(191);
        // PositionSystemRole::observe(PositionSystemRoleObserver::class);
    }
}
