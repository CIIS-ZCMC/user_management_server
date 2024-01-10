<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Models\EmployeeOvertimeCredit;
use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Models\OvertimeApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use Carbon\Carbon;
class EmployeeOvertimeCreditController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {


        // Assume you have biometrics data for all employees
        $biometricsData = [
            [
                'employee_profile_id' => 1,
                'date' => '2023-12-12',
                'from_time' => '08:00:00',
                'to_time' => '17:00:00',
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
        $overtimeApplications = OvertimeApplication::where('status', 'approved')->with('activities', 'directDates')->get();

      foreach ($overtimeApplications as $overtimeApplication) {
        if (isset($overtimeApplication->activities)) {

        //    return $overtimeApplication->relationLoaded('activities');
            // Check if the overtime application is approved

                foreach ($overtimeApplication->activities as $activity) {
                    foreach ($activity->dates as $date) {
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

                                        $totalOvertimeHours = $overlapToTime->diffInHours($overlapFromTime);
                                        // Store the total overtime hours for each unique combination in the database
                                        $date_compare=$date->date;
                                        $total =  $this->calculateTotal($date_compare);
                                        EmployeeOvertimeCredit::create([
                                            'employee_profile_id' => $employee->employee_profile_id,
                                            'date' => date('Y-m-d'),
                                            'operation' => 'add',
                                            'overtime_application_id' =>$overtimeApplication->id,
                                            'credit_value' => $totalOvertimeHours * $total,
                                            'overtime_hours' => $totalOvertimeHours * $total,
                                        ]);
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

                                        $totalOvertimeHours = $overlapToTime->diffInHours($overlapFromTime);
                                        // Store the total overtime hours for each unique combination in the database
                                        $date_compare=$date->date;
                                        $total =  $this->calculateTotal($date_compare);
                                        EmployeeOvertimeCredit::create([
                                            'employee_profile_id' => $employee->employee_profile_id,
                                            'date' => date('Y-m-d'),
                                            'operation' => 'add',
                                            'overtime_application_id' =>$overtimeApplication->id,
                                            'credit_value' => $totalOvertimeHours * $total,
                                            'overtime_hours' => $totalOvertimeHours * $total,
                                        ]);
                                    }
                                }
                            }


                }
            }

        }



      }
    }

    public function calculateTotal($date)
    {

            $carbonDate = Carbon::parse($date);
            if ($carbonDate->isWeekend() || $carbonDate->isHoliday()) {
                return 1.5;
            }
            else {
                return 1;
            }
    }

    public function show(EmployeeOvertimeCredit $employeeOvertimeCredit)
    {

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeOvertimeCredit $employeeOvertimeCredit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeOvertimeCredit $employeeOvertimeCredit)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeOvertimeCredit $employeeOvertimeCredit)
    {
        //
    }
}
