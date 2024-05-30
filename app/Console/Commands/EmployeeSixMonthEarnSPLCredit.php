<?php

namespace App\Console\Commands;

use App\Helpers\Helpers;
use App\Http\Resources\NotificationResource;
use App\Models\EmployeeLeaveCredit;
use App\Models\EmployeeLeaveCreditLogs;
use App\Models\EmployeeProfile;
use App\Models\LeaveType;
use App\Models\Notifications;
use App\Models\UserNotifications;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EmployeeSixMonthEarnSPLCredit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:employee-six-month-earn-s-p-l-credit';

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
        $employees = EmployeeProfile::where('employment_type_id', '!=', 5)->where('id', '!=', 1)->get();
        // Get the SPL leave type
        $special_privilege_leave = LeaveType::where('code', 'SPL')->first();

        foreach ($employees as $employee) {
                // Calculate the date 6 months after the employee's hire date
                $six_months_after_hire = Carbon::parse($employee->date_hired)->addMonths(6);

                // Check if the current date is after or equal to the 6-month interval from the hire date
                if (Carbon::now()->isSameDay($six_months_after_hire) || Carbon::now()->gt($six_months_after_hire)) {
                    // Check if SPL credits have already been given for the current interval
                    $currentYear = Carbon::now()->year;
                    $spls_given = EmployeeLeaveCreditLogs::whereHas('employeeLeaveCredit', function ($query) use ($employee, $six_months_after_hire) {
                        $query->where('employee_profile_id', $employee->id);
                    })
                    ->whereYear('created_at', $currentYear)// Check within the current 6-month interval
                    ->where('reason', 'Annual SPL Credits')
                    ->exists();

                    if (!$spls_given) {
                        // Add SPL credits only if they haven't been given already within the current interval

                        // Check if the employee already has SPL leave credits
                        $employee_leave_credit = EmployeeLeaveCredit::where('employee_profile_id', $employee->id)
                            ->where('leave_type_id', $special_privilege_leave->id)->first();

                        if (!$employee_leave_credit) {
                            // If the employee doesn't have SPL leave credits, create new records
                            $employee_leave_credit = EmployeeLeaveCredit::create([
                                'employee_profile_id' => $employee->id,
                                'leave_type_id' => $special_privilege_leave->id,
                                'total_leave_credits' => $special_privilege_leave->annual_credit
                            ]);
                        } else {
                            // If the employee already has SPL leave credits, update the existing record
                            $current_credit = $employee_leave_credit->total_leave_credits;

                            $employee_leave_credit->update([
                                'total_leave_credits' => $current_credit + $special_privilege_leave->annual_credit
                            ]);
                        }

                        // Create a log entry for the added SPL credits
                        EmployeeLeaveCreditLogs::create([
                            'employee_leave_credit_id' => $employee_leave_credit->id,
                            'previous_credit' => $current_credit ?? 0,
                            'leave_credits' => $special_privilege_leave->annual_credit,
                            'reason' => "Annual SPL Credits",
                            'action' => "add"
                        ]);

                        
                        $title = "Special Privilege Leave credited";
                        $description = "Your SPL credits is now credited for year ". $currentYear." .";
                        
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
                    }

                }
        }
    }
}
