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
        $employees = EmployeeProfile::select('employee_profiles.*', 'd.probation')
            ->join('assigned_areas as aa', 'aa.employee_profile_id', '=', 'employee_profiles.id')
            ->join('designations as d', 'd.id', '=', 'aa.designation_id')
            ->get()
            ->filter(function ($employee) {
                /**
                 * Employees that is not under probation
                 */
                $probationMonths = (int) $employee->probation;
                return $employee->date_hired >= Carbon::now()->subMonths($probationMonths);
            });

        $special_privilege_leave = LeaveType::where('code', 'SPL')->first();

        /**
         * Reset SPL credit of employees base on annual credit per annual a person may get in a SPL
         */
        foreach($employees as $employee){
            $employee_leave_credit = EmployeeLeaveCredit::where('employee_profile_id', $employee->id)
                ->where('leave_type_id', $special_privilege_leave->id)->first();

            $current_credit = $employee_leave_credit->total_leave_credits;

            $employee_leave_credit -> update([
                'total_leave_credits' => $special_privilege_leave->annual_credit
            ]);

            EmployeeLeaveCreditLogs::create([
                'employee_leave_credit_id' => $employee_leave_credit->id,
                'previous_credit' => $current_credit,
                'leave_credits' => $special_privilege_leave->annual_credit,
                'reason' => "Annual SPL Credits",
                'action' => "add"
            ]);
        }
    }
}
