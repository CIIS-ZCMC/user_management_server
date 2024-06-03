<?php

namespace App\Console\Commands;

use App\Models\EmployeeOvertimeCredit;
use App\Models\EmployeeProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class EmployeeOvertimeCreditResetMonthly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:employee-overtime-credit-reset-monthly';

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
        /**
         * Reset Overtime Credit 
         */
        $employees = EmployeeProfile::where('deactivated_at', null)->get();

        foreach ($employees as $employee) {
            $employee_overtime_credit = EmployeeOvertimeCredit::where('employee_profile_id', $employee->id)->first();
            $latest_used_credit_by_hour_annual = $employee_overtime_credit->used_credit_by_hour + $employee_overtime_credit->earned_credit_by_hour;
            $employee_overtime_credit
                ->update([
                    'used_credit_by_hour' => $latest_used_credit_by_hour_annual,
                    'earned_credit_by_hour' => 0
                ]);
        }
    }
}
