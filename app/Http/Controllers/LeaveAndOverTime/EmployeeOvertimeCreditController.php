<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Models\EmployeeOvertimeCredit;
use App\Http\Controllers\Controller;
use App\Imports\EmployeeLeaveCreditsImport;
use App\Imports\EmployeeOvertimeCreditsImport;
use App\Models\EmployeeLeaveCredit;
use App\Models\EmployeeOvertimeCreditLog;
use App\Models\EmployeeProfile;
use App\Models\OvertimeApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

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
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
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
                                        $date_compare = $date->date;
                                        $total =  $this->calculateTotal($date_compare);
                                        $totalOvertimeHoursFormatted = number_format($totalOvertimeHours / 60, 1);
                                        $employeeregular = EmployeeProfile::where('id', $employee->employee_profile_id)
                                            ->whereHas('employmentType', function ($query) {
                                                $query->where('name', 'Permanent Full-Time')
                                                    ->orWhere('name', 'Permanent Part-Time');
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
            if (isset($overtimeApplication->directDates)) {
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
                                    $date_compare = $date->date;
                                    $total =  $this->calculateTotal($date_compare);
                                    $totalOvertimeHoursFormatted = number_format($totalOvertimeHours / 60, 1);
                                    $employeeregular = EmployeeProfile::where('id', $employee->employee_profile_id)
                                        ->whereHas('employmentType', function ($query) {
                                            $query->where('name', 'Regular Full-Time')
                                                ->orWhere('name', 'Regular Part-Time');
                                        })
                                        ->first();

                                    if ($employeeregular) {
                                        EmployeeOvertimeCredit::create([
                                            'employee_profile_id' => $employee->employee_profile_id,
                                            'date' => date('Y-m-d'),
                                            'operation' => 'add',
                                            'overtime_application_id' => $overtimeApplication->id,
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

    public function calculateTotal($date)
    {

        $carbonDate = Carbon::parse($date);
        if ($carbonDate->isWeekend()) {
            return 1.5;
        } else {
            return 1;
        }
    }

    public function show(EmployeeOvertimeCredit $employeeOvertimeCredit) {}

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
    public function updateCredit(Request $request, EmployeeOvertimeCredit $employeeOvertimeCredit)
    {
        $employeeId = $request->employee_id;
        $validUntil = $request->valid_until;
        $creditValue = $request->credit_value;
        $existingCredit = EmployeeOvertimeCredit::where('employee_profile_id', $employeeId)
            ->where('valid_until', $validUntil)
            ->first();
        if ($existingCredit) {
            $existingCredit->earned_credit_by_hour += $creditValue;
            $existingCredit->save();
        } else {
            // Create a new record
            EmployeeOvertimeCredit::create([
                'employee_profile_id' => $employeeId,
                'earned_credit_by_hour' => $creditValue,
                'used_credit_by_hour' => '0',
                'max_credit_monthly' => '40',
                'max_credit_annual' => '120',
                'valid_until' => $validUntil,
            ]);
        }
    }


    public function destroy(EmployeeOvertimeCredit $employeeOvertimeCredit)
    {
        $currentDate = date('Y-m-d');

        // Retrieve records where valid_until is past the current date
        $expiredCredits = EmployeeOvertimeCredit::where('valid_until', '<', $currentDate)->get();

        // Log the expired credits before deleting them
        foreach ($expiredCredits as $expiredCredit) {
            // Create a log entry for each expired credit
            EmployeeOvertimeCreditLog::create([
                'employee_profile_id' => $expiredCredit->employee_profile_id,
                'expired_credit_by_hour' => $expiredCredit->earned_credit_by_hour,
                'action' => 'Expired',
            ]);
        }

        // Delete records where valid_until is past the current date
        $deletedCount = EmployeeOvertimeCredit::where('valid_until', '<', $currentDate)->delete();
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,txt',
        ]);

        try {
            // Run the import
            $import = new EmployeeOvertimeCreditsImport();
            Excel::import($import, $request->file('file'));

            // Collect only the employees we just processed
            $employeeProfileIds = collect($import->data)->pluck('employee_profile_id')->unique();

            // Load their updated overtime credits (like in getEmployees)
            $overtimeCredits = EmployeeOvertimeCredit::with(['employeeProfile.personalInformation'])
                ->whereIn('employee_profile_id', $employeeProfileIds)
                ->get()
                ->groupBy('employee_profile_id');

            $response = [];

            foreach ($overtimeCredits as $employeeProfileId => $credits) {
                $employeeDetails = $credits->first()->employeeProfile->personalInformation->name();

                $currentYearBalance = 0;
                $currentYearValidUntil = null;
                $nextYearBalance = 0;
                $nextYearValidUntil = null;
                $overallTotalBalance = 0;

                foreach ($credits as $credit) {
                    if (!$credit->is_expired) {
                        $validUntil = \Carbon\Carbon::parse($credit->valid_until);
                        $year = $validUntil->year;
                        $overallTotalBalance += $credit->earned_credit_by_hour;

                        if ($year == now()->year) {
                            $currentYearBalance += $credit->earned_credit_by_hour;
                            $currentYearValidUntil = $validUntil->toDateString();
                        } elseif ($year == now()->year + 1) {
                            $nextYearBalance += $credit->earned_credit_by_hour;
                            $nextYearValidUntil = $validUntil->toDateString();
                        }
                    }
                }

                $employeeResponse = [
                    'id' => $employeeProfileId,
                    'name' => $employeeDetails,
                    'employee_id' => $credits->first()->employeeProfile->employee_id,
                    'credits' => [
                        'current_year_balance' => $currentYearBalance,
                        'current_valid_until' => $currentYearValidUntil,
                        'next_year_balance' => $nextYearBalance,
                        'next_year_valid_until' => $nextYearValidUntil,
                        'overall_total_balance' => $overallTotalBalance,
                    ],
                ];

                $response[] = $employeeResponse;
            }

            return response()->json([
                'message' => 'Employee overtime credits imported successfully.',
                'data'    => $response, // 👈 same format as getEmployees()
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Import failed.',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
