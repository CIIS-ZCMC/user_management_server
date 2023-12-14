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
use Illuminate\Support\Facades\Storage;
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
    public function getUserObApplication($id)
    {
        try{
        $ob_applications = ObApplication::with(['employeeProfile.personalInformation','logs'])
        ->where('employee_profile_id', $id)
        ->get();
            $ob_applications_result = $ob_applications->map(function ($ob_application) {
            $division = AssignArea::where('employee_profile_id',$ob_application->employee_profile_id)->value('division_id');
            $department = AssignArea::where('employee_profile_id',$ob_application->employee_profile_id)->value('department_id');
            $section = AssignArea::where('employee_profile_id',$ob_application->employee_profile_id)->value('section_id');
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
            $first_name = optional($ob_application->employeeProfile->personalInformation)->first_name ?? null;
            $last_name = optional($ob_application->employeeProfile->personalInformation)->last_name ?? null;
            return [
                'id' => $ob_application->id,
                'date_from' => $ob_application->date_from,
                'date_to' => $ob_application->date_to,
                'time_from' => $ob_application->time_from,
                'time_to' => $ob_application->time_to,
                'reason' => $ob_application->reason,
                'status' => $ob_application->status,
                'employee_id' => $ob_application->employee_profile_id,
                'employee_name' => "{$first_name} {$last_name}" ,
                'division_head' =>$chief_name,
                'department_head' =>$head_name,
                'section_head' =>$supervisor_name,
                'division_name' => $ob_application->employeeProfile->assignedArea->division->name ?? null,
                'department_name' => $ob_application->employeeProfile->assignedArea->department->name ?? null,
                'section_name' => $ob_application->employeeProfile->assignedArea->section->name ?? null,
                'unit_name' => $ob_application->employeeProfile->assignedArea->unit->name ?? null,
                'logs' => $ob_application->logs->map(function ($log) {
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




             return response()->json(['data' => $ob_applications_result], Response::HTTP_OK);
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    public function getObApplications($id,$status,Request $request)
    {

        try{

            $official_business_applications = [];

            if($status == 'for-approval-division-head'){
                $division = AssignArea::where('employee_profile_id',$id)->value('division_id');
                $divisionHeadId = Division::where('id', $division)->value('chief_employee_profile_id');
                    if($divisionHeadId == $id) {
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
                $department = AssignArea::where('employee_profile_id',$id)->value('department_id');
                $departmentHeadId = Department::where('id', $department)->value('head_employee_profile_id');
                $training_officer_id = Department::where('id', $department)->value('training_officer_employee_profile_id');
                if($departmentHeadId == $id || $training_officer_id == $id) {
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
                $section = AssignArea::where('employee_profile_id',$id)->value('section_id');
                $sectionHeadId = Section::where('id', $section)->value('supervisor_employee_profile_id');
                if($sectionHeadId == $id) {

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

    public function updateObApplicationStatus ($id,$status,Request $request)
    {
        try {
                // $user_id = Auth::user()->id;
                // $employee_id = $request->employee_id;
                // $user = EmployeeProfile::where('id','=',$user_id)->first();
                // $area = AssignArea::where('employee_profile_id',$employee_id)->value('division_id');
                // $division = Division::where('id',$area)->value('is_medical');
                // $user_password=$user->password;
                // $password=$request->password;
                // if($user_password==$password)
                // {
                    $division= true;
                            $message_action = '';
                            $action = '';
                            $new_status = '';


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
                                $action = 'Aprroved by Department Head';
                                $new_status='approved';
                                $message_action="Approved";
                            }
                            $ob_applications = ObApplication::where('id','=', $id)
                                                                    ->first();
                            if($ob_applications){

                                $ob_application_log = new ObApplicationLog();
                                $ob_application_log->action = $action;
                                $ob_application_log->ob_application_id = $id;
                                $ob_application_log->action_by_id = '1';
                                $ob_application_log->date = date('Y-m-d');
                                $ob_application_log->time =  date('H:i:s');
                                $ob_application_log->save();

                                $ob_application = ObApplication::findOrFail($id);
                                $ob_application->status = $new_status;
                                $ob_application->update();

                                return response(['message' => 'Application has been sucessfully '.$message_action, 'data' => $ob_application], Response::HTTP_CREATED);
                                }
                // }
            }


         catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }

    }

    public function update($id,Request $request)
    {
        try{



            $official_business_application = ObApplication::findOrFail($id);
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->time_from = $request->time_from;
            $official_business_application->time_to = $request->time_to;


            if ($request->hasFile('personal_order')) {
                if($official_business_application->personal_order)
                {
                    $filePath = $official_business_application->personal_order;
                    Storage::delete($filePath);
                }

                $imagePath = $request->file('personal_order')->store('official_business', 'public');
                $official_business_application->personal_order = $imagePath;
            }
            if ($request->hasFile('certificate_of_appearance')) {
                if($official_business_application->certificate_of_appearance)
                {
                    $filePath = $official_business_application->certificate_of_appearance;
                    Storage::delete($filePath);
                }

                $imagePath = $request->file('certificate_of_appearance')->store('official_business', 'public');
                $official_business_application->certificate_of_appearance = $imagePath;
            }
            $official_business_application->update();
            if ($official_business_application->isDirty()) {
                $changedColumns = $official_business_application->getChanges();

                $columnsString = implode(', ', $changedColumns);

            }
            $ob_id=$official_business_application->id;
            $columnsString="";
            $process_name="Applied";
            $this->storeOfficialBusinessApplicationLog($ob_id,$process_name,$columnsString);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }

    }

    public function store(Request $request)
    {
        try{

            // $user_id = Auth::user()->id;
            // $user = EmployeeProfile::where('id','=',$user_id)->first();
            // $area = AssignArea::where('employee_profile_id',$employee_id)->value('division_id');
            // $division = Division::where('id',$area)->value('is_medical');

            $division=true;
            $official_business_application = new ObApplication();
            $official_business_application->employee_profile_id = '1';
            $official_business_application->date_from = $request->date_from;
            $official_business_application->date_to = $request->date_to;
            $official_business_application->time_from = $request->time_from;
            $official_business_application->time_to = $request->time_to;
            if($division === true)
            {
                $status='for-approval-department-head';
            }
            else
            {
                $status='for-approval-section-head';
            }
            $official_business_application->status =$status;
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
            $ob_id=$official_business_application->id;
            $columnsString="";
            $process_name="Applied";

            $this->storeOfficialBusinessApplicationLog($ob_id,$process_name,$columnsString);
            $official_business_applications =ObApplication::with(['employeeProfile.personalInformation','logs'])
            ->where('id',$official_business_application->id)->get();
            $official_business_applications_result = $official_business_applications->map(function ($official_business_application) {
                    $division = AssignArea::where('employee_profile_id',$official_business_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$official_business_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$official_business_application->employee_profile_id)->value('section_id');
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
                        'position' => $official_business_application->employeeProfile->assignedArea->designation->name ?? null,
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
            return response()->json(['message' => 'Official Business Application has been sucessfully saved','data' => $official_business_applications_result ], Response::HTTP_OK);
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function declineObApplication($id,Request $request)
    {
        try {

                $ob_applications = ObApplication::where('id','=', $id)
                                                            ->first();
                if($ob_applications)
                {
                        // $user_id = Auth::user()->id;
                        // $user = EmployeeProfile::where('id','=',$user_id)->first();
                        // $user_password=$user->password;
                        // $password=$request->password;
                        // if($user_password==$password)
                        // {
                        //     if($user_id){
                                $ob_application_log = new ObApplicationLog();
                                $ob_application_log->action = 'declined';
                                $ob_application_log->ob_application_id = $id;
                                $ob_application_log->date = date('Y-m-d');
                                $ob_application_log->time =  date('H:i:s');
                                $ob_application_log->action_by_id = '1';
                                $ob_application_log->save();

                                $ob_application = ObApplication::findOrFail($id);
                                $ob_application->status = 'declined';
                                $ob_application->update();
                                return response(['message' => 'Application has been sucessfully declined', 'data' => $ob_application], Response::HTTP_CREATED);

                        //     }
                        //  }
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

    public function storeOfficialBusinessApplicationLog($ob_id,$process_name,$changedfields)
    {
        try {
            $user_id="1";

            $data = [
                'ob_application_id' => $ob_id,
                'action_by_id' => $user_id,
                'action' => $process_name,
                'date' => date('Y-m-d'),
                'time' => date('H:i:s'),
                'fields' => $changedfields
            ];

            $ob_business_log = ObApplicationLog::create($data);

            return $ob_business_log;
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
    }

    public function cancelObApplication($id,Request $request)
    {
        try {

                    $ob_applications = ObApplication::where('id','=', $id)
                                                            ->first();
                if($ob_applications)
                {
                        // $user_id = Auth::user()->id;
                        // $user = EmployeeProfile::where('id','=',$user_id)->first();
                        // $user_password=$user->password;
                        // $password=$request->password;
                        // if($user_password==$password)
                        // {
                        //     if($user_id){
                                $ob_application_log = new ObApplicationLog();
                                $ob_application_log->action = 'cancelled';
                                $ob_application_log->ob_application_id = $id;
                                $ob_application_log->date = date('Y-m-d');
                                $ob_application_log->time =  date('H:i:s');
                                $ob_application_log->action_by_id = '1';
                                $ob_application_log->save();

                                $ob_application = ObApplication::findOrFail($id);
                                $ob_application->status = 'cancelled';
                                $ob_application->update();
                                return response(['message' => 'Application has been sucessfully cancelled', 'data' => $ob_application], Response::HTTP_CREATED);

                        //     }
                        //  }
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



    public function destroy(ObApplication $obApplication)
    {
        //
    }
}
