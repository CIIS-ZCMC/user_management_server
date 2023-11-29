<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Models\Requirement;
use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveType;
use App\Models\EmployeeProfile;
use App\Models\LeaveType as ModelsLeaveType;
use App\Models\RequirementLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class RequirementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{

    $requirements = Requirement::with('logs.employeeProfile.personalInformation') // Eager load relationships
        ->get();
    
    $result = $requirements->map(function ($requirement) {
        // Access requirement details
        $requirementDetails = $requirement->toArray();
    
        // Access logs for the current requirement
        $logs = $requirement->logs->map(function ($log) {
            // Check if the employeeProfile relation is present
            if ($log->employeeProfile) {
                // Access employee name for the current log
                $first_name = optional($log->employeeProfile->personalInformation)->first_name ;
                $last_name = optional($log->employeeProfile->personalInformation)->last_name;
                $date=$log->date;
                $formatted_date=Carbon::parse($date)->format('M d,Y');
                return [
                    'id' => $log->id,
                    'action_by' => "{$first_name} {$last_name}" ,
                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                    'action' => $log->action,
                    'date' => $formatted_date,
                    'time' => $log->time,
                    'process' => $action
                ];
            }
    
            return null; // or handle this case according to your logic
        })->filter(); // Remove null values from the result
    
        return [
            'id' => $requirementDetails['id'],
            'value' => $requirementDetails['name'],
            'label' => $requirementDetails['description'],
            'logs' => $logs,
        ];
    });
    
             return response()->json(['data' => $result ], Response::HTTP_OK);
        }catch(\Throwable $th){
        
            return response()->json(['message' => $th->getMessage()], 500);
        }
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
        try{
            $requirement = new Requirement();
            $requirement->name = ucwords($request->name);
            $requirement->description = $request->description;
            $requirement->save();

            $requirement_log = new RequirementLog();
            $requirement_log->requirement_id = $requirement->id;
            $requirement_log->action_by = '1';
            $requirement_log->action_name = 'Add';
            $requirement_log->save();


            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
           
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id,Requirement $requirement)
    {
        try{
            $data = Requirement::find($id);

            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){
           
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Requirement $requirement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id,Request $request)
    {
        try{
            $requirement = Requirement::findOrFail($id);
            $requirement->name = ucwords($request->name);
            $requirement->description = $request->description;
            $requirement->update();

            $requirement_log = new RequirementLog();
            $requirement_log->requirement_id = $requirement->id;
            $requirement_log->action_by = '1';
            $requirement_log->action_name = 'Update ';
            $requirement_log->save();


          
            return response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){
           
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Requirement $requirement)
    {
        //
    }

   

   
}
