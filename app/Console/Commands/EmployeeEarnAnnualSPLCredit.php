<?php

namespace App\Console\Commands;

use App\Models\EmployeeLeaveCredit;
use App\Models\EmployeeLeaveCreditLogs;
use App\Models\EmployeeProfile;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EmployeeEarnAnnualSPLCredit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:employee-earn-annual-s-p-l-credit';

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
         * This task will trigger every 5th day of january
         * This task is intended for Special Privilege Leave
         */

        $employees = EmployeeProfile::where('date_hired', Carbon::now()->subMonths(6))->get();
        $special_privilege_leave = LeaveType::where('code', 'SPL')->first();

        foreach($employees as $employee){
            $employee_leave_credit = EmployeeLeaveCredit::where('employee_profile_id', $employee->id)
                ->where('leave_type_id', $special_privilege_leave->id)->first();

            $current_credit = $employee_leave_credit->total_leave_credits;

            $employee_leave_credit -> update([
                'total_leave_credits' => $special_privilege_leave->annual_value
            ]);

            EmployeeLeaveCreditLogs::create([
                'employee_leave_credit_id' => $employee_leave_credit->id,
                'previous_credit' => $current_credit,
                'leave_credits' => $special_privilege_leave->annual_value,
                'reason' => "SPL Annual Earned Credit."
            ]);
        }
    }
}
