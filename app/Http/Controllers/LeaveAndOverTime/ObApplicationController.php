<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Models\ObApplication;
use App\Http\Controllers\Controller;
use App\Http\Resources\ObApplication as ResourcesObApplication;
use App\Http\Resources\ObApplicationLog as ResourcesObApplicationLog;
use App\Http\Resources\OfficialBusinessApplication;
use App\Models\AssignArea;
use App\Models\Department;
use App\Models\Division;
use App\Models\EmployeeProfile;
use App\Models\LeaveType;
use App\Models\ObApplicationLog;
use App\Models\ObApplicationRequirement;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\FileService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
class ObApplicationController extends Controller
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
    public function index()
    {
        try{ 
                $official_business_applications =ObApplication::with(['employeeProfile.personalInformation','logs'])->get();
                $official_business_applications_result = $official_business_applications->map(function ($official_business_application) {
                        $division = AssignArea::where('employee_profile_id',$official_business_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$official_business_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$official_business_application->employee_profile_id)->value('section_id');
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
                    $first_name = optional($official_business_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($official_business_application->employeeProfile->personalInformation)->last_name ?? null;
                        return [
                            'id' => $official_business_application->id,
                            'date_from' => $official_business_application->date_from,
                            'date_to' => $official_business_application->date_to,
                            'time_from' => $official_business_application->time_from,
                            'time_to' => $official_business_application->time_to,
                            'reason' => $official_business_application->reason,
                            'status' => $official_business_application->status,
                            'employee_id' => $official_business_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}" ,
                            'division_head' =>$chief_name,
                            'department_head' =>$head_name,
                            'section_head' =>$supervisor_name,
                            'division_name' => $official_business_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $official_business_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $official_business_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $official_business_application->employeeProfile->assignedArea->unit->name ?? null,
                            'logs' => $official_business_application->logs->map(function ($log) {
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
                                    'leave_application_id' => $log->ob_application_id,
                                    'action_by' => "{$first_name} {$last_name}" ,
                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),
                          
                        ];
                    });
                    
                     return response()->json(['data' => $official_business_applications_result], Response::HTTP_OK);
                }catch(\Throwable $th){
                
                    return response()->json(['message' => $th->getMessage()], 500);
                }
           
        
    }

    public function getObApplications(Request $request)
    { 
       
    try{
        $status = $request->status; 
        $employee_id = $request->employee_id; 
        $official_business_applications = [];
        $division = AssignArea::where('employee_profile_id',$employee_id)->value('division_id');
        if($status == 'for-approval-division-head'){
                $divisionHeadId = Division::where('id', $division)->value('chief_employee_profile_id');
                if($divisionHeadId === $employee_id) {
                    $official_business_applications = ObApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','logs'])
                    ->whereHas('employeeProfile.assignedArea', function ($query) use ($division) {
                        $query->where('id', $division);
                    })
                    ->where('status', 'for-approval-division-head') 
                    ->get();

                    $official_business_applications_result = $official_business_applications->map(function ($official_business_application) {
                        $division = AssignArea::where('employee_profile_id',$official_business_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$official_business_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$official_business_application->employee_profile_id)->value('section_id');
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
                    $first_name = optional($official_business_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($official_business_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $official_business_application->id,
                        'date_from' => $official_business_application->date_from,
                        'date_to' => $official_business_application->date_to,
                        'time_from' => $official_business_application->time_from,
                        'time_to' => $official_business_application->time_to,
                        'reason' => $official_business_application->reason,
                        'status' => $official_business_application->status,
                        'employee_id' => $official_business_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'division_head' =>$chief_name,
                        'department_head' =>$head_name,
                        'section_head' =>$supervisor_name,
                        'division_name' => $official_business_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $official_business_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $official_business_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $official_business_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $official_business_application->logs->map(function ($log) {
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
                                'leave_application_id' => $log->ob_application_id,
                                'action_by' => "{$first_name} {$last_name}" ,
                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),
                      
                    ];
                    });
                

                    return response()->json(['official_business_applications' => $official_business_applications_result]);
                } 
        }
        else if($status == 'for-approval-department-head'){
            $department = AssignArea::where('employee_profile_id',$employee_id)->value('department_id');
            $departmentHeadId = Department::where('id', $department)->value('head_employee_profile_id');
            $training_officer_id = Department::where('id', $department)->value('training_officer_employee_profile_id');
            if($departmentHeadId === $employee_id || $training_officer_id === $employee_id) {
                $official_business_applications = ObApplication::with(['employeeProfile.assignedArea.department','employeeProfile.personalInformation','logs' ])
                ->whereHas('employeeProfile.assignedArea', function ($query) use ($department) {
                    $query->where('id', $department);
                })
                ->where('status', 'for-approval-department-head') 
                ->get();
    
                $official_business_applications_result = $official_business_applications->map(function ($official_business_application) {
                    $division = AssignArea::where('employee_profile_id',$official_business_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$official_business_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$official_business_application->employee_profile_id)->value('section_id');
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
                    $first_name = optional($official_business_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($official_business_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $official_business_application->id,
                        'date_from' => $official_business_application->date_from,
                        'date_to' => $official_business_application->date_to,
                        'time_from' => $official_business_application->time_from,
                        'time_to' => $official_business_application->time_to,
                        'reason' => $official_business_application->reason,
                        'status' => $official_business_application->status,
                        'employee_id' => $official_business_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'division_head' =>$chief_name,
                        'department_head' =>$head_name,
                        'section_head' =>$supervisor_name,
                        'division_name' => $official_business_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $official_business_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $official_business_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $official_business_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $official_business_application->logs->map(function ($log) {
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
                                'leave_application_id' => $log->ob_application_id,
                                'action_by' => "{$first_name} {$last_name}" ,
                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),
                      
                    ];
                    });
              
                return response()->json(['official_business_applications' => $official_business_applications_result]);
            }
        }
        else if($status == 'for-approval-section-head'){
            $section = AssignArea::where('employee_profile_id',$employee_id)->value('section_id');
            $sectionHeadId = Section::where('id', $section)->value('supervisor_employee_profile_id');
            if($sectionHeadId === $employee_id) {
      
                $official_business_applications = ObApplication::with(['employeeProfile.assignedArea.section','employeeProfile.personalInformation','logs'])
                ->whereHas('employeeProfile.assignedArea', function ($query) use ($section) {
                    $query->where('id', $section);
                })
                ->where('status', 'for-approval-section-head') 
                ->get();
    
                $official_business_applications_result = $official_business_applications->map(function ($official_business_application) {
                    $division = AssignArea::where('employee_profile_id',$official_business_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$official_business_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$official_business_application->employee_profile_id)->value('section_id');
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
                    $first_name = optional($official_business_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($official_business_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $official_business_application->id,
                        'date_from' => $official_business_application->date_from,
                        'date_to' => $official_business_application->date_to,
                        'time_from' => $official_business_application->time_from,
                        'time_to' => $official_business_application->time_to,
                        'reason' => $official_business_application->reason,
                        'status' => $official_business_application->status,
                        'employee_id' => $official_business_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'division_head' =>$chief_name,
                        'department_head' =>$head_name,
                        'section_head' =>$supervisor_name,
                        'division_name' => $official_business_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $official_business_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $official_business_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $official_business_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $official_business_application->logs->map(function ($log) {
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
                                'leave_application_id' => $log->ob_application_id,
                                'action_by' => "{$first_name} {$last_name}" ,
                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),
                      
                    ];
                });
            
            
    
                return response()->json(['official_business_applications' => $official_business_applications_result]);
            } 
        }
        else if($status == 'declined'){
            $official_business_applications = ObApplication::with(['employeeProfile.personalInformation','logs'])
                ->where('status', 'declined') 
                ->get();
    
                $official_business_applications_result = $official_business_applications->map(function ($official_business_application) {
                    $division = AssignArea::where('employee_profile_id',$official_business_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$official_business_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$official_business_application->employee_profile_id)->value('section_id');
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
                    $first_name = optional($official_business_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($official_business_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $official_business_application->id,
                        'date_from' => $official_business_application->date_from,
                        'date_to' => $official_business_application->date_to,
                        'time_from' => $official_business_application->time_from,
                        'time_to' => $official_business_application->time_to,
                        'reason' => $official_business_application->reason,
                        'status' => $official_business_application->status,
                        'logs' => $official_business_application->logs->map(function ($log) {
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
                      
                    ];
                });
            
    
                return response()->json(['official_business_applications' => $official_business_applications_result]);
        }
     
       

      
    }catch(\Throwable $th){
        
        return response()->json(['message' => $th->getMessage()], 500);
    }
    }

    public function create()
    {
        //
    }

    public function updateStatus (Request $request)
    {
        try {
                $user_id = Auth::user()->id;
                $employee_id = $request->employee_id; 
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

                            if($status == 'applied'){
                               
                                if($division === true)
                                {
                                    $new_status='for-approval-department-head';
                                    $message_action="verified";
                                    $action = 'Aprroved by Supervisor';
                                }
                                else
                                {
                                    $new_status='for-approval-section-head';
                                    $message_action="verified";
                                    $action = 'Approved by Supervisor';
                                }
                                
                            }
                            else if($status == 'for-approval-section-head' ){
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
                                $action = 'Aprroved by Department Head';
                                $new_status='approved';
                                $message_action="Approved";
                            }
                          
                            $ob_application_id = $request->ob_application_id;
                            $ob_applications = ObApplication::where('id','=', $ob_application_id)
                                                                    ->first();
                            if($ob_applications){
                            
                                $ob_application_log = new ObApplicationLog();
                                $ob_application_log->action = $action;
                                $ob_application_log->ob_application_id = $ob_application_id;
                                $ob_application_log->action_by = $user_id;
                                $ob_application_log->date = date('Y-m-d');
                                $ob_application_log->time =  date('H:i:s');
                                $ob_application_log->save();

                                $ob_application = ObApplication::findOrFail($ob_application_id);   
                                $ob_application->status = $new_status;
                                $ob_application->update();
                                    
                                return response(['message' => 'Application has been sucessfully '.$message_action, 'data' => $ob_application], Response::HTTP_CREATED); 
                                }
                }           
            }


         catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
      
    }

    public function updateObApplication(Request $request)
    {
        try{
            
            $ob_application_id= $request->ot_application_id;
            
            $official_business_application = ObApplication::findOrFail($ob_application_id); 
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->update();
         
            if ($request->hasFile('requirements')) {
                $requirements = $request->file('requirements');

                if($requirements){

                    $official_business_application_id = $official_business_application->id; 
                    foreach ($requirements as $requirement) {
                        $official_business_requirement = $this->storeOfficialbusinessApplicationRequirement($official_business_application_id);
                        $official_business_requirement_id = $official_business_requirement->id;

                        if($official_business_requirement){
                            $filename = config('enums.storage.leave') . '/' 
                                        . $official_business_requirement_id ;

                            $uploaded_image = $this->file_service->uploadRequirement($official_business_requirement_id->id, $requirement, $filename, "REQ");

                            if ($uploaded_image) {                     
                                $official_business_requirement_id = ObApplicationRequirement::where('id','=',$official_business_requirement->id)->first();  
                                if($official_business_requirement  ){
                                    $official_business_requirement_name = $requirement->getleaveOriginalName();
                                    $official_business_requirement =  ObApplicationRequirement::findOrFail($official_business_requirement->id);
                                    $official_business_requirement->name = $official_business_requirement_name;
                                    $official_business_requirement->filename = $uploaded_image;
                                    $official_business_requirement->update();
                                }                                      
                            }                           
                        }
                    }
                        
                }     
            }
            $process_name="Update";
            $official_business_logs = $this->storeOfficialBusinessApplicationLog($official_business_application_id,$process_name);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
      
    }

    public function store(Request $request)
    {
        try{
            
            $user_id = Auth::user()->id;
            $user = EmployeeProfile::where('id','=',$user_id)->first();
            $official_business_application = new ObApplication();
            $official_business_application->employee_profile_id = $user->id;
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->business_from = $request->business_from;
            $official_business_application->business_to = $request->business_to;
            $official_business_application->status = "for-approval-supervisor";
            $official_business_application->reason = "for-approval-supervisor";
            $official_business_application->date = date('Y-m-d');
            $official_business_application->time =  date('H:i:s');
            if ($request->hasFile('personal_order')) {
                $imagePath = $request->file('personal_order')->store('official_business', 'public');
                $official_business_application->personal_order = $imagePath;
            }
            if ($request->hasFile('certificate_of_appearance')) {
                $imagePath = $request->file('certificate_of_appearance')->store('official_business', 'public');
                $official_business_application->certificate_of_appearance = $imagePath;
            }
            $official_business_application->save();         
            $process_name="Applied";
            $official_business_log = $this->storeOfficialBusinessApplicationLog($official_business_application->id,$process_name);
            $this->storeOfficialBusinessApplicationLog($official_business_log);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
         
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function declineObApplication(Request $request)
    {
        try {
                    $ob_application_id = $request->ob_application_id;
                    $ob_applications = ObApplication::where('id','=', $ob_application_id)
                                                            ->first();
                if($ob_applications)
                {
                        $user_id = Auth::user()->id;     
                        $user = EmployeeProfile::where('id','=',$user_id)->first();
                        $user_password=$user->password;
                        $password=$request->password;
                        if($user_password==$password)
                        {
                            if($user_id){
                                $ob_application_log = new ObApplicationLog();
                                $ob_application_log->action = 'declined';
                                $ob_application_log->ob_application_id = $ob_application_id;
                                $ob_application_log->date = date('Y-m-d');
                                $ob_application_log->time =  date('H:i:s');
                                $ob_application_log->action_by = $user_id;
                                $ob_application_log->save();

                                $ob_application = ObApplication::findOrFail($ob_application_id);
                                $ob_application->declined_at = now();
                                $ob_application->status = 'declined';
                                $ob_application->update();
                                return response(['message' => 'Application has been sucessfully declined', 'data' => $ob_application], Response::HTTP_CREATED);  
            
                            }
                         }
                }
            } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),  'error'=>true]);
        }
    }
    
    public function storeOfficialBusinessApplicationRequirement($official_business_application_id)
    {
        try {
            $official_business_application_requirement = new ObApplicationRequirement();                       
            $official_business_application_requirement->official_business_application_id = $official_business_application_id;
            $official_business_application_requirement->save();

            return $official_business_application_requirement;
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
    }

    public function storeOfficialBusinessApplicationLog($official_time_application_id,$process_name)
    {
        try {
            $user_id="1";
            $official_time_application_log = new ObApplicationLog();                       
            $official_time_application_log->official_time_application_id = $official_time_application_id;
            $official_time_application_log->action_by = $user_id;
            $official_time_application_log->action = $process_name;
            $official_time_application_log->status = "applied";
            $official_time_application_log->date = date('Y-m-d');
            $official_time_application_log->time =  date('H:i:s');
            $official_time_application_log->save();

            return $official_time_application_log;
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
    }

    public function cancelObApplication(Request $request)
    {
        try {
                    $ob_application_id = $request->ob_application_id;
                    $ob_applications = ObApplication::where('id','=', $ob_application_id)
                                                            ->first();
                if($ob_applications)
                {
                        $user_id = Auth::user()->id;     
                        $user = EmployeeProfile::where('id','=',$user_id)->first();
                        $user_password=$user->password;
                        $password=$request->password;
                        if($user_password==$password)
                        {
                            if($user_id){
                                $ob_application_log = new ObApplicationLog();
                                $ob_application_log->action = 'cancelled';
                                $ob_application_log->ob_application_id = $ob_application_id;
                                $ob_application_log->date = date('Y-m-d');
                                $ob_application_log->time =  date('H:i:s');
                                $ob_application_log->action_by = $user_id;
                                $ob_application_log->save();

                                $ob_application = ObApplication::findOrFail($ob_application_id);
                                $ob_application->status = 'cancelled';
                                $ob_application->update();
                                return response(['message' => 'Application has been sucessfully cancelled', 'data' => $ob_application], Response::HTTP_CREATED);  
            
                            }
                         }
                }
            } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),  'error'=>true]);
        }
    }

    public function show(ObApplication $obApplication)
    {
        //
    }

    public function edit(ObApplication $obApplication)
    {
        //
    }

    public function update(Request $request, ObApplication $obApplication)
    {
        //
    }

    public function destroy(ObApplication $obApplication)
    {
        //
    }
}
