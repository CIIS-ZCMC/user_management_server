<?php

namespace App\Http\Controllers\LeaveAndOverTime;

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
        $leave_types = LeaveType::with('logs.employeeProfile.personalInformation', 'requirements.logs.employeeProfile.personalInformation','attachments')->get();
        $leave_types_result = $leave_types->map(function ($leave_type) {
                return [
                    'id' => $leave_type->id,
                    'name' => $leave_type->name,
                    'description' => $leave_type->description,
                    'period' => $leave_type->period,
                    'file_date' => $leave_type->file_date,
                    'code' => $leave_type->code,
                    'is_active' => $leave_type->is_active,
                    'is_special' => $leave_type->is_special,
                    'leave_credit_year' => $leave_type->leave_credit_year ,
                    'logs' => $leave_type->logs->map(function ($log) {
                        $process_name=$log->action;
                        $action ="";
                        $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                       
                        $date=$log->date;
                        $formatted_date=Carbon::parse($date)->format('M d,Y');
                        return [
                            'id' => $log->id,
                            'leave_application_id' => $log->leave_application_id,
                            'action_by' => "{$first_name} {$last_name}" ,
                            'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                            'action' => $log->action,
                            'date' => $formatted_date,
                            'time' => $log->time,
                          
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
                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                    'action' => $log->action,
                                    'date' => $log->date,
                                    'time' => $log->time,
                                ];
                            }),
                        ];
                    }),
                    'attachments' => $leave_type->attachments->map(function ($attachment) {
                        return [
                            'id' => $attachment->id,
                            'name' => $attachment->file_name,
                            
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

    public function store(Request $request)
    {
      
        try{
          

            $employee_id = $request->employee_id; 
            $filename="";
            $process_name="Add";
            $leave_type = new LeaveType();
            $leave_type->name = ucwords($request->name);
            $leave_type->description = $request->description;
            $leave_type->period = $request->period;
            $leave_type->file_date = $request->file_date;
            $input_name = $request->name;
            $name_codes = explode(' ', $input_name);
            $firstLetters = '';
            foreach ($name_codes as $name_code) {
                $firstLetters .= strtoupper(substr($name_code, 0, 1));
            }
            $leave_type->code = $firstLetters;
            $leave_type->is_active = true;
            $leave_type->is_special = $request->input('is_special');
            if($request->leave_credit_year != null)
            {
                $leave_type->leave_credit_year = $request->leave_credit_year;
            }
            else
            {
                $leave_type->leave_credit_year = "";
            }
           
            $leave_type->save();
            $attachment=$request->file('attachments');
            $leave_type_id=$leave_type->id;
            if($request->hasFile('attachments'))
            {
                foreach ($request->file('attachments') as $file) {
                    $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $uniqueFileName = $fileName . '_' . time() . '.' . $extension;
                    $path = $file->storeAs('public', $uniqueFileName);
                    $leave_attachment= new LeaveAttachment();
                    $leave_attachment->file_name= $fileName;
                    $leave_attachment->leave_type_id = $leave_type_id;
                    $leave_attachment->save();  
                }

            }
           
            $selectedRequirements = $request->input('requirements');
            $leave_type->requirements()->sync($selectedRequirements);
            $columnsString="";
            $this->storeLeaveTypeLog($leave_type_id,$process_name,$columnsString);
            return response()->json(['message' => 'Leave Type has been sucessfully saved','data' => $leave_type ], Response::HTTP_OK);
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
            $originalValues = $leave_type->getOriginal();
            $columnsString="";
            $leave_type->name = ucwords($request->name);
            $leave_type->description = $request->description;
            $leave_type->period = ucwords($request->period);
            $leave_type->file_date = $request->file_date;
            $input_name = $request->name;
            $name_codes = explode(' ', $input_name);
            $firstLetters = '';
            foreach ($name_codes as $name_code) {
                $firstLetters .= strtoupper(substr($name_code, 0, 1));
            }
            $leave_type->code = $firstLetters;
            $leave_type->is_active = true;
            $leave_type->is_special = $request->input('is_special');
            $leave_type->leave_credit_year = $request->leave_credit_year;
            $leave_type->update();
            if ($leave_type->isDirty()) {
                $changedColumns = $leave_type->getChanges();
              
                $columnsString = implode(', ', $changedColumns);
        
            } 
            $process_name="Update";
            $attachment=$request->file('attachments');
            $leave_type_id=$leave_type->id;
                if ($request->hasFile('attachments')) {
                    $attachment = $request->file('attachments');
                    $leave_type->attachments->delete(); 
                    foreach ($request->file('attachments') as $file) {
                        $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        $extension = $file->getClientOriginalExtension();
                        $uniqueFileName = $fileName . '_' . time() . '.' . $extension;
                        $path = $file->storeAs('public', $uniqueFileName);
                        $leave_attachment= new LeaveAttachment();
                        $leave_attachment->file_name= $fileName;
                        $leave_attachment->path= $$path;
                        $leave_attachment->leave_type_id = $leave_type_id;
                        $leave_attachment->save();  
                    }
               
            }
            $selectedRequirements = $request->input('requirements', []);
            $leave_type->requirements()->sync($selectedRequirements);
            $columnsString="";
            $this->storeLeaveTypeLog($leave_type_id,$process_name,$columnsString);
            return response()->json(['message' => 'Leave Type has been sucessfully updated','data' => $leave_type ], Response::HTTP_OK);
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

    public function storeLeaveTypeLog($leave_type_id,$process_name,$changedfields)
    {

        try {
            $user_id="1";

            $data = [
                'leave_type_id' => $leave_type_id,
                'action_by_id' => $user_id,
                'action' => $process_name,
                'date' => date('Y-m-d'),
                'time' => date('H:i:s'),
                'fields' => $changedfields
            ];

            $leave_type_log = LeaveTypeLog::create($data);    

            return $leave_type_log;
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
    }


    public function deactivateLeaveType(Request $request,$leave_type_id)
    {
        try{
            $columnsString="";
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
                $this->storeLeaveTypeLog($leave_type_id,$process_name,$columnsString);
                return response()->json(['data' => 'Success'], Response::HTTP_OK);
            }
           
            
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
        
    }

    public function reactivateLeaveType(Request $request,$leave_type_id)
    {
        try{
            $columnsString="";
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
                 $this->storeLeaveTypeLog($leave_type_id,$process_name,$columnsString);
                return response()->json(['data' => 'Success'], Response::HTTP_OK);
            }
           
            
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
        
    }

}
