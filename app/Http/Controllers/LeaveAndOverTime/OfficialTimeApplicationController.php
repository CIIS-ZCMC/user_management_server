<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use Illuminate\Http\Response;
use App\Models\OfficialTimeApplication;
use App\Http\Controllers\Controller;
use App\Http\Resources\OfficialTimeApplication as ResourcesOfficialTimeApplication;
use App\Http\Resources\OtApplicationLog;
use App\Models\AssignArea;
use App\Models\Department;
use App\Models\Division;
use App\Models\EmployeeProfile;
use Carbon\Carbon;
use App\Models\OfficialTimeRequirements;
use App\Models\OtApplicationLog as ModelsOtApplicationLog;
use App\Models\OtApplicationRequirement;
use App\Models\OvertimeApplication;
use App\Models\Section;
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
            $official_time_applications = OfficialTimeApplication::with(['employeeProfile.personalInformation','logs'])->get();
            $official_time_applications_result = $official_time_applications->map(function ($official_time_application) {
                    $division = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('section_id');
                    $chief_name=null;
                    $head_name=null;
                    $supervisor_name=null;
                    if($division) {
                        $division_name = Division::with('chief.personalInformation')->find($division);
                        if($division_name && $division_name->chief  && $division_name->personalInformation != null)
                        {
                            $chief_name = optional($division->chief->personalInformation)->first_name . '' . optional($division->chief->personalInformation)->last_name;
                        }


                    }
                    if($department)
                    {
                        $department_name = Department::with('head.personalInformation')->find($department);
                        if($department_name && $department_name->head  && $department_name->personalInformation != null)
                        {
                         $head_name = optional($department->head->personalInformation)->first_name ?? null . '' . optional($department->head->personalInformation)->last_name ?? null;
                        }
                    }
                    if($section)
                    {
                        $section_name = Section::with('supervisor.personalInformation')->find($section);
                        if($section_name && $section_name->head  && $section_name->personalInformation != null)
                        {
                        $supervisor_name = optional($section->head->personalInformation)->first_name ?? null . '' . optional($section->head->personalInformation)->last_name ?? null;
                        }
                    }
                $first_name = optional($official_time_application->employeeProfile->personalInformation)->first_name ?? null;
                $last_name = optional($official_time_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $official_time_application->id,
                        'date_from' => $official_time_application->date_from,
                        'date_to' => $official_time_application->date_to,
                        'time_from' => $official_time_application->time_from,
                        'time_to' => $official_time_application->time_to,
                        'reason' => $official_time_application->reason,
                        'status' => $official_time_application->status,
                        'employee_id' => $official_time_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'division_head' =>$chief_name,
                        'department_head' =>$head_name,
                        'section_head' =>$supervisor_name,
                        'division_name' => $official_time_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $official_time_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $official_time_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $official_time_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $official_time_application->logs->map(function ($log) {
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

                 return response()->json(['data' => $official_time_applications_result], Response::HTTP_OK);
            }catch(\Throwable $th){

                return response()->json(['message' => $th->getMessage()], 500);
            }

    }
    public function getOtApplications($id,$status,Request $request)
    {
        try{
            $status = $request->status;
            $employee_id = $request->employee_id;
            $OfficialTimeApplication = [];
            $division = AssignArea::where('employee_profile_id',$employee_id)->value('division_id');
            if($status == 'for-approval-division-head'){
                    $divisionHeadId = Division::where('id', $division)->value('chief_employee_profile_id');
                    if($divisionHeadId === $employee_id) {
                        $OfficialTimeApplication = OfficialTimeApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','logs'])
                        ->whereHas('employeeProfile.assignedArea', function ($query) use ($division) {
                            $query->where('id', $division);
                        })
                        ->where('status', 'for-approval-division-head')
                        ->get();

                        $official_time_applications_result = $OfficialTimeApplication->map(function ($official_time_application) {
                            $division = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('division_id');
                            $department = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('department_id');
                            $section = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('section_id');
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
                        $first_name = optional($official_time_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($official_time_application->employeeProfile->personalInformation)->last_name ?? null;
                        return [
                            'id' => $official_time_application->id,
                            'date_from' => $official_time_application->date_from,
                            'date_to' => $official_time_application->date_to,
                            'time_from' => $official_time_application->time_from,
                            'time_to' => $official_time_application->time_to,
                            'reason' => $official_time_application->reason,
                            'status' => $official_time_application->status,
                            'employee_id' => $official_time_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}" ,
                            'division_head' =>$chief_name,
                            'department_head' =>$head_name,
                            'section_head' =>$supervisor_name,
                            'division_name' => $official_time_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $official_time_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $official_time_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $official_time_application->employeeProfile->assignedArea->unit->name ?? null,
                            'logs' => $official_time_application->logs->map(function ($log) {
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


                        return response()->json(['OfficialTimeApplication' => $official_time_applications_result]);
                    }
            }
            else if($status == 'for-approval-department-head'){
                $department = AssignArea::where('employee_profile_id',$employee_id)->value('department_id');
                $departmentHeadId = Department::where('id', $department)->value('head_employee_profile_id');
                $training_officer_id = Department::where('id', $department)->value('training_officer_employee_profile_id');
                if($departmentHeadId === $employee_id || $training_officer_id === $employee_id) {
                    $OfficialTimeApplication = OfficialTimeApplication::with(['employeeProfile.assignedArea.department','employeeProfile.personalInformation','logs' ])
                    ->whereHas('employeeProfile.assignedArea', function ($query) use ($department) {
                        $query->where('id', $department);
                    })
                    ->where('status', 'for-approval-department-head')
                    ->get();

                    $official_time_applications_result = $OfficialTimeApplication->map(function ($official_time_application) {
                        $division = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('section_id');
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
                        $first_name = optional($official_time_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($official_time_application->employeeProfile->personalInformation)->last_name ?? null;
                        return [
                            'id' => $official_time_application->id,
                            'date_from' => $official_time_application->date_from,
                            'date_to' => $official_time_application->date_to,
                            'time_from' => $official_time_application->time_from,
                            'time_to' => $official_time_application->time_to,
                            'reason' => $official_time_application->reason,
                            'status' => $official_time_application->status,
                            'employee_id' => $official_time_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}" ,
                            'division_head' =>$chief_name,
                            'department_head' =>$head_name,
                            'section_head' =>$supervisor_name,
                            'division_name' => $official_time_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $official_time_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $official_time_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $official_time_application->employeeProfile->assignedArea->unit->name ?? null,
                            'logs' => $official_time_application->logs->map(function ($log) {
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

                    return response()->json(['OfficialTimeApplication' => $OfficialTimeApplication_result]);
                }
            }
            else if($status == 'for-approval-section-head'){
                $section = AssignArea::where('employee_profile_id',$employee_id)->value('section_id');
                $sectionHeadId = Section::where('id', $section)->value('supervisor_employee_profile_id');
                if($sectionHeadId === $employee_id) {

                    $official_time_applications = OfficialTimeApplication::with(['employeeProfile.assignedArea.section','employeeProfile.personalInformation','logs'])
                    ->whereHas('employeeProfile.assignedArea', function ($query) use ($section) {
                        $query->where('id', $section);
                    })
                    ->where('status', 'for-approval-section-head')
                    ->get();

                    $official_time_applications_result = $official_time_applications->map(function ($official_time_application) {
                        $division = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('section_id');
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
                        $first_name = optional($official_time_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($official_time_application->employeeProfile->personalInformation)->last_name ?? null;
                        return [
                            'id' => $official_time_application->id,
                            'date_from' => $official_time_application->date_from,
                            'date_to' => $official_time_application->date_to,
                            'time_from' => $official_time_application->time_from,
                            'time_to' => $official_time_application->time_to,
                            'reason' => $official_time_application->reason,
                            'status' => $official_time_application->status,
                            'employee_id' => $official_time_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}" ,
                            'division_head' =>$chief_name,
                            'department_head' =>$head_name,
                            'section_head' =>$supervisor_name,
                            'division_name' => $official_time_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $official_time_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $official_time_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $official_time_application->employeeProfile->assignedArea->unit->name ?? null,
                            'logs' => $official_time_application->logs->map(function ($log) {
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



                    return response()->json(['official_time_applications' => $official_time_applications_result]);
                }
            }
            else if($status == 'declined'){
                $official_time_applications = OfficialTimeApplication::with(['employeeProfile.personalInformation','logs'])
                    ->where('status', 'declined')
                    ->get();

                    $official_time_applications_result = $official_time_applications->map(function ($official_time_application) {
                        $division = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('section_id');
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
                        $first_name = optional($official_time_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($official_time_application->employeeProfile->personalInformation)->last_name ?? null;
                        return [
                            'id' => $official_time_application->id,
                            'date_from' => $official_time_application->date_from,
                            'date_to' => $official_time_application->date_to,
                            'time_from' => $official_time_application->time_from,
                            'time_to' => $official_time_application->time_to,
                            'reason' => $official_time_application->reason,
                            'status' => $official_time_application->status,
                            'logs' => $official_time_application->logs->map(function ($log) {
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


                    return response()->json(['official_business_applications' => $official_time_applications_result]);
            }




        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try{
            // $user_id = Auth::user()->id;
            // $user = EmployeeProfile::where('id','=',$user_id)->first();
            $official_time_application = new OfficialTimeApplication();
            $official_time_application->employee_profile_id = '1';
            $official_time_application->date_from = $request->date_from;
            $official_time_application->date_to = $request->date_to;
            $official_time_application->time_from = $request->time_from;
            $official_time_application->time_to = $request->time_to;
            $official_time_application->status = "for-approval-supervisor";
            $official_time_application->reason = "for-approval-supervisor";
            $official_time_application->date = date('Y-m-d');
            $official_time_application->time =  date('H:i:s');
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
                                $ot_application_log->time =  date('H:i:s');
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
                                $ot_application_log->time =  date('H:i:s');
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
                                $ot_application_log->time =  date('H:i:s');
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
            $user_id = Auth::user()->id;
            $user = EmployeeProfile::where('id','=',$user_id)->first();
            $official_time_application_log = new ModelsOtApplicationLog();
            $official_time_application_log->official_time_application_id = $official_time_application_id;
            $official_time_application_log->action_by = $user_id;
            $official_time_application_log->process_name = $process_name;
            $official_time_application_log->status = "applied";
            $official_time_application_log->date = date('Y-m-d');
            $official_time_application_log->time = date('H:i:s');
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
