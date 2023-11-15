<?php

namespace App\Http\Controllers;

use App\Models\OvertimeApplication;
use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use Illuminate\Http\Request;

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
            $user_id = Auth::user()->id;
            $user = EmployeeProfile::where('id','=',$user_id)->first();
            $overtime = OvertimeApplication::create([
                'user_id' => $user->id,
                'reference_number' => $user->id,
                'status' => 'applied',
                'remarks' => 'applied',
                'purpose' => $request->purpose,
                'date' => date('Y-m-d')
                
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
           
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
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
