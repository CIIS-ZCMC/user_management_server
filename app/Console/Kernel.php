<?php

namespace App\Console;

use App\Console\Commands\EmployeeEarnAnnualSPLCredit;
use App\Console\Commands\EmployeeMonthlyEarnCredit;
use App\Console\Commands\EmployeeSixMonthEarnSPLCredit;
use App\Console\Commands\ProcessApprovedOvertimeCredits;
use App\Console\Commands\ProcessExpiredOvertimeCredits;
use App\Console\Commands\ProcessUndertimeMonthly;
use App\Console\Commands\RemoveOicLeaveApplication;
use App\Console\Commands\SchedulerTask;
use App\Console\Commands\UpdateOicLeaveApplication;
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
        $schedule->command('app:scheduler-task')->monthly();

        // $schedule->command('app:c-t-o-expiration')->when(function () {
        //     return now()->month == 12 && now()->day == 25;
        // })->daily();

        $schedule->command('app:task-scheduler')->dailyAt('5:00');

        $schedule->command(ProcessExpiredOvertimeCredits::class)->yearly()->when(function () {
            return now()->month == 1 && now()->day == 1;
        });

        $schedule->command(ProcessApprovedOvertimeCredits::class)->monthly()->when(function () {
            return now()->day == 1;
        });

        $schedule->command(ProcessUndertimeMonthly::class)->monthly()->when(function () {
            return now()->day == 1;
        });

        $schedule->command(EmployeeMonthlyEarnCredit::class)->monthly()->when(function () {
            return now()->day == 1;
        });

        $schedule->command(EmployeeSixMonthEarnSPLCredit::class)->daily();
        // Run UpdateOicLeaveApplication every day at 12:00 AM
        $schedule->command(UpdateOicLeaveApplication::class)->dailyAt('00:00');
        // Run RemoveOicLeaveApplication every day at 11:59 PM
        $schedule->command(RemoveOicLeaveApplication::class)->dailyAt('23:59');



        // $schedule->command(EmployeeMonthlyEarnCredit::class)->runInBackground();
        $schedule->command(EmployeeSixMonthEarnSPLCredit::class)->runInBackground();

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
