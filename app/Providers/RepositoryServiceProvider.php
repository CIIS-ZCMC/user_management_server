<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Contracts\DailyTimeRecord\DailyTimeRecordRepositoryInterface;

use App\Repositories\DailyTimeRecord\DailyTimeRecordRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(DailyTimeRecordRepositoryInterface::class, DailyTimeRecordRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
