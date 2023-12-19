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
use Illuminate\Support\Facades\Storage;
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
                    $chief_position=null;
                    $head_name=null;
                    $head_position=null;
                    $supervisor_name=null;
                    $supervisor_position=null;
                    if($division) {
                        $division_name = Division::with('chief.personalInformation')->find($division);
                        if($division_name && $division_name->chief  && $division_name->personalInformation != null)
                        {
                            $chief_name = optional($division->chief->personalInformation)->first_name . '' . optional($division->chief->personalInformation)->last_name;
                            $chief_position = $division->chief->assignedArea->designation->name ?? null;
                        }


                    }
                    if($department)
                    {
                        $department_name = Department::with('head.personalInformation')->find($department);
                        if($department_name && $department_name->head  && $department_name->personalInformation != null)
                        {
                         $head_name = optional($department->head->personalInformation)->first_name ?? null . '' . optional($department->head->personalInformation)->last_name ?? null;
                         $head_position = $department->head->assignedArea->designation->name ?? null;
                        }
                    }
                    if($section)
                    {
                        $section_name = Section::with('supervisor.personalInformation')->find($section);
                        if($section_name && $section_name->supervisor  && $section_name->personalInformation != null)
                        {
                        $supervisor_name = optional($section->supervisor->personalInformation)->first_name ?? null . '' . optional($section->head->personalInformation)->last_name ?? null;
                        $supervisor_position = $section->supervisor->assignedArea->designation->name ?? null;
                        }
                    }
                $first_name = optional($official_time_application->employeeProfile->personalInformation)->first_name ?? null;
                $last_name = optional($official_time_application->employeeProfile->personalInformation)->last_name ?? null;
                $startDate = Carbon::createFromFormat('Y-m-d', $official_time_application->date_from);
                $endDate = Carbon::createFromFormat('Y-m-d', $official_time_application->date_to);

                $numberOfDays = $startDate->diffInDays($endDate) + 1;

                    return [
                        'id' => $official_time_application->id,
                        'date_from' => $official_time_application->date_from,
                        'date_to' => $official_time_application->date_to,
                        'time_from' => $official_time_application->time_from,
                        'time_to' => $official_time_application->time_to,
                        'total_days' => $numberOfDays,
                        'reason' => $official_time_application->reason,
                        'status' => $official_time_application->status,
                        'personal_order' => $official_time_application->personal_order,
                        'personal_order_path' => $official_time_application->personal_order_path,
                        'personal_order_size' => $official_time_application->personal_order_size,
                        'certificate_of_appearance' => $official_time_application->certificate_of_appearance,
                        'certificate_of_appearance_path' => $official_time_application->certificate_of_appearance_path,
                        'certificate_of_appearance_size' => $official_time_application->certificate_of_appearance_size,
                        'employee_id' => $official_time_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'division_head' =>$chief_name,
                        'division_head_position'=> $chief_position,
                        'department_head' =>$head_name,
                        'department_head_position' =>$head_position,
                        'section_head' =>$supervisor_name,
                        'section_head_position' =>$supervisor_position,
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

            $OfficialTimeApplication = [];
            $division = AssignArea::where('employee_profile_id',$id)->value('division_id');
            $divisionHeadId = Division::where('id', $division)->value('chief_employee_profile_id');
            if($status == 'for-approval-division-head'){
                    $divisionHeadId = Division::where('id', $division)->value('chief_employee_profile_id');
                    if($divisionHeadId == $id) {
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
                            $chief_position=null;
                            $head_name=null;
                            $head_position=null;
                            $supervisor_name=null;
                            $supervisor_position=null;
                            if($division) {
                                $division_name = Division::with('chief.personalInformation')->find($division);
                                if($division_name && $division_name->chief  && $division_name->personalInformation != null)
                                {
                                    $chief_name = optional($division->chief->personalInformation)->first_name . '' . optional($division->chief->personalInformation)->last_name;
                                    $chief_position = $division->chief->assignedArea->designation->name ?? null;
                                }


                            }
                            if($department)
                            {
                                $department_name = Department::with('head.personalInformation')->find($department);
                                if($department_name && $department_name->head  && $department_name->personalInformation != null)
                                {
                                 $head_name = optional($department->head->personalInformation)->first_name ?? null . '' . optional($department->head->personalInformation)->last_name ?? null;
                                 $head_position = $department->head->assignedArea->designation->name ?? null;
                                }
                            }
                            if($section)
                            {
                                $section_name = Section::with('supervisor.personalInformation')->find($section);
                                if($section_name && $section_name->supervisor  && $section_name->personalInformation != null)
                                {
                                $supervisor_name = optional($section->supervisor->personalInformation)->first_name ?? null . '' . optional($section->head->personalInformation)->last_name ?? null;
                                $supervisor_position = $section->supervisor->assignedArea->designation->name ?? null;
                                }
                            }
                        $first_name = optional($official_time_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($official_time_application->employeeProfile->personalInformation)->last_name ?? null;
                        $startDate = Carbon::createFromFormat('Y-m-d', $official_time_application->date_from);
                        $endDate = Carbon::createFromFormat('Y-m-d', $official_time_application->date_to);

                        $numberOfDays = $startDate->diffInDays($endDate) + 1;

                        return [
                            'id' => $official_time_application->id,
                            'date_from' => $official_time_application->date_from,
                            'date_to' => $official_time_application->date_to,
                            'time_from' => $official_time_application->time_from,
                            'time_to' => $official_time_application->time_to,
                            'total_days' => $numberOfDays,
                            'reason' => $official_time_application->reason,
                            'status' => $official_time_application->status,
                            'personal_order' => $official_time_application->personal_order,
                            'personal_order_path' => $official_time_application->personal_order_path,
                            'personal_order_size' => $official_time_application->personal_order_size,
                            'certificate_of_appearance' => $official_time_application->certificate_of_appearance,
                            'certificate_of_appearance_path' => $official_time_application->certificate_of_appearance_path,
                            'certificate_of_appearance_size' => $official_time_application->certificate_of_appearance_size,
                            'employee_id' => $official_time_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}" ,
                            'division_head' =>$chief_name,
                            'division_head_position'=> $chief_position,
                            'department_head' =>$head_name,
                            'department_head_position' =>$head_position,
                            'section_head' =>$supervisor_name,
                            'section_head_position' =>$supervisor_position,
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
                $department = AssignArea::where('employee_profile_id',$id)->value('department_id');
                $departmentHeadId = Department::where('id', $department)->value('head_employee_profile_id');
                $training_officer_id = Department::where('id', $department)->value('training_officer_employee_profile_id');
                if($departmentHeadId == $id || $training_officer_id == $id) {
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
                        $chief_position=null;
                        $head_name=null;
                        $head_position=null;
                        $supervisor_name=null;
                        $supervisor_position=null;
                        if($division) {
                            $division_name = Division::with('chief.personalInformation')->find($division);
                            if($division_name && $division_name->chief  && $division_name->personalInformation != null)
                            {
                                $chief_name = optional($division->chief->personalInformation)->first_name . '' . optional($division->chief->personalInformation)->last_name;
                                $chief_position = $division->chief->assignedArea->designation->name ?? null;
                            }


                        }
                        if($department)
                        {
                            $department_name = Department::with('head.personalInformation')->find($department);
                            if($department_name && $department_name->head  && $department_name->personalInformation != null)
                            {
                             $head_name = optional($department->head->personalInformation)->first_name ?? null . '' . optional($department->head->personalInformation)->last_name ?? null;
                             $head_position = $department->head->assignedArea->designation->name ?? null;
                            }
                        }
                        if($section)
                        {
                            $section_name = Section::with('supervisor.personalInformation')->find($section);
                            if($section_name && $section_name->supervisor  && $section_name->personalInformation != null)
                            {
                            $supervisor_name = optional($section->supervisor->personalInformation)->first_name ?? null . '' . optional($section->head->personalInformation)->last_name ?? null;
                            $supervisor_position = $section->supervisor->assignedArea->designation->name ?? null;
                            }
                        }
                        $first_name = optional($official_time_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($official_time_application->employeeProfile->personalInformation)->last_name ?? null;
                        $startDate = Carbon::createFromFormat('Y-m-d', $official_time_application->date_from);
                        $endDate = Carbon::createFromFormat('Y-m-d', $official_time_application->date_to);

                        $numberOfDays = $startDate->diffInDays($endDate) + 1;

                        return [
                            'id' => $official_time_application->id,
                            'date_from' => $official_time_application->date_from,
                            'date_to' => $official_time_application->date_to,
                            'time_from' => $official_time_application->time_from,
                            'time_to' => $official_time_application->time_to,
                            'total_days' => $numberOfDays,
                            'reason' => $official_time_application->reason,
                            'status' => $official_time_application->status,
                            'personal_order' => $official_time_application->personal_order,
                            'personal_order_path' => $official_time_application->personal_order_path,
                            'personal_order_size' => $official_time_application->personal_order_size,
                            'certificate_of_appearance' => $official_time_application->certificate_of_appearance,
                            'certificate_of_appearance_path' => $official_time_application->certificate_of_appearance_path,
                            'certificate_of_appearance_size' => $official_time_application->certificate_of_appearance_size,
                            'employee_id' => $official_time_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}" ,
                            'division_head' =>$chief_name,
                            'division_head_position'=> $chief_position,
                            'department_head' =>$head_name,
                            'department_head_position' =>$head_position,
                            'section_head' =>$supervisor_name,
                            'section_head_position' =>$supervisor_position,
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
            else if($status == 'for-approval-section-head'){
                $section = AssignArea::where('employee_profile_id',$id)->value('section_id');
                $sectionHeadId = Section::where('id', $section)->value('supervisor_employee_profile_id');
                if($sectionHeadId == $id) {

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
                        $chief_position=null;
                        $head_name=null;
                        $head_position=null;
                        $supervisor_name=null;
                        $supervisor_position=null;
                        if($division) {
                            $division_name = Division::with('chief.personalInformation')->find($division);
                            if($division_name && $division_name->chief  && $division_name->personalInformation != null)
                            {
                                $chief_name = optional($division->chief->personalInformation)->first_name . '' . optional($division->chief->personalInformation)->last_name;
                                $chief_position = $division->chief->assignedArea->designation->name ?? null;
                            }


                        }
                        if($department)
                        {
                            $department_name = Department::with('head.personalInformation')->find($department);
                            if($department_name && $department_name->head  && $department_name->personalInformation != null)
                            {
                            $head_name = optional($department->head->personalInformation)->first_name ?? null . '' . optional($department->head->personalInformation)->last_name ?? null;
                            $head_position = $department->head->assignedArea->designation->name ?? null;
                            }
                        }
                        if($section)
                        {
                            $section_name = Section::with('supervisor.personalInformation')->find($section);
                            if($section_name && $section_name->supervisor  && $section_name->personalInformation != null)
                            {
                            $supervisor_name = optional($section->supervisor->personalInformation)->first_name ?? null . '' . optional($section->head->personalInformation)->last_name ?? null;
                            $supervisor_position = $section->supervisor->assignedArea->designation->name ?? null;
                            }
                        }
                        $first_name = optional($official_time_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($official_time_application->employeeProfile->personalInformation)->last_name ?? null;
                        $startDate = Carbon::createFromFormat('Y-m-d', $official_time_application->date_from);
                        $endDate = Carbon::createFromFormat('Y-m-d', $official_time_application->date_to);

                        $numberOfDays = $startDate->diffInDays($endDate) + 1;

                        return [
                            'id' => $official_time_application->id,
                            'date_from' => $official_time_application->date_from,
                            'date_to' => $official_time_application->date_to,
                            'time_from' => $official_time_application->time_from,
                            'time_to' => $official_time_application->time_to,
                            'total_days' => $numberOfDays,
                            'reason' => $official_time_application->reason,
                            'status' => $official_time_application->status,
                            'personal_order' => $official_time_application->personal_order,
                            'personal_order_path' => $official_time_application->personal_order_path,
                            'personal_order_size' => $official_time_application->personal_order_size,
                            'certificate_of_appearance' => $official_time_application->certificate_of_appearance,
                            'certificate_of_appearance_path' => $official_time_application->certificate_of_appearance_path,
                            'certificate_of_appearance_size' => $official_time_application->certificate_of_appearance_size,
                            'employee_id' => $official_time_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}" ,
                            'division_head' =>$chief_name,
                            'division_head_position'=> $chief_position,
                            'department_head' =>$head_name,
                            'department_head_position' =>$head_position,
                            'section_head' =>$supervisor_name,
                            'section_head_position' =>$supervisor_position,
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
                        $chief_position=null;
                        $head_name=null;
                        $head_position=null;
                        $supervisor_name=null;
                        $supervisor_position=null;
                        if($division) {
                            $division_name = Division::with('chief.personalInformation')->find($division);
                            if($division_name && $division_name->chief  && $division_name->personalInformation != null)
                            {
                                $chief_name = optional($division->chief->personalInformation)->first_name . '' . optional($division->chief->personalInformation)->last_name;
                                $chief_position = $division->chief->assignedArea->designation->name ?? null;
                            }


                        }
                        if($department)
                        {
                            $department_name = Department::with('head.personalInformation')->find($department);
                            if($department_name && $department_name->head  && $department_name->personalInformation != null)
                            {
                             $head_name = optional($department->head->personalInformation)->first_name ?? null . '' . optional($department->head->personalInformation)->last_name ?? null;
                             $head_position = $department->head->assignedArea->designation->name ?? null;
                            }
                        }
                        if($section)
                        {
                            $section_name = Section::with('supervisor.personalInformation')->find($section);
                            if($section_name && $section_name->supervisor  && $section_name->personalInformation != null)
                            {
                            $supervisor_name = optional($section->supervisor->personalInformation)->first_name ?? null . '' . optional($section->head->personalInformation)->last_name ?? null;
                            $supervisor_position = $section->supervisor->assignedArea->designation->name ?? null;
                            }
                        }
                        $startDate = Carbon::createFromFormat('Y-m-d', $official_time_application->date_from);
                        $endDate = Carbon::createFromFormat('Y-m-d', $official_time_application->date_to);

                        $numberOfDays = $startDate->diffInDays($endDate) + 1;
                        $first_name = optional($official_time_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($official_time_application->employeeProfile->personalInformation)->last_name ?? null;
                        return [
                            'id' => $official_time_application->id,
                            'date_from' => $official_time_application->date_from,
                            'date_to' => $official_time_application->date_to,
                            'time_from' => $official_time_application->time_from,
                            'time_to' => $official_time_application->time_to,
                            'total_days' => $numberOfDays,
                            'reason' => $official_time_application->reason,
                            'status' => $official_time_application->status,
                            'personal_order' => $official_time_application->personal_order,
                            'personal_order_path' => $official_time_application->personal_order_path,
                            'personal_order_size' => $official_time_application->personal_order_size,
                            'certificate_of_appearance' => $official_time_application->certificate_of_appearance,
                            'certificate_of_appearance_path' => $official_time_application->certificate_of_appearance_path,
                            'certificate_of_appearance_size' => $official_time_application->certificate_of_appearance_size,
                            'employee_id' => $official_time_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}" ,
                            'division_head' =>$chief_name,
                            'division_head_position'=> $chief_position,
                            'department_head' =>$head_name,
                            'department_head_position' =>$head_position,
                            'section_head' =>$supervisor_name,
                            'section_head_position' =>$supervisor_position,
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


                    return response()->json(['official_business_applications' => $official_time_applications_result]);
            }




        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    public function getUserOtApplication($id)
    {
        try{
        $ot_applications = OfficialTimeApplication::with(['employeeProfile.personalInformation','logs'])
        ->where('employee_profile_id', $id)
        ->get();
            $ot_applications_result = $ot_applications->map(function ($ot_application) {
            $division = AssignArea::where('employee_profile_id',$ot_application->employee_profile_id)->value('division_id');
            $department = AssignArea::where('employee_profile_id',$ot_application->employee_profile_id)->value('department_id');
            $section = AssignArea::where('employee_profile_id',$ot_application->employee_profile_id)->value('section_id');
            $chief_name=null;
            $chief_position=null;
            $head_name=null;
            $head_position=null;
            $supervisor_name=null;
            $supervisor_position=null;
            if($division) {
                $division_name = Division::with('chief.personalInformation')->find($division);
                if($division_name && $division_name->chief  && $division_name->personalInformation != null)
                {
                    $chief_name = optional($division->chief->personalInformation)->first_name . '' . optional($division->chief->personalInformation)->last_name;
                    $chief_position = $division->chief->assignedArea->designation->name ?? null;
                }


            }
            if($department)
            {
                $department_name = Department::with('head.personalInformation')->find($department);
                if($department_name && $department_name->head  && $department_name->personalInformation != null)
                {
                 $head_name = optional($department->head->personalInformation)->first_name ?? null . '' . optional($department->head->personalInformation)->last_name ?? null;
                 $head_position = $department->head->assignedArea->designation->name ?? null;
                }
            }
            if($section)
            {
                $section_name = Section::with('supervisor.personalInformation')->find($section);
                if($section_name && $section_name->supervisor  && $section_name->personalInformation != null)
                {
                $supervisor_name = optional($section->supervisor->personalInformation)->first_name ?? null . '' . optional($section->head->personalInformation)->last_name ?? null;
                $supervisor_position = $section->supervisor->assignedArea->designation->name ?? null;
                }
            }
            $first_name = optional($ot_application->employeeProfile->personalInformation)->first_name ?? null;
            $last_name = optional($ot_application->employeeProfile->personalInformation)->last_name ?? null;
            $startDate = Carbon::createFromFormat('Y-m-d', $ot_application->date_from);
            $endDate = Carbon::createFromFormat('Y-m-d', $ot_application->date_to);

            $numberOfDays = $startDate->diffInDays($endDate) + 1;

            return [
                'id' => $ot_application->id,
                'date_from' => $ot_application->date_from,
                'date_to' => $ot_application->date_to,
                'time_from' => $ot_application->time_from,
                'time_to' => $ot_application->time_to,
                'total_days' => $numberOfDays,
                'reason' => $ot_application->reason,
                'status' => $ot_application->status,
                'personal_order' => $ot_application->personal_order,
                'personal_order_path' => $ot_application->personal_order_path,
                'personal_order_size' => $ot_application->personal_order_size,
                'certificate_of_appearance' => $ot_application->certificate_of_appearance,
                'certificate_of_appearance_path' => $ot_application->certificate_of_appearance_path,
                'certificate_of_appearance_size' => $ot_application->certificate_of_appearance_size,
                'employee_id' => $ot_application->employee_profile_id,
                'employee_name' => "{$first_name} {$last_name}" ,
                'division_head' =>$chief_name,
                'division_head_position'=> $chief_position,
                'department_head' =>$head_name,
                'department_head_position' =>$head_position,
                'section_head' =>$supervisor_name,
                'section_head_position' =>$supervisor_position,
                'division_name' => $ot_application->employeeProfile->assignedArea->division->name ?? null,
                'department_name' => $ot_application->employeeProfile->assignedArea->department->name ?? null,
                'section_name' => $ot_application->employeeProfile->assignedArea->section->name ?? null,
                'unit_name' => $ot_application->employeeProfile->assignedArea->unit->name ?? null,
                'logs' => $ot_application->logs->map(function ($log) {
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

             return response()->json(['data' => $ot_applications_result], Response::HTTP_OK);
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
            $official_time_application = new OfficialTimeApplication();
            $official_time_application->employee_profile_id = '1';
            $official_time_application->date_from = $request->date_from;
            $official_time_application->date_to = $request->date_to;
            $official_time_application->time_from = $request->time_from;
            $official_time_application->time_to = $request->time_to;
            if($division === true)
            {
                $status='for-approval-department-head';
            }
            else
            {
                $status='for-approval-section-head';
            }
            $official_time_application->status = $status;
            $official_time_application->reason =$request->reason;
            $official_time_application->date = date('Y-m-d');
            $official_time_application->time =  date('H:i:s');
            if ($request->hasFile('personal_order')) {
                $folderName = 'official_time';
                $fileName=pathinfo($request->file('personal_order')->getClientOriginalName(), PATHINFO_FILENAME);
                $extension  = $request->file('personal_order')->getClientOriginalName();
                $uniqueFileName = $fileName . '_' . time() . '.' . $extension;
                Storage::makeDirectory('public/' . $folderName);
                $request->file('personal_order')->storeAs('public/' . $folderName, $uniqueFileName);
                $path = $folderName .'/'. $uniqueFileName;
                $size = $request->file('personal_order')->getSize();
                $official_time_application->personal_order = $uniqueFileName;
                $official_time_application->personal_order_path = $path;
                $official_time_application->personal_order_size = $size;
            }
            if ($request->hasFile('certificate_of_appearance')) {
                $folderName = 'official_time';
                $fileName=pathinfo($request->file('certificate_of_appearance')->getClientOriginalName(), PATHINFO_FILENAME);
                $extension  = $request->file('certificate_of_appearance')->getClientOriginalName();
                $uniqueFileName = $fileName . '_' . time() . '.' . $extension;
                Storage::makeDirectory('public/' . $folderName);
                $request->file('certificate_of_appearance')->storeAs('public/' . $folderName, $uniqueFileName);
                $path = $folderName .'/'. $uniqueFileName;
                $size = $request->file('certificate_of_appearance')->getSize();
                $official_time_application->certificate_of_appearance = $uniqueFileName;
                $official_time_application->certificate_of_appearance_path = $path;
                $official_time_application->certificate_of_appearance_size = $size;
            }
            $official_time_application->save();
            $ot_id=$official_time_application->id;
            $columnsString="";
            $process_name="Applied";


            $this->storeOfficialTimeApplicationLog($ot_id,$process_name,$columnsString);
            $official_time_applications = OfficialTimeApplication::with(['employeeProfile.personalInformation','logs'])
            ->where('id',$official_time_application->id)->get();
            $official_time_applications_result = $official_time_applications->map(function ($official_time_application) {
                    $division = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('section_id');
                    $chief_name=null;
                    $chief_position=null;
                    $head_name=null;
                    $head_position=null;
                    $supervisor_name=null;
                    $supervisor_position=null;
                    if($division) {
                        $division_name = Division::with('chief.personalInformation')->find($division);
                        if($division_name && $division_name->chief  && $division_name->personalInformation != null)
                        {
                            $chief_name = optional($division->chief->personalInformation)->first_name . '' . optional($division->chief->personalInformation)->last_name;
                            $chief_position = $division->chief->assignedArea->designation->name ?? null;
                        }


                    }
                    if($department)
                    {
                        $department_name = Department::with('head.personalInformation')->find($department);
                        if($department_name && $department_name->head  && $department_name->personalInformation != null)
                        {
                         $head_name = optional($department->head->personalInformation)->first_name ?? null . '' . optional($department->head->personalInformation)->last_name ?? null;
                         $head_position = $department->head->assignedArea->designation->name ?? null;
                        }
                    }
                    if($section)
                    {
                        $section_name = Section::with('supervisor.personalInformation')->find($section);
                        if($section_name && $section_name->supervisor  && $section_name->personalInformation != null)
                        {
                        $supervisor_name = optional($section->supervisor->personalInformation)->first_name ?? null . '' . optional($section->head->personalInformation)->last_name ?? null;
                        $supervisor_position = $section->supervisor->assignedArea->designation->name ?? null;
                        }
                    }
                $first_name = optional($official_time_application->employeeProfile->personalInformation)->first_name ?? null;
                $last_name = optional($official_time_application->employeeProfile->personalInformation)->last_name ?? null;
                $startDate = Carbon::createFromFormat('Y-m-d', $official_time_application->date_from);
                $endDate = Carbon::createFromFormat('Y-m-d', $official_time_application->date_to);

                $numberOfDays = $startDate->diffInDays($endDate) + 1;

                    return [
                        'id' => $official_time_application->id,
                        'date_from' => $official_time_application->date_from,
                        'date_to' => $official_time_application->date_to,
                        'time_from' => $official_time_application->time_from,
                        'time_to' => $official_time_application->time_to,
                        'total_days' => $numberOfDays,
                        'reason' => $official_time_application->reason,
                        'status' => $official_time_application->status,
                        'personal_order' => $official_time_application->personal_order,
                        'personal_order_path' => $official_time_application->personal_order_path,
                        'personal_order_size' => $official_time_application->personal_order_size,
                        'certificate_of_appearance' => $official_time_application->certificate_of_appearance,
                        'certificate_of_appearance_path' => $official_time_application->certificate_of_appearance_path,
                        'employee_id' => $official_time_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'division_head' =>$chief_name,
                        'division_head_position'=> $chief_position,
                        'department_head' =>$head_name,
                        'department_head_position' =>$head_position,
                        'section_head' =>$supervisor_name,
                        'section_head_position' =>$supervisor_position,
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
                $singleArray = array_merge(...$official_time_applications_result);
            return response()->json(['message' => 'Official Business Application has been sucessfully saved','data' => $singleArray ], Response::HTTP_OK);
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function declineOtApplication($id,Request $request)
    {
        try {

                $ot_applications = OfficialTimeApplication::where('id','=', $id)
                                                            ->first();
                if($ot_applications)
                {
                        // $user_id = Auth::user()->id;
                        // $user = EmployeeProfile::where('id','=',$user_id)->first();
                        // $user_password=$user->password;
                        // $password=$request->password;
                        // if($user_password==$password)
                        // {
                        //     if($user_id){
                                $ot_application_log = new ModelsOtApplicationLog();
                                $ot_application_log->action = 'declined';
                                $ot_application_log->official_time_application_id = $id;
                                $ot_application_log->date = date('Y-m-d');
                                $ot_application_log->time =  date('H:i:s');
                                $ot_application_log->action_by_id = '1';
                                $ot_application_log->save();

                                $ot_application = OfficialTimeApplication::findOrFail($id);
                                $ot_application->status = 'declined';
                                $ot_application->update();
                                return response(['message' => 'Application has been sucessfully declined', 'data' => $ot_application], Response::HTTP_CREATED);

                        //     }
                        //  }
                }
            } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),  'error'=>true]);
        }
    }

    public function cancelOtApplication($id,Request $request)
    {
        try {

                    $ot_applications = OfficialTimeApplication::where('id','=', $id)
                                                            ->first();
                if($ot_applications)
                {
                //         $user_id = Auth::user()->id;
                //         $user = EmployeeProfile::where('id','=',$user_id)->first();
                //         $user_password=$user->password;
                //         $password=$request->password;
                //         if($user_password==$password)
                //         {
                //             if($user_id){
                                $ot_application_log = new ModelsOtApplicationLog();
                                $ot_application_log->action = 'cancelled';
                                $ot_application_log->official_time_application_id = $id;
                                $ot_application_log->date = date('Y-m-d');
                                $ot_application_log->time =  date('H:i:s');
                                $ot_application_log->action_by_id = '1';
                                $ot_application_log->save();

                                $ot_application = OfficialTimeApplication::findOrFail($id);
                                $ot_application->status = 'cancelled';
                                $ot_application->update();
                                return response(['message' => 'Application has been sucessfully cancelled', 'data' => $ot_application], Response::HTTP_CREATED);

                        //     }
                        //  }
                }
            } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),  'error'=>true]);
        }
    }

    public function updateStatus ($id,$status,Request $request)
    {
        try {
                // $user_id = Auth::user()->id;
                // $user = EmployeeProfile::where('id','=',$user_id)->first();
                // $user_password=$user->password;
                // $password=$request->password;
                // $area = AssignArea::where('employee_profile_id',$employee_id)->value('division_id');
                // $division = Division::where('id',$area)->value('is_medical');
                // if($user_password==$password)
                // {
                            $message_action = '';
                            $action = '';
                            $new_status = '';
                            $division= true;

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

                            $ot_applications = OfficialTimeApplication::where('id','=', $id)
                                                                    ->first();
                            if($ot_applications){

                                $ot_application_log = new ModelsOtApplicationLog();
                                $ot_application_log->action = $action;
                                $ot_application_log->official_time_application_id = $id;
                                $ot_application_log->action_by_id = '1';
                                $ot_application_log->date = date('Y-m-d');
                                $ot_application_log->time =  date('H:i:s');
                                $ot_application_log->save();

                                $ot_application = OfficialTimeApplication::findOrFail($id);
                                $ot_application->status = $new_status;
                                $ot_application->update();

                                return response(['message' => 'Application has been sucessfully '.$message_action, 'data' => $ot_application], Response::HTTP_CREATED);
                                }
            //     }
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
            $columnsString="";
            $official_time_logs = $this->storeOfficialTimeApplicationLog($official_time_application_id,$process_name,$columnsString);
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
    public function storeOfficialTimeApplicationLog($ot_id,$process_name,$changedFields)
    {
        try {
            $user_id="1";

            $data = [
                'official_time_application_id' => $ot_id,
                'action_by_id' => $user_id,
                'action' => $process_name,
                'date' => date('Y-m-d'),
                'time' => date('H:i:s'),
                'fields' => $changedFields
            ];

            $ot_log = ModelsOtApplicationLog::create($data);

            return $ot_log;
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
