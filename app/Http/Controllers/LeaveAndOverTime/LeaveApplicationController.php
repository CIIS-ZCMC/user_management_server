<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use Illuminate\Http\Response;
use App\Models\LeaveApplication;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeLeaveCredit as ResourcesEmployeeLeaveCredit;
use App\Http\Resources\LeaveApplication as ResourcesLeaveApplication;
use App\Models\AssignArea;
use App\Models\Department;
use App\Models\Division;
use App\Models\EmployeeLeaveCredit;
use App\Models\EmployeeProfile;
use App\Models\LeaveApplicationDateTime;
use App\Models\LeaveApplicationLog;
use App\Models\LeaveApplicationRequirement;
use App\Models\Section;
use Illuminate\Http\Request;
use App\Services\FileService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
class LeaveApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $file_service;
    public function __construct(
        FileService $file_service
    ) { 
        $this->file_service = $file_service; 
    }
    public function checkUserLeaveCredit(Request $request)
    {
        $leave_application_id = $request->leave_application_id;
        $leave_type_id = $request->leave_type_id;
        $leave_application_date_time=LeaveApplicationDateTime::findOrFail($leave_application_id);
        $total_days = 0;

        foreach ($leave_application_date_time as $leave_date_time) {
            $date_from = Carbon::parse($leave_date_time->date_from);
            $date_to = Carbon::parse($leave_date_time->date_to);
            $total_days += $date_to->diffInDays($date_from) + 1; 
        }
        $user_id = Auth::user()->id;
        $user = EmployeeProfile::where('id','=',$user_id)->first();
    
        $total_leave_credit_to_add = EmployeeLeaveCredit::where('employee_profile_id', $user->id)
            ->where('leave_type_id', $leave_type_id)
            ->where('operation', 'add')
            ->sum('credit_value');
        $total_leave_credit_to_deduct = EmployeeLeaveCredit::where('employee_profile_id', $user->id)
            ->where('leave_type_id', $leave_type_id)
            ->where('operation', 'deduct')
            ->sum('credit_value');

        // Calculate the difference
        $total_leave_credit = $total_leave_credit_to_add - $total_leave_credit_to_deduct;
    
        if($total_days >  $total_leave_credit){  
            return response()->json(['message' => 'Insufficient Leave Credit Value'], Response::HTTP_OK);
        }
    
    }
    public function index()
    {
        try{ 
            $leave_applications=[];
            $leave_applications =LeaveApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','dates','logs', 'requirements','employeeProfile.leaveCredits.leaveType'])->get();
            $leave_applications_result = $leave_applications->map(function ($leave_application) {
            $add=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
            ->where('operation', 'add')
            ->sum('credit_value');
            $deduct=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
            ->where('operation', 'deduct')
            ->sum('credit_value');
            $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
            $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
            $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
            $chief_name=null;
            $head_name=null;
            $supervisor_name=null;
            if ($division) {
                $division = Division::with('chief.personalInformation')->find($division);
                $chief_name = optional($division->chief->personalInformation)->first_name . '' . optional($division->chief->personalInformation)->last_name;
            }
            if($department)
            {
                $department = Department::with('head.personalInformation')->find($department);
                $head_name = optional($department->head->personalInformation)->first_name ?? null . '' . optional($division->head->personalInformation)->last_name ?? null;
            }
            if($section)
            {
                $section = Section::with('supervisor.personalInformation')->find($section);
                $supervisor_name = optional($section->head->personalInformation)->first_name ?? null . '' . optional($division->head->personalInformation)->last_name ?? null;
            }
            $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
            $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
            return [
                'id' => $leave_application->id,
                'leave_type_name' => $leave_application->leaveType->name,
                'is_special' => $leave_application->leaveType->is_special,
                'reference_number' => $leave_application->reference_number,
                'country' => $leave_application->country,
                'city' => $leave_application->city,
                'zip_code' => $leave_application->zip_code,
                'patient_type' => $leave_application->patient_type,
                'illness' => $leave_application->illness,
                'reason' => $leave_application->reason,
                'leave_credit_total' => $leave_application->leave_credit_total ,
                'leave_credit_balance' => $add - $deduct,
                'days_total' => $leave_application->leave_credit_total ,
                'status' => $leave_application->status ,
                'remarks' => $leave_application->remarks ,
                'date' => $leave_application->date ,
                'with_pay' => $leave_application->with_pay ,
                'employee_id' => $leave_application->employee_profile_id,
                'employee_name' => "{$first_name} {$last_name}" ,
                'division_head' =>$chief_name,
                'department_head' =>$head_name,
                'section_head' =>$supervisor_name,
                'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                'logs' => $leave_application->logs->map(function ($log) {
                    $process_name=$log->action;
                    $action ="";
                    $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                    if($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id ) 
                    {
                        $action =  $process_name . ' by ' . 'Division Head';
                    }
                    else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id)
                    {
                        $action =  $process_name . ' by ' . 'Supervisor';
                    }
                    else{
                        $action=  $process_name . ' by ' . $first_name .' '. $last_name;
                    }
                   
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
                        'process' => $action
                    ];
                }),
                'requirements' => $leave_application->requirements->map(function ($requirement) {
                    return [
                        'id' => $requirement->id,
                        'leave_application_id' => $requirement->leave_application_id,
                        'name' => $requirement->name,
                        'file_name' => $requirement->file_name,
                    ];
                }),
                'dates' => $leave_application->dates->map(function ($date) {
                    $formatted_date_from=Carbon::parse($date->date_from)->format('M d,Y');
                    $formatted_date_to=Carbon::parse($date->date_to)->format('M d,Y');
                    return [
                       
                        'id' => $date->id,
                        'leave_application_id' => $date->leave_application_id,
                        'date_from' => $formatted_date_from,
                        'date_to' => $formatted_date_to,

                    ];
                }),

              
            ];


             
        });
           
             return response()->json(['data' => $leave_applications_result], Response::HTTP_OK);
        }catch(\Throwable $th){
        
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    public function getEmployeeLeaveCredit(Request $request)
    {
      
        $results = EmployeeProfile::with(['personalInformation','leaveCredits.leaveType'])
        ->get()
        ->map(function ($employee) {
            $leaveCredits = $employee->leaveCredits->groupBy('leaveType.id');
            $result = [];

            foreach ($leaveCredits as $leave_type => $credits) {
                $total_balance = $credits->sum(function ($credit) {
                    return ($credit->operation === 'add') ? $credit->credit_value : -$credit->credit_value;
                });

                $result[] = [
                    'leave_type' => $leave_type,
                    'total_balance' => $total_balance,
                ];
            }

            return [
                'employee_id' => $employee->id,
                'employee_name' =>   $employee->personalInformation->first_name,
                'leave_credit_balance' => $result,
            ];
        });

     return response()->json(['employee_leave_credit_balance' => $results]);
    }
    public function getEmployeeLeaveCreditLogs(Request $request)
    {
      
        $results =EmployeeProfile::with(['personalInformation:id,first_name,last_name,middle_name', 'leaveCredits.leaveType:id,name'])
        ->select('date_hired','personal_information_id','id')
        ->get();
         return response()->json(['data' => $results]);
    }
    public function getUserLeaveCreditsLogs()
    {
        try{ 
            $user_id = Auth::user()->id;
            $user = EmployeeProfile::where('id','=',$user_id)->first();
            $leave_credits=[];
            
           $leave_credits =EmployeeLeaveCredit::with('leaveType:id,name')->where('employee_profile_id','=',$user->id)->get();
          
           
          
             return response()->json(['data' => $leave_credits], Response::HTTP_OK);
        }catch(\Throwable $th){
        
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    public function getUserLeaveApplication()
    {
        try{ 
            $user_id = Auth::user()->id;
            $user = EmployeeProfile::where('id','=',$user_id)->first();
            $leave_applications=[];
            $leave_applications = LeaveApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
            ->where('employee_profile_id',$user->id )->get();
            $leave_applications_result = $leave_applications->map(function ($leave_application) {
                $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                $chief_name=null;
                $head_name=null;
                $supervisor_name=null;
                if ($division) {
                    $division = Division::with('chief.personalInformation')->find($division);
                    $chief_name = optional($division->chief->personalInformation)->first_name . '' . optional($division->chief->personalInformation)->last_name;
                }
                if($department)
                {
                    $department = Department::with('head.personalInformation')->find($department);
                    $head_name = optional($department->head->personalInformation)->first_name ?? null . '' . optional($division->head->personalInformation)->last_name ?? null;
                }
                if($section)
                {
                    $section = Section::with('supervisor.personalInformation')->find($section);
                    $supervisor_name = optional($section->head->personalInformation)->first_name ?? null . '' . optional($division->head->personalInformation)->last_name ?? null;
                }
                $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                return [
                    'id' => $leave_application->id,
                    'leave_type_name' => $leave_application->leaveType->name,
                    'is_special' => $leave_application->leaveType->is_special,
                    'reference_number' => $leave_application->reference_number,
                    'country' => $leave_application->country,
                    'city' => $leave_application->city,
                    'zip_code' => $leave_application->zip_code,
                    'patient_type' => $leave_application->patient_type,
                    'illness' => $leave_application->illness,
                    'reason' => $leave_application->reason,
                    'leave_credit_total' => $leave_application->leave_credit_total ,
                    'leave_credit_balance' => $add - $deduct,
                    'days_total' => $leave_application->leave_credit_total ,
                    'status' => $leave_application->status ,
                    'remarks' => $leave_application->remarks ,
                    'date' => $leave_application->date ,
                    'with_pay' => $leave_application->with_pay ,
                    'employee_id' => $leave_application->employee_profile_id,
                    'employee_name' => "{$first_name} {$last_name}" ,
                    'division_head' =>$chief_name,
                    'department_head' =>$head_name,
                    'section_head' =>$supervisor_name,
                    'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                    'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                    'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                    'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                    'logs' => $leave_application->logs->map(function ($log) {
                        $process_name=$log->action;
                        $action ="";
                        $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                        if($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id ) 
                        {
                            $action =  $process_name . ' by ' . 'Division Head';
                        }
                        else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id)
                        {
                            $action =  $process_name . ' by ' . 'Supervisor';
                        }
                        else{
                            $action=  $process_name . ' by ' . $first_name .' '. $last_name;
                        }
                       
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
                            'process' => $action
                        ];
                    }),
                    'requirements' => $leave_application->requirements->map(function ($requirement) {
                        return [
                            'id' => $requirement->id,
                            'leave_application_id' => $requirement->leave_application_id,
                            'name' => $requirement->name,
                            'file_name' => $requirement->file_name,
                        ];
                    }),
                    'dates' => $leave_application->dates->map(function ($date) {
                        $formatted_date_from=Carbon::parse($date->date_from)->format('M d,Y');
                        $formatted_date_to=Carbon::parse($date->date_to)->format('M d,Y');
                        return [
                           
                            'id' => $date->id,
                            'leave_application_id' => $date->leave_application_id,
                            'date_from' => $formatted_date_from,
                            'date_to' => $formatted_date_to,
    
                        ];
                    }),
    
                  
                ];
    
    
                 
            });
           
            // Compute total leave credits to add
            $total_leave_credit_to_add = EmployeeLeaveCredit::where('employee_profile_id', '1')
            ->where('operation', 'add')
            ->sum('credit_value');

            // Compute total leave credits to deduct
            $total_leave_credit_to_deduct = EmployeeLeaveCredit::where('employee_profile_id', '1')
                ->where('operation', 'deduct')
                ->sum('credit_value');

            // Calculate the difference
            $total_leave_credit = $total_leave_credit_to_add - $total_leave_credit_to_deduct;
             return response()->json(['data' => $leave_applications,'total_leave_credit'=> $total_leave_credit], Response::HTTP_OK);
        }catch(\Throwable $th){
        
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function getUserLeaveApplicationLogs()
    {
        try{ 
            $user_id = Auth::user()->id;
            $user = EmployeeProfile::where('id','=',$user_id)->first();
            $leave_applications=[];
            
           $leave_applications =LeaveApplication::where('user_id','=',$user->id)->get();
           $leave_application_resource=ResourcesLeaveApplication::collection($leave_applications);
           
            // Compute total leave credits to add
            $total_leave_credit_to_add = EmployeeLeaveCredit::where('employee_profile_id', $user->id)
            ->where('operation', 'add')
            ->sum('$user->id');

            // Compute total leave credits to deduct
            $total_leave_credit_to_deduct = EmployeeLeaveCredit::where('employee_profile_id', $user->id)
                ->where('operation', 'deduct')
                ->sum('credit_value');

            // Calculate the difference
            $total_leave_credit = $total_leave_credit_to_add - $total_leave_credit_to_deduct;
             return response()->json(['data' => $leave_application_resource,'total_leave_credit'=> $total_leave_credit], Response::HTTP_OK);
        }catch(\Throwable $th){
        
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
   
    public function getLeaveApplications(Request $request)
    {
        $status = $request->status; 
        $employee_id = $request->employee_id; 
        $leave_applications = [];
        $division = AssignArea::where('employee_profile_id',$employee_id)->value('division_id');
        if($status == 'applied'){
            $section = AssignArea::where('employee_profile_id',$employee_id)->value('section_id');
            $hr_head_id = Section::where('id', $section)->value('supervisor_employee_profile_id');
            if($hr_head_id === $employee_id) {
                $leave_applications = LeaveApplication::with(['employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
                ->where('status', 'applied') 
                ->get();
                $leave_applications_result = $leave_applications->map(function ($leave_application) {
                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                    $chief_name=null;
                    $head_name=null;
                    $supervisor_name=null;
                    if ($division) {
                        $division = Division::with('chief.personalInformation')->find($division);
                        $chief_name = optional($division->chief->personalInformation)->first_name . '' . optional($division->chief->personalInformation)->last_name;
                    }
                    if($department)
                    {
                        $department = Department::with('head.personalInformation')->find($department);
                        $head_name = optional($department->head->personalInformation)->first_name ?? null . '' . optional($division->head->personalInformation)->last_name ?? null;
                    }
                    if($section)
                    {
                        $section = Section::with('supervisor.personalInformation')->find($section);
                        $supervisor_name = optional($section->head->personalInformation)->first_name ?? null . '' . optional($division->head->personalInformation)->last_name ?? null;
                    }
                    $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $leave_application->id,
                        'leave_type_name' => $leave_application->leaveType->name,
                        'is_special' => $leave_application->leaveType->is_special,
                        'reference_number' => $leave_application->reference_number,
                        'country' => $leave_application->country,
                        'city' => $leave_application->city,
                        'zip_code' => $leave_application->zip_code,
                        'patient_type' => $leave_application->patient_type,
                        'illness' => $leave_application->illness,
                        'reason' => $leave_application->reason,
                        'leave_credit_total' => $leave_application->leave_credit_total ,
                        'days_total' => $leave_application->leave_credit_total ,
                        'status' => $leave_application->status ,
                        'remarks' => $leave_application->remarks ,
                        'date' => $leave_application->date ,
                        'with_pay' => $leave_application->with_pay ,
                        'employee_id' => $leave_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'division_head' =>$chief_name,
                        'department_head' =>$head_name,
                        'section_head' =>$supervisor_name,
                        'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $leave_application->logs->map(function ($log) {
                            $process_name=$log->action;
                            $action ="";
                            $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                            $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                            if($process_name === 'applied')
                            {
                                $action=  $process_name . ' by ' . $first_name .' '. $last_name;
                            }
                            else
                            {
                                $action = $process_name;

                            }
                        
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
                                'process' => $action
                            ];
                        }),
                        'requirements' => $leave_application->requirements->map(function ($requirement) {
                            return [
                                'id' => $requirement->id,
                                'leave_application_id' => $requirement->leave_application_id,
                                'name' => $requirement->name,
                                'file_name' => $requirement->file_name,
                            ];
                        }),
                        'dates' => $leave_application->dates->map(function ($date) {
                            $formatted_date_from=Carbon::parse($date->date_from)->format('M d,Y');
                            $formatted_date_to=Carbon::parse($date->date_to)->format('M d,Y');
                            return [
                            
                                'id' => $date->id,
                                'leave_application_id' => $date->leave_application_id,
                                'date_from' => $formatted_date_from,
                                'date_to' => $formatted_date_to,
        
                            ];
                        }),
                    ];
                });
            

                return response()->json(['leave_applications' => $leave_applications_result]);
            } 
        }
        else if($status == 'for-approval-division-head'){
                $divisionHeadId = Division::where('id', $division)->value('chief_employee_profile_id');
                if($divisionHeadId === $employee_id) {
                    $leave_applications = LeaveApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
                    ->whereHas('employeeProfile.assignedArea', function ($query) use ($division) {
                        $query->where('id', $division);
                    })
                    ->where('status', 'for-approval-division-head') 
                    ->get();

                    $leave_applications_result = $leave_applications->map(function ($leave_application) {
                        $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                        $chief_name=null;
                        $head_name=null;
                        $supervisor_name=null;
                        if ($division) {
                            $division = Division::with('chief.personalInformation')->find($division);
                            $chief_name = optional($division->chief->personalInformation)->first_name . '' . optional($division->chief->personalInformation)->last_name;
                        }
                        if($department)
                        {
                            $department = Department::with('head.personalInformation')->find($department);
                            $head_name = optional($department->head->personalInformation)->first_name ?? null . '' . optional($division->head->personalInformation)->last_name ?? null;
                        }
                        if($section)
                        {
                            $section = Section::with('supervisor.personalInformation')->find($section);
                            $supervisor_name = optional($section->head->personalInformation)->first_name ?? null . '' . optional($division->head->personalInformation)->last_name ?? null;
                        }
                    $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                        return [
                            'id' => $leave_application->id,
                            'leave_type_name' => $leave_application->leaveType->name,
                            'is_special' => $leave_application->leaveType->is_special,
                            'reference_number' => $leave_application->reference_number,
                            'country' => $leave_application->country,
                            'city' => $leave_application->city,
                            'zip_code' => $leave_application->zip_code,
                            'patient_type' => $leave_application->patient_type,
                            'illness' => $leave_application->illness,
                            'reason' => $leave_application->reason,
                            'leave_credit_total' => $leave_application->leave_credit_total ,
                            'days_total' => $leave_application->leave_credit_total ,
                            'status' => $leave_application->status ,
                            'remarks' => $leave_application->remarks ,
                            'date' => $leave_application->date ,
                            'with_pay' => $leave_application->with_pay ,
                            'employee_id' => $leave_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}" ,
                            'division_head' =>$chief_name,
                            'department_head' =>$head_name,
                            'section_head' =>$supervisor_name,
                            'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                            'logs' => $leave_application->logs->map(function ($log) {
                                $process_name=$log->action;
                                $action ="";
                                $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                                $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                                if($process_name === 'applied')
                                {
                                    $action=  $process_name . ' by ' . $first_name .' '. $last_name;
                                }
                                else
                                {
                                    $action = $process_name;

                                }
                            
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
                                    'process' => $action
                                ];
                            }),
                            'requirements' => $leave_application->requirements->map(function ($requirement) {
                                return [
                                    'id' => $requirement->id,
                                    'leave_application_id' => $requirement->leave_application_id,
                                    'name' => $requirement->name,
                                    'file_name' => $requirement->file_name,
                                ];
                            }),
                            'dates' => $leave_application->dates->map(function ($date) {
                                $formatted_date_from=Carbon::parse($date->date_from)->format('M d,Y');
                                $formatted_date_to=Carbon::parse($date->date_to)->format('M d,Y');
                                return [
                                
                                    'id' => $date->id,
                                    'leave_application_id' => $date->leave_application_id,
                                    'date_from' => $formatted_date_from,
                                    'date_to' => $formatted_date_to,
            
                                ];
                            }),
                        ];
                    });
                

                    return response()->json(['leave_applications' => $leave_applications_result]);
                } 
        }
        else if($status == 'for-approval-department-head'){
            $department = AssignArea::where('employee_profile_id',$employee_id)->value('department_id');
            $departmentHeadId = Department::where('id', $department)->value('head_employee_profile_id');
            $training_officer_id = Department::where('id', $department)->value('training_officer_employee_profile_id');
            if($departmentHeadId === $employee_id || $training_officer_id === $employee_id) {
                $leave_applications = LeaveApplication::with(['employeeProfile.assignedArea.department','employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
                ->whereHas('employeeProfile.assignedArea', function ($query) use ($department) {
                    $query->where('id', $department);
                })
                ->where('status', 'for-approval-department-head') 
                ->get();
    
                $leave_applications_result = $leave_applications->map(function ($leave_application) {
                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                    $chief_name=null;
                    $head_name=null;
                    $supervisor_name=null;
                    if ($division) {
                        $division = Division::with('chief.personalInformation')->find($division);
                        $chief_name = optional($division->chief->personalInformation)->first_name . '' . optional($division->chief->personalInformation)->last_name;
                    }
                    if($department)
                    {
                        $department = Department::with('head.personalInformation')->find($department);
                        $head_name = optional($department->head->personalInformation)->first_name ?? null . '' . optional($division->head->personalInformation)->last_name ?? null;
                    }
                    if($section)
                    {
                        $section = Section::with('supervisor.personalInformation')->find($section);
                        $supervisor_name = optional($section->head->personalInformation)->first_name ?? null . '' . optional($division->head->personalInformation)->last_name ?? null;
                    }
                    $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $leave_application->id,
                        'leave_type_name' => $leave_application->leaveType->name,
                        'is_special' => $leave_application->leaveType->is_special,
                        'reference_number' => $leave_application->reference_number,
                        'country' => $leave_application->country,
                        'city' => $leave_application->city,
                        'zip_code' => $leave_application->zip_code,
                        'patient_type' => $leave_application->patient_type,
                        'illness' => $leave_application->illness,
                        'reason' => $leave_application->reason,
                        'leave_credit_total' => $leave_application->leave_credit_total ,
                        'days_total' => $leave_application->leave_credit_total ,
                        'status' => $leave_application->status ,
                        'remarks' => $leave_application->remarks ,
                        'date' => $leave_application->date ,
                        'with_pay' => $leave_application->with_pay ,
                        'employee_id' => $leave_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'division_head' =>$chief_name,
                        'department_head' =>$head_name,
                        'section_head' =>$supervisor_name,
                        'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $leave_application->logs->map(function ($log) {
                            $process_name=$log->action;
                            $action ="";
                            $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                            $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                            if($process_name === 'applied')
                            {
                                $action=  $process_name . ' by ' . $first_name .' '. $last_name;
                            }
                            else
                            {
                                $action = $process_name;

                            }
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
                                'process' => $action
                            ];
                        }),
                        'requirements' => $leave_application->requirements->map(function ($requirement) {
                            return [
                                'id' => $requirement->id,
                                'leave_application_id' => $requirement->leave_application_id,
                                'name' => $requirement->name,
                                'file_name' => $requirement->file_name,
                            ];
                        }),
                        'dates' => $leave_application->dates->map(function ($date) {
                            $formatted_date_from=Carbon::parse($date->date_from)->format('M d,Y');
                            $formatted_date_to=Carbon::parse($date->date_to)->format('M d,Y');
                            return [
                               
                                'id' => $date->id,
                                'leave_application_id' => $date->leave_application_id,
                                'date_from' => $formatted_date_from,
                                'date_to' => $formatted_date_to,
        
                            ];
                        }),
                    ];
                });
                return response()->json(['leave_applications' => $leave_applications_result]);
            }
        }
        else if($status == 'for-approval-section-head'){
            $section = AssignArea::where('employee_profile_id',$employee_id)->value('section_id');
            $sectionHeadId = Section::where('id', $section)->value('supervisor_employee_profile_id');
            if($sectionHeadId === $employee_id) {
      
                $leave_applications = LeaveApplication::with(['employeeProfile.assignedArea.section','employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
                ->whereHas('employeeProfile.assignedArea', function ($query) use ($section) {
                    $query->where('id', $section);
                })
                ->where('status', 'for-approval-section-head') 
                ->get();
    
                $leave_applications_result = $leave_applications->map(function ($leave_application) {
                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                    $chief_name=null;
                    $head_name=null;
                    $supervisor_name=null;
                    if ($division) {
                        $division = Division::with('chief.personalInformation')->find($division);
                        $chief_name = optional($division->chief->personalInformation)->first_name . '' . optional($division->chief->personalInformation)->last_name;
                    }
                    if($department)
                    {
                        $department = Department::with('head.personalInformation')->find($department);
                        $head_name = optional($department->head->personalInformation)->first_name ?? null . '' . optional($division->head->personalInformation)->last_name ?? null;
                    }
                    if($section)
                    {
                        $section = Section::with('supervisor.personalInformation')->find($section);
                        $supervisor_name = optional($section->head->personalInformation)->first_name ?? null . '' . optional($division->head->personalInformation)->last_name ?? null;
                    }
                    $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $leave_application->id,
                        'leave_type_name' => $leave_application->leaveType->name,
                        'is_special' => $leave_application->leaveType->is_special,
                        'reference_number' => $leave_application->reference_number,
                        'country' => $leave_application->country,
                        'city' => $leave_application->city,
                        'zip_code' => $leave_application->zip_code,
                        'patient_type' => $leave_application->patient_type,
                        'illness' => $leave_application->illness,
                        'reason' => $leave_application->reason,
                        'leave_credit_total' => $leave_application->leave_credit_total ,
                        'days_total' => $leave_application->leave_credit_total ,
                        'status' => $leave_application->status ,
                        'remarks' => $leave_application->remarks ,
                        'date' => $leave_application->date ,
                        'with_pay' => $leave_application->with_pay ,
                        'employee_id' => $leave_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'division_head' =>$chief_name,
                        'department_head' =>$head_name,
                        'section_head' =>$supervisor_name,
                        'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $leave_application->logs->map(function ($log) {
                            $process_name=$log->action;
                            $action ="";
                            $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                            $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                            if($process_name === 'applied')
                            {
                                $action=  $process_name . ' by ' . $first_name .' '. $last_name;
                            }
                            else
                            {
                                $action = $process_name;

                            }
                        
                           
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
                                'process' => $action
                            ];
                        }),
                        'requirements' => $leave_application->requirements->map(function ($requirement) {
                            return [
                                'id' => $requirement->id,
                                'leave_application_id' => $requirement->leave_application_id,
                                'name' => $requirement->name,
                                'file_name' => $requirement->file_name,
                            ];
                        }),
                        'dates' => $leave_application->dates->map(function ($date) {
                            $formatted_date_from=Carbon::parse($date->date_from)->format('M d,Y');
                            $formatted_date_to=Carbon::parse($date->date_to)->format('M d,Y');
                            return [
                               
                                'id' => $date->id,
                                'leave_application_id' => $date->leave_application_id,
                                'date_from' => $formatted_date_from,
                                'date_to' => $formatted_date_to,
        
                            ];
                        }),
                    ];
                });
            
    
                return response()->json(['leave_applications' => $leave_applications_result]);
            } 
        }
        else if($status == 'declined'){
            $leave_applications = LeaveApplication::with(['employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
            ->whereHas('logs', function ($query) use ($employee_id) {
                $query->where('action_by_id', $employee_id);
            })
                ->where('status', 'declined') 
                ->get();
            $leave_applications_result = $leave_applications->map(function ($leave_application) {
                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                    $chief_name=null;
                    $head_name=null;
                    $supervisor_name=null;
                    if ($division) {
                        $division = Division::with('chief.personalInformation')->find($division);
                        $chief_name = optional($division->chief->personalInformation)->first_name . '' . optional($division->chief->personalInformation)->last_name;
                    }
                    if($department)
                    {
                        $department = Department::with('head.personalInformation')->find($department);
                        $head_name = optional($department->head->personalInformation)->first_name ?? null . '' . optional($division->head->personalInformation)->last_name ?? null;
                    }
                    if($section)
                    {
                        $section = Section::with('supervisor.personalInformation')->find($section);
                        $supervisor_name = optional($section->head->personalInformation)->first_name ?? null . '' . optional($division->head->personalInformation)->last_name ?? null;
                    }
                    $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $leave_application->id,
                        'leave_type_name' => $leave_application->leaveType->name,
                        'is_special' => $leave_application->leaveType->is_special,
                        'reference_number' => $leave_application->reference_number,
                        'country' => $leave_application->country,
                        'city' => $leave_application->city,
                        'zip_code' => $leave_application->zip_code,
                        'patient_type' => $leave_application->patient_type,
                        'illness' => $leave_application->illness,
                        'reason' => $leave_application->reason,
                        'leave_credit_total' => $leave_application->leave_credit_total ,
                        'days_total' => $leave_application->leave_credit_total ,
                        'status' => $leave_application->status ,
                        'remarks' => $leave_application->remarks ,
                        'date' => $leave_application->date ,
                        'with_pay' => $leave_application->with_pay ,
                        'employee_id' => $leave_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'division_head' =>$chief_name,
                        'department_head' =>$head_name,
                        'section_head' =>$supervisor_name,
                        'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $leave_application->logs->map(function ($log) {
                            $process_name=$log->action;
                            $action ="";
                            $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                            $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                            if($process_name === 'applied')
                            {
                                $action=  $process_name . ' by ' . $first_name .' '. $last_name;
                            }
                            else
                            {
                                $action = $process_name;

                            }
                        
                           
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
                                'process' => $action
                            ];
                        }),
                        'requirements' => $leave_application->requirements->map(function ($requirement) {
                            return [
                                'id' => $requirement->id,
                                'leave_application_id' => $requirement->leave_application_id,
                                'name' => $requirement->name,
                                'file_name' => $requirement->file_name,
                            ];
                        }),
                        'dates' => $leave_application->dates->map(function ($date) {
                            $formatted_date_from=Carbon::parse($date->date_from)->format('M d,Y');
                            $formatted_date_to=Carbon::parse($date->date_to)->format('M d,Y');
                            return [
                               
                                'id' => $date->id,
                                'leave_application_id' => $date->leave_application_id,
                                'date_from' => $formatted_date_from,
                                'date_to' => $formatted_date_to,
        
                            ];
                        }),
                    ];
                });
            
    
                return response()->json(['leave_applications' => $leave_applications_result]);
        }
        else{
            $leave_applications = LeaveApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
            ->where('employee_profile_id',$employee_id )->get();
        }
        if (isset($request->search)) {
            $search = $request->search; 
            $leave_applications = $leave_applications->where('reference_number','like', '%' .$search . '%');
                                                 
            $leave_applications = isset($search) && $search; 
        }
        return ResourcesLeaveApplication::collection($leave_applications->paginate(50));
    }

    public function getDivisionLeaveApplications(Request $request)
    {
        $employee_id = 2;
        $division = AssignArea::where('employee_profile_id',$employee_id)->value('division_id');
        $divisionHeadId = Division::where('id', $division)->value('chief_employee_profile_id');
        if($divisionHeadId === $employee_id) {
  
            $leave_applications = LeaveApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
            ->whereHas('employeeProfile.assignedArea', function ($query) use ($division) {
                $query->where('id', $division);
            })
            ->where('status', 'for-approval-division') 
            ->get();

            $leave_applications_result = $leave_applications->map(function ($leave_application) {
           
                $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                return [
                    'id' => $leave_application->id,
                    'leave_type_name' => $leave_application->leaveType->name,
                    'is_special' => $leave_application->leaveType->is_special,
                    'reference_number' => $leave_application->reference_number,
                    'country' => $leave_application->country,
                    'city' => $leave_application->city,
                    'zip_code' => $leave_application->zip_code,
                    'patient_type' => $leave_application->patient_type,
                    'illness' => $leave_application->illness,
                    'reason' => $leave_application->reason,
                    'leave_credit_total' => $leave_application->leave_credit_total ,
                    'days_total' => $leave_application->leave_credit_total ,
                    'status' => $leave_application->status ,
                    'remarks' => $leave_application->remarks ,
                    'date' => $leave_application->date ,
                    'with_pay' => $leave_application->with_pay ,
                    'employee_id' => $leave_application->employee_profile_id,
                    'employee_name' => "{$first_name} {$last_name}" ,
                    'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                    'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                    'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                    'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                    'logs' => $leave_application->logs->map(function ($log) {
                        $process_name=$log->action;
                        $action ="";
                        $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                        if($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id ) 
                        {
                            $action =  $process_name . ' by ' . 'Division Head';
                        }
                        else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id)
                        {
                            $action =  $process_name . ' by ' . 'Supervisor';
                        }
                        else{
                            $action=  $process_name . ' by ' . $first_name .' '. $last_name;
                        }
                       
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
                            'process' => $action
                        ];
                    }),
                    'requirements' => $leave_application->requirements->map(function ($requirement) {
                        return [
                            'id' => $requirement->id,
                            'leave_application_id' => $requirement->leave_application_id,
                            'name' => $requirement->name,
                            'file_name' => $requirement->file_name,
                        ];
                    }),
                    'dates' => $leave_application->dates->map(function ($date) {
                        $formatted_date_from=Carbon::parse($date->date_from)->format('M d,Y');
                        $formatted_date_to=Carbon::parse($date->date_to)->format('M d,Y');
                        return [
                           
                            'id' => $date->id,
                            'leave_application_id' => $date->leave_application_id,
                            'date_from' => $formatted_date_from,
                            'date_to' => $formatted_date_to,
    
                        ];
                    }),
                ];
            });
        

            return response()->json(['leave_applications' => $leave_applications_result]);
        } else 
        {
        
            return response()->json(['error' =>  $divisionHeadId]);
    
        }
       
    }

    public function getDepartmentLeaveApplications(Request $request)
    {
        $employee_id = 2;
        $department = AssignArea::where('employee_profile_id',$employee_id)->value('department_id');
        $departmentHeadId = Department::where('id', $department)->value('head_employee_profile_id');
        if($departmentHeadId === $employee_id) {
  
            $leave_applications = LeaveApplication::with(['employeeProfile.assignedArea.department','employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
            ->whereHas('employeeProfile.assignedArea', function ($query) use ($department) {
                $query->where('id', $department);
            })
            ->where('status', 'for-approval-head') 
            ->get();

            $leave_applications_result = $leave_applications->map(function ($leave_application) {
           
                $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                return [
                    'id' => $leave_application->id,
                    'leave_type_name' => $leave_application->leaveType->name,
                    'is_special' => $leave_application->leaveType->is_special,
                    'reference_number' => $leave_application->reference_number,
                    'country' => $leave_application->country,
                    'city' => $leave_application->city,
                    'zip_code' => $leave_application->zip_code,
                    'patient_type' => $leave_application->patient_type,
                    'illness' => $leave_application->illness,
                    'reason' => $leave_application->reason,
                    'leave_credit_total' => $leave_application->leave_credit_total ,
                    'days_total' => $leave_application->leave_credit_total ,
                    'status' => $leave_application->status ,
                    'remarks' => $leave_application->remarks ,
                    'date' => $leave_application->date ,
                    'with_pay' => $leave_application->with_pay ,
                    'employee_id' => $leave_application->employee_profile_id,
                    'employee_name' => "{$first_name} {$last_name}" ,
                    'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                    'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                    'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                    'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                    'logs' => $leave_application->logs->map(function ($log) {
                        $process_name=$log->action;
                        $action ="";
                        $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                        if($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id ) 
                        {
                            $action =  $process_name . ' by ' . 'Division Head';
                        }
                        else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id)
                        {
                            $action =  $process_name . ' by ' . 'Supervisor';
                        }
                        else{
                            $action=  $process_name . ' by ' . $first_name .' '. $last_name;
                        }
                       
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
                            'process' => $action
                        ];
                    }),
                    'requirements' => $leave_application->requirements->map(function ($requirement) {
                        return [
                            'id' => $requirement->id,
                            'leave_application_id' => $requirement->leave_application_id,
                            'name' => $requirement->name,
                            'file_name' => $requirement->file_name,
                        ];
                    }),
                    'dates' => $leave_application->dates->map(function ($date) {
                        $formatted_date_from=Carbon::parse($date->date_from)->format('M d,Y');
                        $formatted_date_to=Carbon::parse($date->date_to)->format('M d,Y');
                        return [
                           
                            'id' => $date->id,
                            'leave_application_id' => $date->leave_application_id,
                            'date_from' => $formatted_date_from,
                            'date_to' => $formatted_date_to,
    
                        ];
                    }),
                ];
            });
        

            return response()->json(['leave_applications' => $leave_applications_result]);
        } else 
        {
        
            return response()->json(['error' =>  '']);
    
        }
       
    }

    public function getSectionLeaveApplications(Request $request)
    {
        $employee_id = 2;
        $section = AssignArea::where('employee_profile_id',$employee_id)->value('section_id');
        $sectionHeadId = Section::where('id', $section)->value('supervisor_employee_profile_id');
        if($sectionHeadId === $employee_id) {
  
            $leave_applications = LeaveApplication::with(['employeeProfile.assignedArea.section','employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
            ->whereHas('employeeProfile.assignedArea', function ($query) use ($section) {
                $query->where('id', $section);
            })
            ->where('status', 'applied') 
            ->get();

            $leave_applications_result = $leave_applications->map(function ($leave_application) {
           
                $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                return [
                    'id' => $leave_application->id,
                    'leave_type_name' => $leave_application->leaveType->name,
                    'is_special' => $leave_application->leaveType->is_special,
                    'reference_number' => $leave_application->reference_number,
                    'country' => $leave_application->country,
                    'city' => $leave_application->city,
                    'zip_code' => $leave_application->zip_code,
                    'patient_type' => $leave_application->patient_type,
                    'illness' => $leave_application->illness,
                    'reason' => $leave_application->reason,
                    'leave_credit_total' => $leave_application->leave_credit_total ,
                    'days_total' => $leave_application->leave_credit_total ,
                    'status' => $leave_application->status ,
                    'remarks' => $leave_application->remarks ,
                    'date' => $leave_application->date ,
                    'with_pay' => $leave_application->with_pay ,
                    'employee_id' => $leave_application->employee_profile_id,
                    'employee_name' => "{$first_name} {$last_name}" ,
                    'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                    'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                    'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                    'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                    'logs' => $leave_application->logs->map(function ($log) {
                        $process_name=$log->action;
                        $action ="";
                        $first_name = optional($log->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($log->employeeProfile->personalInformation)->last_name ?? null;
                        if($log->action_by_id  === optional($log->employeeProfile->assignedArea->division)->chief_employee_profile_id ) 
                        {
                            $action =  $process_name . ' by ' . 'Division Head';
                        }
                        else if ($log->action_by_id === optional($log->employeeProfile->assignedArea->department)->head_employee_profile_id || optional($log->employeeProfile->assignedArea->section)->supervisor_employee_profile_id)
                        {
                            $action =  $process_name . ' by ' . 'Supervisor';
                        }
                        else{
                            $action=  $process_name . ' by ' . $first_name .' '. $last_name;
                        }
                       
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
                            'process' => $action
                        ];
                    }),
                    'requirements' => $leave_application->requirements->map(function ($requirement) {
                        return [
                            'id' => $requirement->id,
                            'leave_application_id' => $requirement->leave_application_id,
                            'name' => $requirement->name,
                            'file_name' => $requirement->file_name,
                        ];
                    }),
                    'dates' => $leave_application->dates->map(function ($date) {
                        $formatted_date_from=Carbon::parse($date->date_from)->format('M d,Y');
                        $formatted_date_to=Carbon::parse($date->date_to)->format('M d,Y');
                        return [
                           
                            'id' => $date->id,
                            'leave_application_id' => $date->leave_application_id,
                            'date_from' => $formatted_date_from,
                            'date_to' => $formatted_date_to,
    
                        ];
                    }),
                ];
            });
        

            return response()->json(['leave_applications' => $leave_applications_result]);
        } else 
        {
        
            return response()->json(['error' =>  '']);
    
        }
       
    }

    public function updateLeaveApplicationStatus (Request $request)
    {
        try {
                $employee_id = $request->employee_id; 
                $user_id = Auth::user()->id;
                $user = EmployeeProfile::where('id','=',$user_id)->first();
                $division = AssignArea::where('employee_profile_id',$employee_id)->value('is_medical');
                $user_password=$user->password;
                $password=$request->password;
                if($user_password==$password)
                {
                            $message_action = '';
                            $action = '';
                            $new_status = '';
                            $status = $request->status;

                            if($status == 'for-approval-section-head' ){
                                $action = 'Aprroved by Supervisor';
                                $new_status='for-approval-division-head';
                                $message_action="Approved";
                            }
                            else if($status == 'for-approval-department-head'){
                                $action = 'Aprroved by Supervisor';
                                $new_status='for-approval-division-head';
                                $message_action="Approved";
                            }
                            else if($status == 'for-approval-division-head'){
                                $action = 'Aprroved by Division Head';
                                $new_status='approved';
                                $message_action="Approved";
                            }
                            else if($status == 'applied'){
                                $action = 'Verified by HRMO';
                                if($division === true)
                                {
                                    $new_status='for-approval-department-head';
                                    $message_action="verified";
                                }
                                else
                                {
                                    $new_status='for-approval-section-head';
                                    $message_action="verified";
                                }
                                
                            }
                            $leave_application_id = $request->leave_application_id;
                            $leave_applications = LeaveApplication::where('id','=', $leave_application_id)
                                                                    ->first();
                            if($leave_applications){    
                                $leave_application_log = new LeaveApplicationLog();
                                $leave_application_log->action = $action;
                                $leave_application_log->leave_application_id = $leave_application_id;
                                $leave_application_log->action_by = $user_id;
                                $leave_application_log->date = date('Y-m-d');
                                $leave_application_log->save();

                                $leave_application = LeaveApplication::findOrFail($leave_application_id);   
                                $leave_application->status = $new_status;
                                $leave_application->update();

                                if($new_status=="approved")
                                {
                                    $leave_application_date_time=LeaveApplicationDateTime::findOrFail($leave_application_id);
                                    $total_days = 0;
    
                                    foreach ($leave_application_date_time as $leave_date_time) {
                                        $date_from = Carbon::parse($leave_date_time->date_from);
                                        $date_to = Carbon::parse($leave_date_time->date_to);
                                        $total_days += $date_to->diffInDays($date_from) + 1; // Add 1 to include both the start and end dates

                                    }

                                    $employee_leave_credits = new EmployeeLeaveCredit();
                                    $employee_leave_credits->employee_profile_id = $user->id;
                                    $employee_leave_credits->leave_application_id = $leave_application_id;
                                    $employee_leave_credits->operation = "deduct";
                                    $employee_leave_credits->leave_credit = $total_days;
                                    $employee_leave_credits->date = date('Y-m-d');;
                                    $employee_leave_credits->save();
    
                                }
                                return response(['message' => 'Application has been sucessfully '.$message_action, 'data' => $leave_application], Response::HTTP_CREATED); 
                            }
                }           
            }
         catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
      
    }


    public function declineLeaveApplication(Request $request)
    {
        try {
                    $leave_application_id = $request->leave_application_id;
                    $leave_applications = LeaveApplication::where('id','=', $leave_application_id)
                                                            ->first();
                if($leave_applications)
                {
                        $user_id = Auth::user()->id;     
                        $user = EmployeeProfile::where('id','=',$user_id)->first();
                        $user_password=$user->password;
                        $password=$request->password;
                        if($user_password==$password)
                        {
                            if($user_id){
                                $leave_application_log = new LeaveApplicationLog();
                                $leave_application_log->action = 'declined';
                                $leave_application_log->leave_application_id = $leave_application_id;
                                $leave_application_log->date = date('Y-m-d');
                                $leave_application_log->action_by = $user_id;
                                $leave_application_log->save();

                                $leave_application = LeaveApplication::findOrFail($leave_application_id);
                                $leave_application->status = 'declined';
                                $leave_application->update();
                                return response(['message' => 'Application has been sucessfully declined', 'data' => $leave_application], Response::HTTP_CREATED);  
            
                            }
                         }
                }
            } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),  'error'=>true]);
        }
    }

    public function cancelLeaveApplication(Request $request)
    {
        try {
                    $leave_application_id = $request->leave_application_id;
                    $leave_applications = LeaveApplication::where('id','=', $leave_application_id)
                                                            ->first();
                if($leave_applications)
                {
                        $user_id = Auth::user()->id;     
                        $user = EmployeeProfile::where('id','=',$user_id)->first();
                        $user_password=$user->password;
                        $password=$request->password;
                        if($user_password==$password)
                        {
                            if($user_id){
                                $leave_application_log = new LeaveApplicationLog();
                                $leave_application_log->action = 'cancel';
                                $leave_application_log->leave_application_id = $leave_application_id;
                                $leave_application_log->date = date('Y-m-d');
                                $leave_application_log->action_by = $user_id;
                                $leave_application_log->save();

                                $leave_application = LeaveApplication::findOrFail($leave_application_id);
                                $leave_application->status = 'cancelled';
                                $leave_application->update();
                                return response(['message' => 'Application has been sucessfully cancelled', 'data' => $leave_application], Response::HTTP_CREATED);  
            
                            }
                         }
                }
            } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),  'error'=>true]);
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {  
        try{
            $leave_type_id = $request->leave_type_id;
            $user_id = Auth::user()->id;
            $user = EmployeeProfile::where('id','=',$user_id)->first();
            $user_status = $user->status;
            $division = AssignArea::where('employee_profile_id',$user->id)->value('division_id');
         
            if($user_status == 'Permanent')
            {
                $employee_leave_credit=EmployeeLeaveCredit::where('employee_id','=',$user->id)
                                                    ->where('leave_type_id','=', $leave_type_id)
                                                    ->first(); 
                $total_leave_credit=$employee_leave_credit->total_leave_credit;
                    if($total_leave_credit > 0)
                    {
                            $leave_application = new Leaveapplication();
                            $leave_application->leave_type_id = $leave_type_id;
                            $leave_application->reference_number = $request->reference_number;
                            $leave_application->country = $request->country;
                            $leave_application->city = $request->city;
                            $leave_application->patient_type = $request->patient_type;
                            $leave_application->illnes = $request->illnes;
                            $leave_application->reason = $request->reason;
                            $leave_application->with_pay =  $request->has('with_pay');
                            $leave_application->whole_day = $request->whole_day;
                            $leave_application->leave_credit_total = "2";
                            $leave_application->status = "applied";
                            $time=Carbon::now()->format('H:i:s');
                            $leave_application->date = date('Y-m-d');
                            $leave_application->time =  date('H:i:s');
                            $leave_application->save();
                            $date=$request->date_from;
                            if($date!=null)
                            {
                                foreach ($date as $dates) {
                                    $leave_application_date_time = new LeaveApplicationDateTime();
                                    $leave_application_date_time->date_from = $request->date_from;
                                    $leave_application_date_time->date_to = $request->date_to;
                                    $leave_application_date_time->time_from = $request->time_from;
                                    $leave_application_date_time->time_to = $request->time_to;
                                    $leave_application_date_time->save();
                                    }

                            }
                           
                
                            if ($request->hasFile('requirements')) {
                                $requirements = $request->file('requirements');
                                if($requirements){
                
                                    $leave_application_id = $leave_application->id; 
                                    foreach ($requirements as $requirement) {
                                        $leave_application_requirement = $this->storeLeaveApplicationRequirement($leave_application_id);
                                        $leave_application_requirement_id = $leave_application_requirement->id;
                
                                        if($leave_application_requirement){
                                    
                                            $filename = config('enums.storage.leave') . '/' 
                                                        . $leave_application_requirement_id ;
                
                                            $uploaded_image = $this->file_service->uploadRequirement($leave_application_requirement_id->id, $requirement, $filename, "REQ");
                
                                            if ($uploaded_image) {                     
                                                $leave_application_requirement_id = LeaveApplicationRequirement::where('id','=',$leave_application_requirement->id)->first();  
                                                if($leave_application_requirement  ){
                                                    $leave_application_requirement_name = $requirement->getleaveOriginalName();
                                                    $leave_application_requirement =  LeaveApplicationRequirement::findOrFail($leave_application_requirement->id);
                                                    $leave_application_requirement->name = $leave_application_requirement_name;
                                                    $leave_application_requirement->filename = $uploaded_image;
                                                    $leave_application_requirement->update();
                                                }                                      
                                            }                           
                                        }
                                    }
                                        
                                }     
                            }
                            $process_name="Applied";
                            $leave_application_logs = $this->storeLeaveApplicationLog($leave_application_id,$process_name);
                    }
            }
           
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    

    public function storeLeaveApplicationRequirement($leave_application_id)
    {
        try {
            $leave_application_requirement = new LeaveApplicationRequirement();                       
            $leave_application_requirement->leave_application_id = $leave_application_id;
            $leave_application_requirement->save();

            return $leave_application_requirement;
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
    }
    public function storeLeaveApplicationLog($leave_application_id,$process_name)
    {
        try {
            $user_id = Auth::user()->id;
            $user = EmployeeProfile::where('id','=',$user_id)->first();
            $leave_application_log = new LeaveApplicationLog();                       
            $leave_application_log->leave_application_id = $leave_application_id;
            $leave_application_log->action_by = $user->id;
            $leave_application_log->action = $process_name;
            $leave_application_log->status = "applied";
            $leave_application_log->date = date('Y-m-d');
            $leave_application_log->time =  date('H:i:s');
            $leave_application_log->save();
            return $leave_application_log;
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(LeaveApplication $leaveApplication)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaveApplication $leaveApplication)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeaveApplication $leaveApplication)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeaveApplication $leaveApplication)
    {
        //
    }
}
