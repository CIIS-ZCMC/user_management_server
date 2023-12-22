<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Models\MonetizationApplication;
use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveType;
use App\Models\EmployeeProfile;
use App\Models\LeaveType as ModelsLeaveType;
use App\Models\MoneApplicationLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MonetizationApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{ 
            $mone_applications=[];
            
            $mone_applications =MonetizationApplication::with(['logs'])->get();
          
           
             return response()->json(['data' => $mone_applications], Response::HTTP_OK);
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

    public function getMoneApplications(Request $request)
    {
        $status = $request->status;  
        $mone_applications = [];

       if($status == 'for-approval-supervisor'){
            $mone_applications = MonetizationApplication::where('status', '=', 'for-approval-supervisor');
            
        }
        else if($status == 'for-approval-head'){
            $mone_applications = MonetizationApplication::where('status', '=', 'for-approval-head');

        }
        else if($status == 'declined'){
            $mone_applications = MonetizationApplication::where('status', '=', 'declined');
                                                   
        }
        else if($status == 'approved'){
            $mone_applications = MonetizationApplication::where('status', '=', 'approved');
        
        }
        else{
            $mone_applications = MonetizationApplication::where('status', '=', $status );
        }


        if (isset($request->search)) {
            $search = $request->search; 
            $mone_applications = $mone_applications->where('reference_number','like', '%' .$search . '%');
                                                 
            $mone_applications = isset($search) && $search; 
        }

        return response()->json(['data' => $mone_applications], Response::HTTP_OK);
    }

    public function updateStatus (Request $request)
    {
        try {
                $user_id = Auth::user()->id;
                $user = EmployeeProfile::where('id','=',$user_id)->first();
                $user_password=$user->password;
                $password=$request->password;
                if($user_password==$password)
                {
                            $message_action = '';
                            $action = '';
                            $new_status = '';
                            $status = $request->status;

                            if($status == 'for-approval-supervisor' ){
                                $action = 'Aprroved by Supervisor';
                                $new_status='for-approval-head';
                                $message_action="Approved";
                            }
                            else if($status == 'for-approval-head'){
                                $action = 'Aprroved by Department Head';
                                $new_status='approved';
                                $message_action="Approved";
                            }
                            else{
                                $action = $status;
                            }
                            $mone_application_id = $request->monetization_application_id;
                            $mone_applications = MonetizationApplication::where('id','=', $mone_application_id)
                                                                    ->first();
                            if($mone_applications){
                            
                                $mone_application_log = new MoneApplicationLog();
                                $mone_application_log->action = $action;
                                $mone_application_log->mone_application_id = $mone_application_id;
                                $mone_application_log->action_by = $user_id;
                                $mone_application_log->date = date('Y-m-d');
                                $mone_application_log->save();

                                $mone_application = MonetizationApplication::findOrFail($mone_application_id);   
                                $mone_application->status = $new_status;
                                $mone_application->update();
                                    
                                return response(['message' => 'Application has been sucessfully '.$message_action, 'data' => $mone_application], Response::HTTP_CREATED); 
                                }
                }           
            }


         catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
      
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
            $leave_type=ModelsLeaveType::where('name','Vacation Leave')->first();
            $user_id = Auth::user()->id;
            $user = EmployeeProfile::where('id','=',$user_id)->first();
            $mone_application = new MonetizationApplication();
            $mone_application->employee_profile_id = $user->id;
            $mone_application->leave_type_id = $leave_type->id;
            $mone_application->credit_value = $request->credit_value;
            $mone_application->status = "for-approval-supervisor";
            $mone_application->reason = "for-approval-supervisor";
            $mone_application->date = date('Y-m-d');
            $mone_application->time =  date('H:i:s');
            if ($request->hasFile('attachment')) {
                $imagePath = $request->file('attachment')->store('images', 'public');
                $mone_application->attachment = $imagePath;
            }
            
        
            $mone_application->save();
           
            $process_name="Applied";
            $mone_logs = $this->storeMonetizationLog($mone_application->id,$process_name,$user->id);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function declineMoneApplication(Request $request)
    {
        try {
                    $mone_application_id = $request->monetization_application_id;
                    $mone_applications = MonetizationApplication::where('id','=', $mone_application_id)
                                                            ->first();
                if($mone_applications)
                {
                        $user_id = Auth::user()->id;     
                        $user = EmployeeProfile::where('id','=',$user_id)->first();
                        $user_password=$user->password;
                        $password=$request->password;
                        if($user_password==$password)
                        {
                            if($user_id){
                                $mone_application_log = new MoneApplicationLog();
                                $mone_application_log->action = 'declined';
                                $mone_application_log->mone_application_id = $mone_application_id;
                                $mone_application_log->date = date('Y-m-d');
                                $mone_application_log->action_by = $user_id;
                                $mone_application_log->save();

                                $mone_application = MonetizationApplication::findOrFail($mone_application_id);
                                $mone_application->status = 'declined';
                                $mone_application->update();
                                return response(['message' => 'Application has been sucessfully declined', 'data' => $mone_application], Response::HTTP_CREATED);  
            
                            }
                         }
                }
            } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),  'error'=>true]);
        }
    }
    public function updateMoneApplication(Request $request)
    {
        try{
            $mone_application_id=$request->monetization_application_id;
            $user_id = Auth::user()->id;
            $user = EmployeeProfile::where('id','=',$user_id)->first();
            $mone_application = MonetizationApplication::findOrFail($mone_application_id);
            $mone_application->credit_value = $request->credit_value;
            if ($request->hasFile('attachment')) {
                $imagePath = $request->file('attachment')->store('images', 'public');
                $mone_application->attachment = $imagePath;
            }
            
            $mone_application->update();
           
            $process_name="Update";
            $mone_logs = $this->storeMonetizationLog($mone_application->id,$process_name,$user->id);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }


    public function storeMonetizationLog($mone_application_id,$process_name,$user_id)
    {
        try {

            $mone_application_log = new MoneApplicationLog();                       
            $mone_application_log->mone_application_id = $mone_application_id;
            $mone_application_log->action_by = $user_id;
            $mone_application_log->action = $process_name;
            $mone_application_log->status = "applied";
            $mone_application_log->date = date('Y-m-d');
            $mone_application_log->time =  date('H:i:s');
            $mone_application_log->save();

            return $mone_application_log;
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
    }

    public function cancelmoneApplication(Request $request)
    {
        try {
                    $mone_application_id = $request->mone_application_id;
                    $mone_applications = MonetizationApplication::where('id','=', $mone_application_id)
                                                            ->first();
                if($mone_applications)
                {
                        $user_id = Auth::user()->id;     
                        $user = EmployeeProfile::where('id','=',$user_id)->first();
                        $user_password=$user->password;
                        $password=$request->password;
                        if($user_password==$password)
                        {
                            if($user_id){
                                $mone_application_log = new MoneApplicationLog();
                                $mone_application_log->action = 'cancelled';
                                $mone_application_log->mone_application_id = $mone_application_id;
                                $mone_application_log->date = date('Y-m-d');
                                $mone_application_log->action_by = $user_id;
                                $mone_application_log->save();

                                $mone_application = MonetizationApplication::findOrFail($mone_application_id);
                                $mone_application->status = 'cancelled';
                                $mone_application->update();
                                return response(['message' => 'Application has been sucessfully cancelled', 'data' => $mone_application], Response::HTTP_CREATED);  
            
                            }
                         }
                }
            } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),  'error'=>true]);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(MonetizationApplication $monetizationApplication)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MonetizationApplication $monetizationApplication)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MonetizationApplication $monetizationApplication)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MonetizationApplication $monetizationApplication)
    {
        //
    }
}
