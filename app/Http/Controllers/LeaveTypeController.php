<?php

namespace App\Http\Controllers;
use Intervention\Image\Facades\Image;
use Carbon\Carbon;
use App\Models\LeaveType;
use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveType as ResourcesLeaveType;
use App\Models\EmployeeProfile;
use App\Models\LeaveAttachment;
use App\Models\LeaveCredit;
use App\Models\LeaveTypeLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use \Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
class LeaveTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
       
        // $leaveTypes = LeaveType::with('logs.employeeProfile.personalInformation','requirements.logs.employeeProfile')->get();
        $leave_types = LeaveType::with('logs.employeeProfile.personalInformation', 'requirements.logs.employeeProfile.personalInformation')->get();

           
            $leave_types_result = $leave_types->map(function ($leave_type) {
                return [
                    'id' => $leave_type->id,
                    'name' => $leave_type->name,
                    'description' => $leave_type->description,
                    'period' => $leave_type->period,
                    'file_date' => $leave_type->file_date,
                    'code' => $leave_type->code,
                    'status' => $leave_type->status,
                    'is_special' => $leave_type->is_special,
                    'leave_credit_year' => $leave_type->leave_credit_year ,
                    'logs' => $leave_type->logs->map(function ($log) {
                        $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null ;
                        $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                        return [
                            'id' => $log->id,
                            'action_by' => "{$first_name} {$last_name}",
                            'action' => $log->action,
                            'date' => $log->date,
                        ];
                    }),
                    'requirements' => $leave_type->requirements->map(function ($requirement) {
                        return [
                            'id' => $requirement->id,
                            'name' => $requirement->name,
                            'logs' => $requirement->logs->map(function ($log) {
                                $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null ;
                                $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                                return [
                                    'id' => $log->id,
                                    'action_by' => "{$first_name} {$last_name}",
                                    'action' => $log->action,
                                    'date' => $log->date,
                                ];
                            }),
                        ];
                    }),
                ];
            });
            
             return response()->json(['data' => $leave_types_result], Response::HTTP_OK);
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
            $filename="";
            $process_name="Add";
            $leave_type = new LeaveType();
            $leave_type->name = ucwords($request->name);
            $leave_type->description = $request->description;
            $leave_type->period = $request->period;
            $leave_type->file_date = $request->file_date;
            $leave_type->leave_credit_id = $request->leave_credit_id;
            $code = preg_split("/[\s,_-]+/", $request->name);
            $leave_type->code = $code;
            $leave_type->status = 'active';
            $leave_type->is_special =$request->has('is_special');
            $leave_type->leave_credit_year = $request->leave_credit_year;
            foreach ($request->file('attachments') as $file) {
                // Generate a unique name for each file
                $file_name = time() . '_' . $file->getClientOriginalName();
    
                // Move the file to the storage directory
                $file->move(public_path('attachments'), $file_name);
                $leave_attachment= new LeaveAttachment();
                $leave_attachment->file_name= $file_name;
                $leave_attachment->save();
               
                
            }


            $leave_type->attachment = $filename;
            $leave_type->save();
            $leave_type_id=$leave_type->id;
            if (!empty($request->leave_requirements)) {
                $this->storeLeaveTypeRequirements($leave_type->id, $request->leave_requirements);
            } 

            $leave_type_logs = $this->storeLeaveTypeLog($leave_type_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id,LeaveType $leaveType)
    {
        try{
            $data = LeaveCredit::find($id);

            return response() -> json(['data' => $data], 200);
        }catch(\Throwable $th){
           
            return response() -> json(['message' => $th -> getMessage()], 500);
        }
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaveType $leaveType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id,Request $request, LeaveType $leaveType)
    {
        try{
            $leave_type = LeaveType::findOrFail($id);
            $leave_type->name = ucwords($request->name);
            $leave_type->description = $request->description;
            $leave_type->period = ucwords($request->period);
            $leave_type->file_date = $request->file_date;
            $leave_type->leave_credit_id = $request->leave_credit_id;
            $code = preg_split("/[\s,_-]+/", $request->name);
            $leave_type->code = $code;
            if ($request->hasFile('attachment')) {
                $attachment = $request->file('attachment');
                if ($attachment->isValid()) {
                    $extension = $attachment->getClientOriginalExtension();
                    $filename = $request->name . $extension;
                    $image_path = 'images/leave/attachment' . $filename;
                    Image::make($attachment)->save($image_path);
                    
                }
            }
            $leave_type->attachment = $filename;
            $leave_type->update();
            if (!empty($request->leave_requirements)) {
                $this->storeLeaveTypeRequirements($leave_type->id, $request->leave_requirements);
            } 
            $leave_type_id=$leave_type->id;
            $process_name="Update";
            $leave_type_logs = $this->storeLeaveTypeLog($leave_type_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeaveType $leaveType)
    {
        //
    }

    public function storeLeaveTypeRequirements($leave_type_id, $leave_requirements)
    {
        try { 
            $leave_type_requirements = LeaveType::findOrFail($leave_type_id);
            $leave_type_requirements->requirements()->sync($leave_requirements);
            $leave_type_requirements->update();
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function storeLeaveTypeLog($leave_type_id,$process_name)
    {
        try {
            $user_id="1";
            $leave_application_log = new LeaveTypeLog();                       
            $leave_application_log->leave_type_id = $leave_type_id                                                                ;
            $leave_application_log->action_by = $user_id;
            $leave_application_log->process_name = $process_name;
            $leave_application_log->date = now()->toDateString('Ymd');
            $leave_application_log->save();

            return $leave_application_log;
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
    }


    public function deactivateLeaveType(Request $request,$leave_type_id)
    {
        try{
            $user_id = Auth::user()->id;
            $user = EmployeeProfile::where('id','=',$user_id)->first();
            $user_password=$user->password;
            $password=$request->password;
            if($user_password==$password)
            {
                $deactivate_leave_type = LeaveType::findOrFail($leave_type_id);
                $deactivate_leave_type->status="deactivated";
                $deactivate_leave_type->reason=$request->reason;
                $deactivate_leave_type->update();
                $process_name="Deactivate";
                $leave_type_logs = $this->storeLeaveTypeLog($leave_type_id,$process_name);
                return response()->json(['data' => 'Success'], Response::HTTP_OK);
            }
           
            
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
        
    }

    public function reactivateLeaveType(Request $request,$leave_type_id)
    {
        try{
            $user_id = Auth::user()->id;
            $user = EmployeeProfile::where('id','=',$user_id)->first();
            $user_password=$user->password;
            $password=$request->password;
            if($user_password==$password)
            {
                $deactivate_leave_type = LeaveType::findOrFail($leave_type_id);
                $deactivate_leave_type->status="active";
                $deactivate_leave_type->reason=$request->reason;
                $deactivate_leave_type->update();
                $process_name="Reactivate";
                $leave_type_logs = $this->storeLeaveTypeLog($leave_type_id,$process_name);
                return response()->json(['data' => 'Success'], Response::HTTP_OK);
            }
           
            
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
        
    }

}
