<?php

namespace App\Console\Commands;

use App\Helpers\Helpers;
use App\Models\EmployeeProfile;
use App\Models\EmployeeSchedule;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SchedulerTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scheduler-task';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'To generate next month schedule for administrative employees this will trigger every first day of the month.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try{
            $employees = EmployeeProfile::where('is_shifting', 0)->get();
            $next_month_schedules = Helpers::generateSchedule(Carbon::now()->addMonth()->startOfMonth());

            foreach($employees as $employee){
                foreach($next_month_schedules as $schedule){
                    EmployeeSchedule::create([
                        'employee_profile_id' => $employee->id,
                        'schedule_id' => $schedule->id
                    ]);
                }
            }
            
            Helpers::infoLog('SchedulerTask', 'PASSED', "Next Month Schedule Generated.");
        }catch(\Exception $e){
            Helpers::errorLog('SchedulerTask', 'FAILED', $e->getMessage());
        }
    }
}
