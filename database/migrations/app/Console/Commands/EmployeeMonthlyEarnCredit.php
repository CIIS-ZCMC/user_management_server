<?php

namespace App\Console\Commands;

use App\Helpers\Helpers;
use App\Http\Resources\NotificationResource;
use App\Models\EmployeeLeaveCredit;
use App\Models\EmployeeLeaveCreditLogs;
use App\Models\Notifications;
use App\Models\UserNotifications;
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
        $sick_leave = LeaveType::where('code', 'SL')->first();
        $vacation_leave = LeaveType::where('code', 'VL')->first();
        $force_leave = LeaveType::where('code', 'FL')->first();

        $month_before = Carbon::now()->subMonth()->format('F');;

        $employees = EmployeeProfile::where('employment_type_id', '!=', 5)
            ->where('id', '!=', 1)
            ->where('date_hired', '<', Carbon::now()->subDays(30))
            ->get();

        foreach ($employees as $employee) {
            /**
             * Sick Leave
             */
            $sick_leave_credit = EmployeeLeaveCredit::where('employee_profile_id', $employee->id)
                ->where('leave_type_id', $sick_leave->id)->first();

            $sick_leave_current_credit = $sick_leave_credit->total_leave_credits;
            $sick_monthly_value = $sick_leave->month_value;
            if ($employee->employmentType->name === 'Permanent Part-time') {
                $sick_monthly_value = $sick_leave->month_value / 2;
            }
            $sick_leave_credit->increment('total_leave_credits', $sick_monthly_value);

            EmployeeLeaveCreditLogs::create([
                'employee_leave_credit_id' => $sick_leave_credit->id,
                'previous_credit' => $sick_leave_current_credit,
                'leave_credits' =>  $sick_monthly_value,
                'reason' => "Monthly Sick Leave Credits",
                'action' => "add"
            ]);

            $title = "Sick Leave credited";
            $description = $sick_monthly_value . " sick leave credits is credited for the month of " . $month_before . ".";

            $notification = Notifications::create([
                "title" => $title,
                "description" => $description,
                "module_path" => '/leave-applications',
            ]);

            $user_notification = UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => $employee->id,
            ]);

            Helpers::sendNotification([
                "id" => $employee->employee_id,
                "data" => new NotificationResource($user_notification)
            ]);

            /**
             * Vacation Leave
             */

            $vacation_leave_credit = EmployeeLeaveCredit::where('employee_profile_id', $employee->id)
                ->where('leave_type_id', $vacation_leave->id)->first();

            $vacation_leave_current_credit = $vacation_leave_credit->total_leave_credits;
            $vl_monthly_value = $vacation_leave->month_value;
            if ($employee->employmentType->name === 'Permanent Part-time') {
                $vl_monthly_value = $vacation_leave->month_value / 2;
            }

            $vacation_leave_credit->increment('total_leave_credits', $vl_monthly_value);
            EmployeeLeaveCreditLogs::create([
                'employee_leave_credit_id' => $vacation_leave_credit->id,
                'previous_credit' => $vacation_leave_current_credit,
                'leave_credits' => $vl_monthly_value,
                'reason' => "Monthly Vacation Leave Credits",
                'action' => "add"
            ]);
            $title = "Vacation Leave credited";
            $description = $vl_monthly_value . " vacation leave credits is credited for the month of " . $month_before . ".";

            $notification = Notifications::create([
                "title" => $title,
                "description" => $description,
                "module_path" => '/leave-applications',
            ]);

            $user_notification = UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => $employee->id,
            ]);

            Helpers::sendNotification([
                "id" => $employee->employee_id,
                "data" => new NotificationResource($user_notification)
            ]);

            /**
             * Force Leave
             */

            // if ($vacation_leave_credit->total_leave_credits >= 10) {

            //     $force_leave_credit = EmployeeLeaveCredit::where('employee_profile_id', $employee->id)
            //         ->where('leave_type_id', $force_leave->id)->first();

            //     $force_leave_current_credit = $force_leave_credit->total_leave_credits;
            //     $fl_annual_value = $force_leave->annual_credit;
            //     if ($employee->employmentType->name === 'Permanent Part-time') {
            //         $fl_annual_value = $force_leave->annual_credit / 2;
            //     }
            //     $log_entry_exists = EmployeeLeaveCreditLogs::join('employee_leave_credits', 'employee_leave_credit_logs.employee_leave_credit_id', '=', 'employee_leave_credits.id')
            //         ->where('employee_leave_credits.employee_profile_id', $employee->id)
            //         ->where('employee_leave_credits.leave_type_id', $force_leave->id)
            //         ->where(function ($query) {
            //             $query->where('employee_leave_credit_logs.reason', 'Annual Forced Leave Credits')
            //                   ->orWhere('employee_leave_credit_logs.reason', 'Update Credits');
            //         })
            //         ->whereYear('employee_leave_credit_logs.created_at', now()->year)
            //         ->exists();

            //     if (!$log_entry_exists) {
            //         $force_leave_credit->increment('total_leave_credits', $fl_annual_value);
            //         // Log the leave credit increment
            //         EmployeeLeaveCreditLogs::create([
            //             'employee_leave_credit_id' => $force_leave_credit->id,
            //             'reason' => 'Annual Forced Leave Credits',
            //             'action' => "add",
            //             'previous_credit' => $force_leave_current_credit,
            //             'leave_credits' => $fl_annual_value,

            //         ]);
            //     }

            //     $title = "Forced Leave credited";
            //     $description = "You now have ".$fl_annual_value." forced leave credits credited for year ".now()->year." .";

            //     $notification = Notifications::create([
            //         "title" => $title,
            //         "description" => $description,
            //         "module_path" => '/leave-applications',
            //     ]);

            //     $user_notification = UserNotifications::create([
            //         'notification_id' => $notification->id,
            //         'employee_profile_id' => $employee->id,
            //     ]);

            //     Helpers::sendNotification([
            //         "id" => $employee->employee_id,
            //         "data" => new NotificationResource($user_notification)
            //     ]);
            // }

        }
    }
}
