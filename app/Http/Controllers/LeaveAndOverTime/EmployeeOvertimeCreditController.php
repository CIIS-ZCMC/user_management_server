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

        $biometricsData = [
            [
                'employee_profile_id' => 1,
                'date' => '2023-12-12',
                'from_time' => '08:00:00',
                'to_time' => '17:00:00',
            ],
            [
                'employee_profile_id' => 1,
                'date' => '2023-12-12',
                'from_time' => '13:00:00',
                'to_time' => '17:00:00',
            ],
            [
                'employee_profile_id' => 1,
                'date' => '2023-12-13',
                'from_time' => '13:00:00',
                'to_time' => '17:00:00',
            ],


        ];
                $currentMonth = date('m');
                $overtimeApplications = OvertimeApplication::where('status', 'approved')->get();
                foreach ($overtimeApplications as $overtimeApplication) {
                    foreach ($overtimeApplication->activities as $activity) {
                        foreach ($activity->dates as $date) {

                            if (date('m', strtotime($date->date)) == $currentMonth) {

                                foreach ($date->employees as $employee) {


                                    $biometrics = collect($biometricsData)
                                    ->where('employee_profile_id', $employee->employee_profile_id)
                                    ->where('date', $date->date)
                                    ->filter(function ($biometric) use ($date) {
                                        // Check if the biometrics time range overlaps with the overtime period
                                        $biometricFromTime = Carbon::parse($biometric['from_time']);
                                        $biometricToTime = Carbon::parse($biometric['to_time']);
                                        $overtimeFromTime = Carbon::parse($date->time_from);
                                        $overtimeToTime = Carbon::parse($date->time_to);

                                        return (
                                            $biometricFromTime->between($overtimeFromTime, $overtimeToTime) ||
                                            $biometricToTime->between($overtimeFromTime, $overtimeToTime)
                                        );
                                    });

                                // Calculate and store the total overtime hours in the array for this employee
                                foreach ($biometrics as $biometric) {
                                    $biometricFromTime = Carbon::parse($biometric['from_time']);
                                    $biometricToTime = Carbon::parse($biometric['to_time']);
                                    $overtimeFromTime = Carbon::parse($date->time_from);
                                    $overtimeToTime = Carbon::parse($date->time_to);

                                    // Calculate the time difference in hours for the overlapping period
                                    $overlapFromTime = max($biometricFromTime, $overtimeFromTime);
                                    $overlapToTime = min($biometricToTime, $overtimeToTime);

                                    $totalOvertimeHours = $overlapToTime->diffInHours($overlapFromTime);

                                    $employee_leave_credits = new EmployeeOvertimeCredit();
                                    $employee_leave_credits->employee_profile_id = $employee->employee_profile_id;
                                    $employee_leave_credits->overtime_application_id  = $overtimeApplication->id;
                                    $employee_leave_credits->operation = "add";
                                    // $employee_leave_credits->reason = "Overtime";
                                    $employee_leave_credits->credit_value = $totalOvertimeHours;
                                    $employee_leave_credits->date = date('Y-m-d');;
                                    $employee_leave_credits->save();

                                }
                            }

                            }
                        }
                    }
                }


    }


    /**
     * Display the specified resource.
     */
    public function show(EmployeeOvertimeCredit $employeeOvertimeCredit)
    {
        //
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
