<?php

namespace App\Console\Commands;

use App\Models\EmployeeLeaveCredit;
use App\Models\EmployeeLeaveCreditLogs;
use Carbon\Carbon;
use App\Models\LeaveType;
use App\Models\EmployeeProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EmployeeMonthlyEarnCredit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:employee-monthly-earn-credit';

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
         * Employee Credit Earn for Sick leave And Vacation Leave
         * Employee May earn 15credits in a year, and credits will be given every 5th day of the month
         * where employee will earn credit of (annual credit/number of months)
         * Employee must be working in the company with 1 month old.
         */
        $sick_leave = LeaveType::where('code', 'SL')->first();
        $vacation_leave = LeaveType::where('code', 'VL')->first();
        $force_leave = LeaveType::where('code', 'FL')->first();

        $employees = EmployeeProfile::where('date_hired', '<', Carbon::now()->subDays(30))->get();

        foreach ($employees as $employee) {
            /**
             * Sick Leave
             */
            $sick_leave_credit = EmployeeLeaveCredit::where('employee_profile_id', $employee->id)
                ->where('leave_type_id', $sick_leave->id)->first();

            $sick_leave_current_credit = $sick_leave_credit->total_leave_credit;

            $sick_leave_credit->update([
                'total_leave_credits' => DB::raw("total_leave_credits + $sick_leave->monthly_value")
            ]);

            EmployeeLeaveCreditLogs::create([
                'employee_leave_credit_id' => $sick_leave_credit->id,
                'previous_credit' => $sick_leave_current_credit,
                'leave_credits' => $sick_leave->monthly_value
            ]);

            /**
             * Vacation Leave
             */
            $vacation_leave_credit = EmployeeLeaveCredit::where('employee_profile_id', $employee->id)
                ->where('leave_type_id', $vacation_leave->id)->first();

            $vacation_leave_current_credit = $vacation_leave_credit->total_leave_credit;

            $vacation_leave_credit->update([
                'total_leave_credits' => DB::raw("total_leave_credits + $vacation_leave->monthly_value")
            ]);

            EmployeeLeaveCreditLogs::create([
                'employee_leave_credit_id' => $vacation_leave_credit->id,
                'previous_credit' => $vacation_leave_current_credit,
                'leave_credits' => $vacation_leave->monthly_value
            ]);

            /**
             * Force Leave
             */

            if($vacation_leave_credit->total_leave_credit >= 10){
                $force_leave_credit = EmployeeLeaveCredit::where('employee_profile_id', $employee->id)
                ->where('leave_type_id', $force_leave->id)->first();

                $force_leave_current_credit = $force_leave_credit->total_leave_credit;

                $force_leave_credit->update([
                    'total_leave_credits' => $force_leave->annual_value
                ]);

                EmployeeLeaveCreditLogs::create([
                    'employee_leave_credit_id' => $force_leave_credit->id,
                    'previous_credit' => $force_leave_current_credit,
                    'leave_credits' => $force_leave->annual_value
                ]);
            }
        }
    }
}
