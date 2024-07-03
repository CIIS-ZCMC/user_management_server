<?php

namespace App\Console\Commands;

use App\Helpers\Helpers;
use App\Http\Resources\NotificationResource;
use App\Models\DailyTimeRecords;
use App\Models\EmployeeOvertimeCredit;
use App\Models\EmployeeOvertimeCreditLog;
use App\Models\EmployeeProfile;
use App\Models\Holiday;
use App\Models\Notifications;
use App\Models\OvertimeApplication;
use App\Models\UserNotifications;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessApprovedOvertimeCredits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-approved-overtime-credits';

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
        $currentMonth = date('m');
        $pastMonth = date('m', strtotime('-1 month'));
        $overtimeApplications = OvertimeApplication::where('status', 'approved')->with('activities', 'dates')->get();

        foreach ($overtimeApplications as $overtimeApplication) {
            if (isset($overtimeApplication->activities)) {

                // Check if the overtime application is approved
                foreach ($overtimeApplication->activities as $activity) {
                    foreach ($activity->dates as $date) {

                        // Check if the date is in the current month
                        if (date('m', strtotime($date->date)) == $pastMonth) {

                            // Iterate over employees before checking for matching biometric data
                            foreach ($date->employees as $employee) {

                                $overtimeFromTime = $date->time_from;
                                $overtimeToTime = $date->time_to;
                                $biometric_id = $employee->EmployeeProfile->biometric_id;
                                $matchingBiometrics = Helpers::getFirstInAndOutBiometric($biometric_id, $date->date, $overtimeFromTime, $overtimeToTime);

                                if ($matchingBiometrics) {
                                    $totalOverlapHours = $matchingBiometrics;
                                    // Convert the string representation of the date to a DateTime object
                                    $dateObject = Carbon::createFromFormat('Y-m-d', $date->date);

                                    // Format the date to 'm-d' format
                                    $dateFormatted = $dateObject->format('m-d');

                                    // Check if there is a holiday for the formatted month-day
                                    $isHoliday = Holiday::where('month_day', $dateFormatted)->exists();

                                    $overtimeRate = 1;

                                    if ($isHoliday) {
                                        $holiday = Holiday::where('month_day', $dateFormatted)->first();
                                        if ($holiday->isspecial == 1) {
                                            $effectiveDate = Carbon::createFromFormat('Y-m-d', $holiday->effectiveDate);
                                            if ($effectiveDate->isSameDay($dateObject)) {

                                                if ($dateObject->isWeekend()) {
                                                    $overtimeRate = 1.5;
                                                }
                                            }
                                        } else {
                                            if ($dateObject->isWeekend()) {
                                                $overtimeRate = 1.5;
                                            }
                                        }
                                    }

                                    $totalOverlapHours *= $overtimeRate;
                                    $employeeregular = EmployeeProfile::where('id', $employee->employee_profile_id)
                                        ->whereHas('employmentType', function ($query) {
                                            $query->where('name', 'Permanent Full-time')
                                                ->orWhere('name', 'Permanent Part-time')
                                                ->orWhere('name', 'Permanent CTI')
                                                ->orWhere('name', 'Temporary')
                                                ->orWhere('name', 'Job Order');

                                        })
                                        ->first();
                                    if ($employeeregular) {
                                        $currentYear = date('Y');
                                        $nextYear = $currentYear + 1;
                                        $validUntil = $nextYear . '-12-31';
                                        // Check if a EmployeeOvertimeCredit record exists for the employee with the calculated valid until date
                                        $existingCredit = EmployeeOvertimeCredit::where('employee_profile_id', $employee->employee_profile_id)
                                            ->where('valid_until', $validUntil)
                                            ->first();

                                        if ($existingCredit) {
                                            // Update the existing record
                                            $existingCredit->earned_credit_by_hour += $totalOverlapHours;
                                            $existingCredit->save();

                                            EmployeeOvertimeCreditLog::create([
                                                'employee_ot_credit_id' => $existingCredit->id,
                                                'action' => 'add',
                                                'reason' => 'overtime',
                                                'hours' => (float) $totalOverlapHours
                                            ]);
                                        } else {
                                            // Create a new record

                                            $newId = EmployeeOvertimeCredit::create([
                                                'employee_profile_id' => $employee->employee_profile_id,
                                                'earned_credit_by_hour' => (float) $totalOverlapHours,
                                                'used_credit_by_hour' => '0',
                                                'max_credit_monthly' => '40',
                                                'max_credit_annual' => '120',
                                                'valid_until' => $validUntil,
                                            ]);


                                            EmployeeOvertimeCreditLog::create([
                                                'employee_ot_credit_id' => $newId->id,
                                                'action' => 'add',
                                                'reason' => 'overtime',
                                                'hours' => (float) $totalOverlapHours
                                            ]);
                                        }
                                    }
                                }
                                $title = "COC earned credited";
                                $description = "You have earned a COC of ". $totalOverlapHours . ".";

                                $notification = Notifications::create([
                                    "title" => $title,
                                    "description" => $description,
                                    "module_path" => '/cto',
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
            if (isset($overtimeApplication->dates)) {
                foreach ($overtimeApplication->dates as $date) {

                    // Check if the date is in the current month
                    if (date('m', strtotime($date->date)) == $pastMonth) {

                        // Iterate over employees before checking for matching biometric data
                        foreach ($date->employees as $employee) {

                            $overtimeFromTime = $date->time_from;
                            $overtimeToTime = $date->time_to;
                            $biometric_id = $employee->EmployeeProfile->biometric_id;

                            $matchingBiometrics = Helpers::getFirstInAndOutBiometric($biometric_id, $date->date, $overtimeFromTime, $overtimeToTime);

                            if ($matchingBiometrics) {

                                $totalOverlapHours = $matchingBiometrics;

                                // Convert the string representation of the date to a DateTime object
                                $dateObject = Carbon::createFromFormat('Y-m-d', $date->date);

                                // Format the date to 'm-d' format
                                $dateFormatted = $dateObject->format('m-d');

                                // Check if there is a holiday for the formatted month-day
                                $isHoliday = Holiday::where('month_day', $dateFormatted)->exists();

                                $overtimeRate = 1;

                                if ($isHoliday) {
                                    $holiday = Holiday::where('month_day', $dateFormatted)->first();
                                    if ($holiday->isspecial == 1) {
                                        $effectiveDate = Carbon::createFromFormat('Y-m-d', $holiday->effectiveDate);
                                        if ($effectiveDate->isSameDay($dateObject)) {

                                            if ($dateObject->isWeekend()) {
                                                $overtimeRate = 1.5;
                                            }
                                        }
                                    } else {
                                        if ($dateObject->isWeekend()) {
                                            $overtimeRate = 1.5;
                                        }
                                    }
                                }

                                $totalOverlapHours *= $overtimeRate;
                                $employeeregular = EmployeeProfile::where('id', $employee->employee_profile_id)
                                    ->whereHas('employmentType', function ($query) {
                                        $query->where('name', 'Permanent Full-time')
                                            ->orWhere('name', 'Permanent Part-time');
                                    })
                                    ->first();
                                if ($employeeregular) {
                                    $currentYear = date('Y');
                                    $nextYear = $currentYear + 1;
                                    $validUntil = $nextYear . '-12-31';
                                    // Check if a EmployeeOvertimeCredit record exists for the employee with the calculated valid until date
                                    $existingCredit = EmployeeOvertimeCredit::where('employee_profile_id', $employee->employee_profile_id)
                                        ->where('valid_until', $validUntil)
                                        ->first();

                                    if ($existingCredit) {
                                        // Update the existing record
                                        $existingCredit->earned_credit_by_hour += $totalOverlapHours;
                                        $existingCredit->save();

                                        EmployeeOvertimeCreditLog::create([
                                            'employee_ot_credit_id' => $existingCredit->id,
                                            'action' => 'add',
                                            'reason' => 'overtime',
                                            'hours' => (float) $totalOverlapHours
                                        ]);
                                    } else {
                                        // Create a new record

                                        $newId=EmployeeOvertimeCredit::create([
                                            'employee_profile_id' => $employee->employee_profile_id,
                                            'earned_credit_by_hour' => (float) $totalOverlapHours,
                                            'used_credit_by_hour' => '0',
                                            'max_credit_monthly' => '40',
                                            'max_credit_annual' => '120',
                                            'valid_until' => $validUntil,
                                        ]);

                                        EmployeeOvertimeCreditLog::create([
                                            'employee_ot_credit_id' => $newId->id,
                                            'action' => 'add',
                                            'reason' => 'overtime',
                                            'hours' => (float) $totalOverlapHours
                                        ]);
                                    }
                                }
                            }
                            $title = "COC earned credited";
                            $description = "You have earned a COC of ". $totalOverlapHours . ".";

                            $notification = Notifications::create([
                                "title" => $title,
                                "description" => $description,
                                "module_path" => '/cto',
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
    }




}
