<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Models\OvertimeApplication;
use App\Http\Controllers\Controller;
use App\Models\EmployeeOvertimeCredit;
use App\Models\EmployeeProfile;
use App\Models\OvtApplicationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class OvertimeApplicationController extends Controller
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
    public function getEmployeeOvertimeTotal()
    {
        $employeeProfiles = EmployeeProfile::with(['overtimeCredits', 'personalInformation'])
        ->get();

        $employeeOvertimeTotals = $employeeProfiles->map(function ($employeeProfile) {
        $totalAddCredits = $employeeProfile->overtimeCredits->where('operation', 'add')->sum('overtime_hours');
        $totalDeductCredits = $employeeProfile->overtimeCredits->where('operation', 'deduct')->sum('overtime_hours');

        $totalOvertimeCredits = $totalAddCredits - $totalDeductCredits;

        return [
            'employee_id' => $employeeProfile->id,
            'employee_name' => $employeeProfile->personalInformation->first_name,
            'total_overtime_credits' => $totalOvertimeCredits,
        ];
        });

        return response()->json(['data' => $employeeOvertimeTotals], Response::HTTP_OK);
    }

    public function getEmployees()
    {
        $currentMonth = date('m');
        $currentYear = date('Y');

        $filteredEmployees = EmployeeProfile::with(['overtimeCredits', 'personalInformation']) // Eager load the 'overtimeCredits' and 'profileInformation' relationships
        ->get()
        ->filter(function ($employeeProfile) use ($currentMonth, $currentYear) {
            $totalAddCredits = $employeeProfile->overtimeCredits->where('operation', 'add')->sum('overtime_hours');
        $totalDeductCredits = $employeeProfile->overtimeCredits->where('operation', 'deduct')->sum('overtime_hours');
        
        $totalOvertimeCredits = $totalAddCredits - $totalDeductCredits;

        return $totalOvertimeCredits < 40 && $totalOvertimeCredits < 120;
        })
        ->map(function ($employeeProfile) {
            $totalAddCredits = $employeeProfile->overtimeCredits->where('operation', 'add')->sum('overtime_hours');
            $totalDeductCredits = $employeeProfile->overtimeCredits->where('operation', 'deduct')->sum('overtime_hours');
            
            $totalOvertimeCredits = $totalAddCredits - $totalDeductCredits;

            return [
                'employee_name' => $employeeProfile->personalInformation->first_name, // Assuming 'name' is the field in the ProfileInformation model representing the employee name
                'total_overtime_credits' => $totalOvertimeCredits,
            ];
        });
      
            return response()->json(['data' => $filteredEmployees], Response::HTTP_OK);

    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
            $user_id = Auth::user()->id;
            $user = EmployeeProfile::where('id','=',$user_id)->first();
            $overtime = OvertimeApplication::create([
                'user_id' => $user->id,
                'reference_number' => $user->id,
                'status' => 'applied',
                'remarks' => 'applied',
                'purpose' => $request->purpose,
                'date' => date('Y-m-d'),
                'time' => date('H:i:s')
                
            ]);

            foreach ($request->activities as $activityData) {
                $activity = $overtime->activities()->create([
                    'activity_name' => $activityData['activity_name'],
                    'quantity' => $activityData['quantity'],
                ]);
    
              
                foreach ($activityData['dates'] as $dateData) {
                    $date = $activity->dates()->create([
                        'date' => $dateData['date'],
                        'time_from' => $dateData['time_from'],
                        'time_to' => $dateData['time_to'],
                    ]);
    
                    foreach ($dateData['employees'] as $employeeData) {
                        $date->employees()->create([
                            'employee_id' => $employeeData['employee_id'],
                           
                        ]);
                    }
                }
            }
           
            $process_name="Applied";
            $overtime_application_logs = $this->storeOvertimeApplicationLog($overtime->id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function storeOvertimeApplicationLog($overtime_application_id,$process_name)
    {
        try {
            $user_id = Auth::user()->id;
            $user = EmployeeProfile::where('id','=',$user_id)->first();
            $overtime_application_log = new OvtApplicationLog();                       
            $overtime_application_log->overtime_application_id = $overtime_application_id;
            $overtime_application_log->action_by = $user_id;
            $overtime_application_log->process_name = $process_name;
            $overtime_application_log->status = "applied";
            $overtime_application_log->date = date('Y-m-d');
            $overtime_application_log->time = date('H:i:s');
            $overtime_application_log->save();

            return $overtime_application_log;
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(OvertimeApplication $overtimeApplication)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OvertimeApplication $overtimeApplication)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OvertimeApplication $overtimeApplication)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OvertimeApplication $overtimeApplication)
    {
        //
    }
}
