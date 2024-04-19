<?php

namespace App\Console\Commands;

use App\Models\EmployeeOvertimeCredit;
use App\Models\EmployeeProfile;
use App\Models\OvertimeApplication;
use Illuminate\Console\Command;
use Carbon\Carbon;
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

    public function handle()
    {
        $biometricsData = [
            [
                'employee_profile_id' => 1,
                'date' => '2023-12-12',
                'from_time' => '08:00:00',
                'to_time' => '17:30:00',
            ],
            [
                'employee_profile_id' => 1,
                'date' => '2023-12-13',
                'from_time' => '13:00:00',
                'to_time' => '17:00:00',
            ],
            [
                'employee_profile_id' => 1,
                'date' => '2023-12-16',
                'from_time' => '13:00:00',
                'to_time' => '17:00:00',
            ],
            [
                'employee_profile_id' => 1,
                'date' => '2023-12-14',
                'from_time' => '14:00:00',
                'to_time' => '17:00:00',
            ],
        ];

        $currentMonth = date('m');
        $pastMonth = date('m', strtotime('-1 month'));
        $overtimeApplications = OvertimeApplication::where('status', 'approved')->with('activities', 'directDates')->get();
        foreach ($overtimeApplications as $overtimeApplication) {
            if (isset($overtimeApplication->activities)) {
                // Check if the overtime application is approved
                    foreach ($overtimeApplication->activities as $activity) {
                        foreach ($activity->dates as $date) {
                            // Check if the date is in the current month
                            if (date('m', strtotime($date->date)) == $pastMonth) {
                                // Iterate over employees before checking for matching biometric data
                                foreach ($date->employees as $employee) {
                                    // Check if there is biometrics data available for the current date and employee
                                    $matchingBiometrics = collect($biometricsData)
                                        ->where('date', $date->date)
                                        ->where('employee_profile_id', $employee->employee_profile_id)
                                        ->filter(function ($biometric) use ($date) {
                                            // Convert times to Carbon objects for easy comparison
                                            $biometricFromTime = Carbon::parse($biometric['from_time']);
                                            $biometricToTime = Carbon::parse($biometric['to_time']);
                                            $overtimeFromTime = Carbon::parse($date->time_from);
                                            $overtimeToTime = Carbon::parse($date->time_to);

                                            // Check if there is an overlap between the biometric time range and overtime period
                                    $isOverlapping = $biometricFromTime->lt($overtimeToTime) &&
                                    $biometricToTime->gt($overtimeFromTime);

                                    // Check if the biometric range is fully or partially within the overtime period
                                    return $isOverlapping || ($biometricFromTime >= $overtimeFromTime && $biometricToTime <= $overtimeToTime);
                                        });

                                    // Proceed only if there is matching biometric data for the current date and employee
                                    if ($matchingBiometrics->isNotEmpty()) {
                                        // Calculate and store the total overtime hours for each unique combination
                                        foreach ($matchingBiometrics as $biometric) {
                                            // Calculate the time difference in hours for the overlapping period
                                            $biometricFromTime = Carbon::parse($biometric['from_time']);
                                            $biometricToTime = Carbon::parse($biometric['to_time']);
                                            $overtimeFromTime = Carbon::parse($date->time_from);
                                            $overtimeToTime = Carbon::parse($date->time_to);

                                            $overlapFromTime = max($biometricFromTime, $overtimeFromTime);
                                            $overlapToTime = min($biometricToTime, $overtimeToTime);

                                            $totalOvertimeHours = $overlapToTime->diffInMinutes($overlapFromTime);
                                            // Store the total overtime hours for each unique combination in the database
                                            $date_compare=$date->date;
                                            $total =  $this->calculateTotal($date_compare);
                                            $totalOvertimeHoursFormatted = number_format($totalOvertimeHours / 60, 1);
                                            $employeeregular = EmployeeProfile::where('id', $employee->employee_profile_id)
                                            ->whereHas('employmentType', function ($query) {
                                                $query->where('name', 'Permanent Full-Time')
                                                    ->orWhere('name', 'Permanent Part-Time');
                                            })
                                            ->first();
                                            if($employeeregular)
                                            {
                                                $currentYear = date('Y');
                                                $nextYear = $currentYear + 1;
                                                $validUntil = $nextYear . '-12-31';
                                                // Check if a EmployeeOvertimeCredit record exists for the employee with the calculated valid until date
                                                $existingCredit = EmployeeOvertimeCredit::where('employee_profile_id', $employee->employee_profile_id)
                                                    ->where('valid_until', $validUntil)
                                                    ->first();

                                                if ($existingCredit) {
                                                    // Update the existing record
                                                    $existingCredit->earned_credit_by_hour += $totalOvertimeHoursFormatted;
                                                    $existingCredit->save();
                                                } else {
                                                    // Create a new record
                                                    EmployeeOvertimeCredit::create([
                                                        'employee_profile_id' => $employee->employee_profile_id,
                                                        'earned_credit_by_hour' => $totalOvertimeHoursFormatted,
                                                        'used_credit_by_hour' => '0',
                                                        'max_credit_monthly' => '40',
                                                        'max_credit_annual' => '120',
                                                        'valid_until' => $validUntil,
                                                    ]);
                                                }
                                            }

                                        }
                                    }
                                }
                            }
                        }
                    }

            }
            if (isset($overtimeApplication->directDates))  {
                        foreach ($overtimeApplication->directDates as $date) {

                            // Check if the date is in the current month
                            if (date('m', strtotime($date->date)) == $currentMonth) {
                                // Iterate over employees before checking for matching biometric data
                                foreach ($date->employees as $employee) {
                                    // Check if there is biometrics data available for the current date and employee
                                    $matchingBiometrics = collect($biometricsData)
                                        ->where('date', $date->date)
                                        ->where('employee_profile_id', $employee->employee_profile_id)
                                        ->filter(function ($biometric) use ($date) {
                                            // Convert times to Carbon objects for easy comparison
                                            $biometricFromTime = Carbon::parse($biometric['from_time']);
                                            $biometricToTime = Carbon::parse($biometric['to_time']);
                                            $overtimeFromTime = Carbon::parse($date->time_from);
                                            $overtimeToTime = Carbon::parse($date->time_to);

                                            // Check if there is an overlap between the biometric time range and overtime period
                                    $isOverlapping = $biometricFromTime->lt($overtimeToTime) &&
                                    $biometricToTime->gt($overtimeFromTime);

                                    // Check if the biometric range is fully or partially within the overtime period
                                    return $isOverlapping || ($biometricFromTime >= $overtimeFromTime && $biometricToTime <= $overtimeToTime);
                                        });

                                    // Proceed only if there is matching biometric data for the current date and employee
                                    if ($matchingBiometrics->isNotEmpty()) {
                                        // Calculate and store the total overtime hours for each unique combination
                                        foreach ($matchingBiometrics as $biometric) {
                                            // Calculate the time difference in hours for the overlapping period
                                            $biometricFromTime = Carbon::parse($biometric['from_time']);
                                            $biometricToTime = Carbon::parse($biometric['to_time']);
                                            $overtimeFromTime = Carbon::parse($date->time_from);
                                            $overtimeToTime = Carbon::parse($date->time_to);

                                            $overlapFromTime = max($biometricFromTime, $overtimeFromTime);
                                            $overlapToTime = min($biometricToTime, $overtimeToTime);

                                            $totalOvertimeHours = $overlapToTime->diffInMinutes($overlapFromTime);
                                            // Store the total overtime hours for each unique combination in the database
                                            $date_compare=$date->date;
                                            $total =  $this->calculateTotal($date_compare);
                                            $totalOvertimeHoursFormatted = number_format($totalOvertimeHours / 60, 1);
                                            $employeeregular = EmployeeProfile::where('id', $employee->employee_profile_id)
                                            ->whereHas('employmentType', function ($query) {
                                                $query->where('name', 'Regular Full-Time')
                                                    ->orWhere('name', 'Regular Part-Time');
                                            })
                                            ->first();

                                            if($employeeregular)
                                            {
                                                EmployeeOvertimeCredit::create([
                                                    'employee_profile_id' => $employee->employee_profile_id,
                                                    'date' => date('Y-m-d'),
                                                    'operation' => 'add',
                                                    'overtime_application_id' =>$overtimeApplication->id,
                                                    'credit_value' => $totalOvertimeHoursFormatted,
                                                    'overtime_hours' => $totalOvertimeHoursFormatted,
                                                ]);
                                            }
                                        }
                                    }
                                }


                    }
                }

            }



        }
    }
}
