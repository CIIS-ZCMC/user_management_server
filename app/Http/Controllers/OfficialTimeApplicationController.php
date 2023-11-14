<?php

namespace App\Http\Controllers;
use Illuminate\Http\Response;
use App\Models\OfficialTimeApplication;
use App\Http\Controllers\Controller;
use App\Http\Resources\OfficialTimeApplication as ResourcesOfficialTimeApplication;
use App\Http\Resources\OtApplicationLog;
use App\Models\EmployeeProfile;
use App\Models\OfficialTimeRequirements;
use App\Models\OtApplicationLog as ModelsOtApplicationLog;
use App\Models\OtApplicationRequirement;
use App\Models\OvertimeApplication;
use Illuminate\Http\Request;
use App\Services\FileService;
use Illuminate\Support\Facades\Auth;
class OfficialTimeApplicationController extends Controller
{
    protected $file_service;
    public function __construct(FileService $file_service)
    { 
        $this->file_service = $file_service; 
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{ 
            $official_time_applications=[];
            
           $official_time_applications =OfficialTimeApplication::with(['logs', 'requirements'])->get();;
        //    $official_time_application_resource=ResourcesOfficialTimeApplication::collection($official_time_applications);
           
             return response()->json(['data' => $official_time_applications], Response::HTTP_OK);
        }catch(\Throwable $th){
        
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    public function getOtApplications(Request $request)
    {
        $status = $request->status;  
        $ot_applications = [];

       if($status == 'for-approval-supervisor'){
            $ot_applications = OfficialTimeApplication::where('status', '=', 'for-approval-supervisor');
            
        }
        else if($status == 'for-approval-head'){
            $ot_applications = OfficialTimeApplication::where('status', '=', 'for-approval-head');

        }
        else if($status == 'declined'){
            $ot_applications = OfficialTimeApplication::where('status', '=', 'declined');
                                                   
        }
        else if($status == 'approved'){
            $ot_applications = OfficialTimeApplication::where('status', '=', 'approved');
        
        }
        else{
            $ot_applications = OfficialTimeApplication::where('status', '=', $status );
        }


        if (isset($request->search)) {
            $search = $request->search; 
            $ot_applications = $ot_applications->where('reference_number','like', '%' .$search . '%');                                      
            $ot_applications = isset($search) && $search; 
        }

        return ResourcesOfficialTimeApplication::collection($ot_applications->paginate(50));
    }

    public function store(Request $request)
    {
        try{
            $user_id = Auth::user()->id;
            $user = EmployeeProfile::where('id','=',$user_id)->first();
            $official_time_application = new OfficialTimeApplication();
            $official_time_application->employee_profile_id = $user->id;
            $official_time_application->date_from = $request->date_from;
            $official_time_application->date_to = $request->date_to;
            $official_time_application->time_from = $request->time_from;
            $official_time_application->time_to = $request->time_to;
            $official_time_application->status = "for-approval-supervisor";
            $official_time_application->reason = "for-approval-supervisor";
            $official_time_application->date = date('Y-m-d');
            if ($request->hasFile('personal_order')) {
                $imagePath = $request->file('personal_order')->store('images', 'public');
                $official_time_application->personal_order = $imagePath;
            }
            if ($request->hasFile('certificate_of_appearance')) {
                $imagePath = $request->file('certificate_of_appearance')->store('images', 'public');
                $official_time_application->certificate_of_appearance = $imagePath;
            }
            $official_time_application->save();
         
          
            $process_name="Applied";
            $official_time_logs = $this->storeOfficialTimeApplicationLog($official_time_application->id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function declineOtApplication(Request $request)
    {
        try {
                    $ot_application_id = $request->ot_application_id;
                    $ot_applications = OfficialTimeApplication::where('id','=', $ot_application_id)
                                                            ->first();
                if($ot_applications)
                {
                        $user_id = Auth::user()->id;     
                        $user = EmployeeProfile::where('id','=',$user_id)->first();
                        $user_password=$user->password;
                        $password=$request->password;
                        if($user_password==$password)
                        {
                            if($user_id){
                                $ot_application_log = new ModelsOtApplicationLog();
                                $ot_application_log->action = 'declined';
                                $ot_application_log->ot_application_id = $ot_application_id;
                                $ot_application_log->date = date('Y-m-d');
                                $ot_application_log->action_by = $user_id;
                                $ot_application_log->save();

                                $ot_application = OfficialTimeApplication::findOrFail($ot_application_id);
                                $ot_application->declined_at = now();
                                $ot_application->status = 'declined';
                                $ot_application->update();
                                return response(['message' => 'Application has been sucessfully declined', 'data' => $ot_application], Response::HTTP_CREATED);  
            
                            }
                         }
                }
            } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),  'error'=>true]);
        }
    }

    public function cancelOtApplication(Request $request)
    {
        try {
                    $ot_application_id = $request->ot_application_id;
                    $ot_applications = OfficialTimeApplication::where('id','=', $ot_application_id)
                                                            ->first();
                if($ot_applications)
                {
                        $user_id = Auth::user()->id;     
                        $user = EmployeeProfile::where('id','=',$user_id)->first();
                        $user_password=$user->password;
                        $password=$request->password;
                        if($user_password==$password)
                        {
                            if($user_id){
                                $ot_application_log = new ModelsOtApplicationLog();
                                $ot_application_log->action = 'cancelled';
                                $ot_application_log->ot_application_id = $ot_application_id;
                                $ot_application_log->date = date('Y-m-d');
                                $ot_application_log->action_by = $user_id;
                                $ot_application_log->save();

                                $ot_application = OfficialTimeApplication::findOrFail($ot_application_id);
                                $ot_application->status = 'cancelled';
                                $ot_application->update();
                                return response(['message' => 'Application has been sucessfully cancelled', 'data' => $ot_application], Response::HTTP_CREATED);  
            
                            }
                         }
                }
            } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),  'error'=>true]);
        }
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
                            $ot_application_id = $request->ot_application_id;
                            $ot_applications = OfficialTimeApplication::where('id','=', $ot_application_id)
                                                                    ->first();
                            if($ot_applications){
                            
                                $ot_application_log = new ModelsOtApplicationLog();
                                $ot_application_log->action = $action;
                                $ot_application_log->ot_application_id = $ot_application_id;
                                $ot_application_log->action_by = $user_id;
                                $ot_application_log->date = date('Y-m-d');
                                $ot_application_log->save();

                                $ot_application = OfficialTimeApplication::findOrFail($ot_application_id);   
                                $ot_application->status = $new_status;
                                $ot_application->update();
                                    
                                return response(['message' => 'Application has been sucessfully '.$message_action, 'data' => $ot_application], Response::HTTP_CREATED); 
                                }
                }           
            }


         catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
      
    }

    public function updateOtApplication(Request $request)
    {
        try{
            $ot_application_id= $request->ot_application_id;
            $official_time_application = OfficialTimeApplication::findOrFail($ot_application_id); 
            $official_time_application->date_from = $request->date_from;
            $official_time_application->date_to = $request->date_to;
            $official_time_application->time_from = $request->time_from;
            $official_time_application->time_to = $request->time_to;
            $official_time_application->date = date('Y-m-d');
            $official_time_application->update();
         
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_time_application_id = $official_time_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_time_requirement = $this->storeOfficialTimeApplicationRequirement($official_time_application_id);
                        $official_time_requirement_id = $official_time_requirement->id;

                        if($official_time_requirement){
                            $filename = config('enums.storage.leave') . '/' 
                                        . $official_time_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_time_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_time_requirement_id = OtApplicationRequirement::where('id','=',$official_time_requirement->id)->first();  
                                if($official_time_requirement  ){
                                    $official_time_requirement_name = $requirement->getleaveOriginalName();
                                    $official_time_requirement =  OtApplicationRequirement::findOrFail($official_time_requirement->id);
                                    $official_time_requirement->name = $official_time_requirement_name;
                                    $official_time_requirement->filename = $uploaded_image;
                                    $official_time_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Update";
            $official_time_logs = $this->storeOfficialTimeApplicationLog($official_time_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
      
    }

    public function storeOfficialTimeApplicationRequirement($official_time_application_id)
    {
        try {
            $official_time_application_requirement = new OtApplicationRequirement();                       
            $official_time_application_requirement->official_time_application_id = $official_time_application_id;
            $official_time_application_requirement->save();

            return $official_time_application_requirement;
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
    }
    public function storeOfficialTimeApplicationLog($official_time_application_id,$process_name)
    {
        try {
            $user_id="1";
            $official_time_application_log = new ModelsOtApplicationLog();                       
            $official_time_application_log->official_time_application_id = $official_time_application_id;
            $official_time_application_log->action_by = $user_id;
            $official_time_application_log->process_name = $process_name;
            $official_time_application_log->status = "applied";
            $official_time_application_log->date = date('Y-m-d');
            $official_time_application_log->save();

            return $official_time_application_log;
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(OfficialTimeApplication $officialTimeApplication)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OfficialTimeApplication $officialTimeApplication)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OfficialTimeApplication $officialTimeApplication)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OfficialTimeApplication $officialTimeApplication)
    {
        //
    }
}
