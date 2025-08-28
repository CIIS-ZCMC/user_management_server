<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Contracts\DailyTimeRecord\DailyTimeRecordRepositoryInterface;
use App\Contracts\Schedule\ScheduleRepositoryInterface;

use App\Repositories\DailyTimeRecord\DailyTimeRecordRepository;
use App\Repositories\Schedule\ScheduleRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(DailyTimeRecordRepositoryInterface::class, DailyTimeRecordRepository::class);
        $this->app->bind(ScheduleRepositoryInterface::class, ScheduleRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
