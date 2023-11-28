<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

// use App\Models\PositionSystemRole;
// use App\Observers\PositionSystemRoleObserver;

use App\Services\RequestLogger;
use App\Services\FileValidationAndUpload;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RequestLogger::class, function ($app) {
            return new RequestLogger();
        });
        
        $this->app->singleton(FileValidationAndUpload::class, function ($app) {
            return new FileValidationAndUpload();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
