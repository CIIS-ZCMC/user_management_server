<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
class ResetLeaveCredits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-leave-credits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }

    protected function schedule(Schedule $schedule)
        {
            $schedule->call('App\Http\Controllers\LeaveCreditsController@addYearlyLeaveCredit')->yearlyOn(1, 1);
            $schedule->call('App\Http\Controllers\LeaveCreditsController@resetYearlyLeaveCredit')->yearlyOn(1, 1);
            $schedule->call('App\Http\Controllers\LeaveCreditsController@addMonthlyLeaveCredit')->monthlyOn(1, '00:00');

            $schedule->call('App\Http\Controllers\OvertimeApplicationController@resetYearlyOvertimeCredit')->yearlyOn(1, 1);
            $schedule->call('App\Http\Controllers\EmployeeOvertimeCreditController@store')->monthlyOn(1, '00:00');
        }

}
