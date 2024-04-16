<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;


class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('app:pull-d-t-r')->everyTenSeconds();
        $schedule->command('app:pull-d-t-r')->everyThreeMinutes();
        $schedule->command('app:backup-d-t-r')->everyThirtyMinutes();

        $schedule->command('app:c-t-o-expiration')->when(function () {
            return now()->month == 12 && now()->day == 25;
        })->daily();
        
        $schedule->command('app:task-scheduler')->dailyAt('5:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
