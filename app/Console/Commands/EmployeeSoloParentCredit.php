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


class EmployeeSoloParentCredit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:employee-solo-parent-credit';

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
        $employees = EmployeeProfile::where('employment_type_id', '!=', 5)->where('id', '!=', 1)->where('solo_parent', '=', 1)->get();
        // Get the SPL leave type
        $solo_parent_leave = LeaveType::where('name', 'Solo Parent Leave')->first();

        foreach ($employees as $employee) {

            $twelve_months_after_hire = Carbon::parse($employee->date_hired)->addYear();

            // Check if the current date is after or equal to the 6-month interval from the hire date
            if (Carbon::now()->isSameDay($twelve_months_after_hire) || Carbon::now()->gt($twelve_months_after_hire)) {

                $currentYear = Carbon::now()->year;
                $spl_given = EmployeeLeaveCreditLogs::whereHas('employeeLeaveCredit', function ($query) use ($employee, $solo_parent_leave) {
                    $query->where('employee_profile_id', $employee->id)
                        ->where('id', $solo_parent_leave->id); // Check if employeeLeaveCredit ID is 3
                })
                    ->whereYear('created_at', now()->year) // Check within the current year
                    ->where(function ($query) {
                        $query->where('reason', 'Annual Solo Parent Credits')
                            ->orWhere('reason', 'Update Credits');
                    })
                    ->exists();


                if (!$spl_given) {
                    // Add SPL credits only if they haven't been given already within the current interval
                    // Check if the employee already has SPL leave credits
                    $employee_leave_credit = EmployeeLeaveCredit::where('employee_profile_id', $employee->id)
                        ->where('leave_type_id', $solo_parent_leave->id)->first();

                    if (!$employee_leave_credit) {
                        // If the employee doesn't have SPL leave credits, create new records
                        $employee_leave_credit = EmployeeLeaveCredit::create([
                            'employee_profile_id' => $employee->id,
                            'leave_type_id' => $solo_parent_leave->id,
                            'total_leave_credits' => $solo_parent_leave->annual_credit
                        ]);
                    } else {
                        // If the employee already has SPL leave credits, update the existing record
                        $current_credit = $employee_leave_credit->total_leave_credits;

                        $employee_leave_credit->update([
                            'total_leave_credits' => $current_credit + $solo_parent_leave->annual_credit
                        ]);
                    }

                    // Create a log entry for the added SPL credits
                    EmployeeLeaveCreditLogs::create([
                        'employee_leave_credit_id' => $employee_leave_credit->id,
                        'previous_credit' => $current_credit ?? 0,
                        'leave_credits' => $solo_parent_leave->annual_credit,
                        'reason' => "Annual Solo Parent Credits",
                        'action' => "add"
                    ]);


                    $title = "Solo Parent Leave credited";
                    $description = "Your SP credits is now credited for year " . $currentYear . " .";

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
