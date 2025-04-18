<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Helpers\Helpers;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
class OfficialTimeApplicationController extends Controller
{
    protected $file_service;
    private $CONTROLLER_NAME = "OfficialTimeApplicationController";

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
            if($official_time_applications->isNotEmpty())
            {
                $official_time_applications_result = $official_time_applications->map(function ($official_time_application) {
                    $logsData = $official_time_application->logs ? $official_time_application->logs : collect();
                    $division = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('section_id');
                    $recommending_name=null;
                    $recommending_position=null;
                    $recommending_code=null;
                    $approving_name=null;
                    $approving_position=null;
                    $approving_code=null;
                    $division_head=Division::where('chief_employee_profile_id',$official_time_application->employee_profile_id)->count();
                    $section_head=Section::where('supervisor_employee_profile_id',$official_time_application->employee_profile_id)->count();
                    $department_head=Department::where('head_employee_profile_id',$official_time_application->employee_profile_id)->count();
                    $omcc = Division::with('chief.personalInformation')->where('area_id','OMCC-DI-001')->first();
                    if ($division_head > 0) {

                        if($omcc)
                        {

                            if($omcc && $omcc->chief  && $omcc->chief->personalInformation != null)
                            {
                                $recommending_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                $recommending_position = $omcc->chief->assignedArea->designation->name ?? null;
                                $recommending_code = $omcc->chief->assignedArea->designation->code ?? null;

                                $approving_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                $approving_position = $omcc->chief->assignedArea->designation->name ?? null;
                                $approving_code = $omcc->chief->assignedArea->designation->code ?? null;
                            }
                        }
                    }
                    else if($section_head > 0 || $department_head > 0)
                    {

                        if($department)
                        {
                            $division_name = Division::with('chief.personalInformation')->find($division);
                            if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                            {
                                $recommending_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                $recommending_position = $division_name->chief->assignedArea->designation->name ?? null;
                                $recommending_code = $division_name->chief->assignedArea->designation->code ?? null;
                            }
                        }
                        if($section)
                        {
                            $division_name = Division::with('chief.personalInformation')->find($division);
                            if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                            {
                                $recommending_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                $recommending_position = $division_name->chief->assignedArea->designation->name ?? null;
                                $recommending_code = $division_name->chief->assignedArea->designation->code ?? null;
                            }
                        }
                        if($omcc) {
                            if($omcc && $omcc->chief  && $omcc->chief->personalInformation != null)
                            {
                                $approving_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                $approving_position = $omcc->chief->assignedArea->designation->name ?? null;
                                $approving_code = $omcc->chief->assignedArea->designation->code ?? null;
                            }
                        }

                    }
                    else
                    {
                        if($division) {
                            $division_name = Division::with('chief.personalInformation')->find($division);

                            if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                            {
                                $approving_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                $approving_position = $division_name->chief->assignedArea->designation->name ?? null;
                                $approving_code = $division_name->chief->assignedArea->designation->code ?? null;
                            }
                        }
                        if($department)
                        {
                            $department_name = Department::with('head.personalInformation')->find($department);
                            if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                            {
                                $recommending_name = optional($department_name->head->personalInformation)->last_name . ', ' . optional($department_name->head->personalInformation)->first_name;
                                $recommending_position = $department_name->head->assignedArea->designation->name ?? null;
                                $recommending_code = $department_name->head->assignedArea->designation->code ?? null;
                            }
                        }
                        if($section)
                        {
                            $section_name = Section::with('supervisor.personalInformation')->find($section);
                            if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                            {
                                $recommending_name = optional($section_name->supervisor->personalInformation)->last_name . ', ' . optional($section_name->supervisor->personalInformation)->first_name;
                                $recommending_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                $recommending_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                            }
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
                        // 'time_from' => $official_time_application->time_from,
                        // 'time_to' => $official_time_application->time_to,
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
                        'position_code' => $official_time_application->employeeProfile->assignedArea->designation->code ?? null,
                        'position_name' => $official_time_application->employeeProfile->assignedArea->designation->name ?? null,
                        'date_created' => $official_time_application->created_at,
                        'recommending_head' =>$recommending_name,
                        'recommending_head_position'=> $recommending_position,
                        'recommending_head_code'=> $recommending_code,
                        'approving_head' =>$approving_name,
                        'approving_head_position' =>$approving_position,
                        'approving_head_code' =>$approving_code,
                        'division_name' => $official_time_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $official_time_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $official_time_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $official_time_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $logsData->map(function ($log) {
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
                                'official_time_id' => $log->official_time_application_id,
                                'action_by' => "{$first_name} {$last_name}" ,
                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),
                    ];
                });
                 return response()->json(['data' => $official_time_applications_result], Response::HTTP_OK);
            }
            else
            {
                return response()->json(['data'=> $official_time_applications,'message' => 'No records available'], Response::HTTP_OK);
            }
        } catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
    public function getOtApplications(Request $request)
    {
        try{
            $user=$request->user;
            $OfficialTimeApplication = [];
            $division = AssignArea::where('employee_profile_id',$user->id)->value('division_id');
            $divisionHeadId = Division::where('id', $division)->value('chief_employee_profile_id');
            $section = AssignArea::where('employee_profile_id',$user->id)->value('section_id');
            $sectionHeadId = Section::where('id', $section)->value('supervisor_employee_profile_id');
            $department = AssignArea::where('employee_profile_id',$user->id)->value('department_id');
            $departmentHeadId = Department::where('id', $department)->value('head_employee_profile_id');
            $training_officer_id = Department::where('id', $department)->value('training_officer_employee_profile_id');
            $division_oic_Id = Division::where('id', $division)->value('oic_employee_profile_id');
            $department_oic_Id = Division::where('id', $division)->value('oic_employee_profile_id');
            $section_oic_id = Section::where('id', $section)->value('oic_employee_profile_id');


            if($divisionHeadId === $user->id || $division_oic_Id === $user->id) {
                $OfficialTimeApplication = OfficialTimeApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','logs'])
                        ->whereHas('employeeProfile.assignedArea', function ($query) use ($division) {
                            $query->where('division_id', $division);
                        })
                        ->where('status', 'for-approval-division-head')
                        ->orwhere('status', 'approved')
                        ->orwhere('status', 'declined')
                        ->get();
                if($OfficialTimeApplication->isNotEmpty())
                {
                    $official_time_applications_result = $OfficialTimeApplication->map(function ($official_time_application) {
                        $logsData = $official_time_application->logs ? $official_time_application->logs : collect();
                        $division = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('section_id');
                        $recommending_name=null;
                                $recommending_position=null;
                                $recommending_code=null;
                                $approving_name=null;
                                $approving_position=null;
                                $approving_code=null;
                                $division_head=Division::where('chief_employee_profile_id',$official_time_application->employee_profile_id)->count();
                                $section_head=Section::where('supervisor_employee_profile_id',$official_time_application->employee_profile_id)->count();
                                $department_head=Department::where('head_employee_profile_id',$official_time_application->employee_profile_id)->count();
                                $omcc = Division::with('chief.personalInformation')->where('area_id','OMCC-DI-001')->first();
                                if ($division_head > 0) {

                                    if($omcc)
                                    {

                                        if($omcc && $omcc->chief  && $omcc->chief->personalInformation != null)
                                        {
                                            $recommending_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                            $recommending_position = $omcc->chief->assignedArea->designation->name ?? null;
                                            $recommending_code = $omcc->chief->assignedArea->designation->code ?? null;

                                            $approving_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                            $approving_position = $omcc->chief->assignedArea->designation->name ?? null;
                                            $approving_code = $omcc->chief->assignedArea->designation->code ?? null;
                                        }
                                    }
                                }
                                else if($section_head > 0 || $department_head > 0)
                                {

                                    if($department)
                                    {
                                        $division_name = Division::with('chief.personalInformation')->find($division);
                                        if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                        {
                                            $recommending_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                            $recommending_position = $division_name->chief->assignedArea->designation->name ?? null;
                                            $recommending_code = $division_name->chief->assignedArea->designation->code ?? null;
                                        }
                                    }
                                    if($section)
                                    {
                                        $division_name = Division::with('chief.personalInformation')->find($division);
                                        if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                        {
                                            $recommending_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                            $recommending_position = $division_name->chief->assignedArea->designation->name ?? null;
                                            $recommending_code = $division_name->chief->assignedArea->designation->code ?? null;
                                        }
                                    }
                                    if($omcc) {
                                        if($omcc && $omcc->chief  && $omcc->chief->personalInformation != null)
                                        {
                                            $approving_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                            $approving_position = $omcc->chief->assignedArea->designation->name ?? null;
                                            $approving_code = $omcc->chief->assignedArea->designation->code ?? null;
                                        }
                                    }

                                }
                                else
                                {
                                    if($division) {
                                        $division_name = Division::with('chief.personalInformation')->find($division);

                                        if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                        {
                                            $approving_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                            $approving_position = $division_name->chief->assignedArea->designation->name ?? null;
                                            $approving_code = $division_name->chief->assignedArea->designation->code ?? null;
                                        }
                                    }
                                    if($department)
                                    {
                                        $department_name = Department::with('head.personalInformation')->find($department);
                                        if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                                        {
                                            $recommending_name = optional($department_name->head->personalInformation)->last_name . ', ' . optional($department_name->head->personalInformation)->first_name;
                                            $recommending_position = $department_name->head->assignedArea->designation->name ?? null;
                                            $recommending_code = $department_name->head->assignedArea->designation->code ?? null;
                                        }
                                    }
                                    if($section)
                                    {
                                        $section_name = Section::with('supervisor.personalInformation')->find($section);
                                        if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                                        {
                                            $recommending_name = optional($section_name->supervisor->personalInformation)->last_name . ', ' . optional($section_name->supervisor->personalInformation)->first_name;
                                            $recommending_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                            $recommending_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                                        }
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
                        // 'time_from' => $official_time_application->time_from,
                        // 'time_to' => $official_time_application->time_to,
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
                        'position_code' => $official_time_application->employeeProfile->assignedArea->designation->code ?? null,
                        'position_name' => $official_time_application->employeeProfile->assignedArea->designation->name ?? null,
                        'date_created' => $official_time_application->created_at,
                        'recommending_head' =>$recommending_name,
                        'recommending_head_position'=> $recommending_position,
                        'recommending_head_code'=> $recommending_code,
                        'approving_head' =>$approving_name,
                        'approving_head_position' =>$approving_position,
                        'approving_head_code' =>$approving_code,
                        'division_name' => $official_time_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $official_time_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $official_time_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $official_time_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $logsData->map(function ($log) {
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
                                'official_time_id' => $log->official_time_application_id,
                                'action_by' => "{$first_name} {$last_name}" ,
                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),

                    ];
                    });
                    return response()->json(['data' => $official_time_applications_result]);
                }
                else
                {
                    return response()->json(['message' => 'No records available'], Response::HTTP_OK);
                }
            }
            else if($departmentHeadId === $user->id || $training_officer_id === $user->id || $department_oic_Id === $user->id) {
                $OfficialTimeApplication = OfficialTimeApplication::with(['employeeProfile.assignedArea.department','employeeProfile.personalInformation','logs' ])
                ->whereHas('employeeProfile.assignedArea', function ($query) use ($department) {
                    $query->where('department_id', $department);
                })
                ->where('status', 'for-approval-department-head')
                ->orWhere('status', 'for-approval-division-head')
                ->orWhere('status', 'approved')
                ->orwhere('status', 'declined')
                ->get();
                if($OfficialTimeApplication->isNotEmpty())
                {
                    $official_time_applications_result = $OfficialTimeApplication->map(function ($official_time_application) {
                        $logsData = $official_time_application->logs ? $official_time_application->logs : collect();
                        $division = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('section_id');
                        $recommending_name=null;
                        $recommending_position=null;
                        $recommending_code=null;
                        $approving_name=null;
                        $approving_position=null;
                        $approving_code=null;
                        $division_head=Division::where('chief_employee_profile_id',$official_time_application->employee_profile_id)->count();
                        $section_head=Section::where('supervisor_employee_profile_id',$official_time_application->employee_profile_id)->count();
                        $department_head=Department::where('head_employee_profile_id',$official_time_application->employee_profile_id)->count();
                        $omcc = Division::with('chief.personalInformation')->where('area_id','OMCC-DI-001')->first();
                        if ($division_head > 0) {

                            if($omcc)
                            {

                                if($omcc && $omcc->chief  && $omcc->chief->personalInformation != null)
                                {
                                    $recommending_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                    $recommending_position = $omcc->chief->assignedArea->designation->name ?? null;
                                    $recommending_code = $omcc->chief->assignedArea->designation->code ?? null;

                                    $approving_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                    $approving_position = $omcc->chief->assignedArea->designation->name ?? null;
                                    $approving_code = $omcc->chief->assignedArea->designation->code ?? null;
                                }
                            }
                        }
                        else if($section_head > 0 || $department_head > 0)
                        {

                            if($department)
                            {
                                $division_name = Division::with('chief.personalInformation')->find($division);
                                if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                {
                                    $recommending_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                    $recommending_position = $division_name->chief->assignedArea->designation->name ?? null;
                                    $recommending_code = $division_name->chief->assignedArea->designation->code ?? null;
                                }
                            }
                            if($section)
                            {
                                $division_name = Division::with('chief.personalInformation')->find($division);
                                if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                {
                                    $recommending_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                    $recommending_position = $division_name->chief->assignedArea->designation->name ?? null;
                                    $recommending_code = $division_name->chief->assignedArea->designation->code ?? null;
                                }
                            }
                            if($omcc) {
                                if($omcc && $omcc->chief  && $omcc->chief->personalInformation != null)
                                {
                                    $approving_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                    $approving_position = $omcc->chief->assignedArea->designation->name ?? null;
                                    $approving_code = $omcc->chief->assignedArea->designation->code ?? null;
                                }
                            }

                        }
                        else
                        {
                            if($division) {
                                $division_name = Division::with('chief.personalInformation')->find($division);

                                if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                {
                                    $approving_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                    $approving_position = $division_name->chief->assignedArea->designation->name ?? null;
                                    $approving_code = $division_name->chief->assignedArea->designation->code ?? null;
                                }
                            }
                            if($department)
                            {
                                $department_name = Department::with('head.personalInformation')->find($department);
                                if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                                {
                                    $recommending_name = optional($department_name->head->personalInformation)->last_name . ', ' . optional($department_name->head->personalInformation)->first_name;
                                    $recommending_position = $department_name->head->assignedArea->designation->name ?? null;
                                    $recommending_code = $department_name->head->assignedArea->designation->code ?? null;
                                }
                            }
                            if($section)
                            {
                                $section_name = Section::with('supervisor.personalInformation')->find($section);
                                if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                                {
                                    $recommending_name = optional($section_name->supervisor->personalInformation)->last_name . ', ' . optional($section_name->supervisor->personalInformation)->first_name;
                                    $recommending_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                    $recommending_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                                }
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
                            // 'time_from' => $official_time_application->time_from,
                            // 'time_to' => $official_time_application->time_to,
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
                            'position_code' => $official_time_application->employeeProfile->assignedArea->designation->code ?? null,
                            'position_name' => $official_time_application->employeeProfile->assignedArea->designation->name ?? null,
                            'date_created' => $official_time_application->created_at,
                            'recommending_head' =>$recommending_name,
                            'recommending_head_position'=> $recommending_position,
                            'recommending_head_code'=> $recommending_code,
                            'approving_head' =>$approving_name,
                            'approving_head_position' =>$approving_position,
                            'approving_head_code' =>$approving_code,
                            'division_name' => $official_time_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $official_time_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $official_time_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $official_time_application->employeeProfile->assignedArea->unit->name ?? null,
                            'logs' => $logsData->map(function ($log) {
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
                                    'official_time_id' => $log->official_time_application_id,
                                    'action_by' => "{$first_name} {$last_name}" ,
                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),

                        ];
                        });
                    return response()->json(['data' => $official_time_applications_result]);
                }
                else
                {
                    return response()->json(['message' => 'No records available'], Response::HTTP_OK);
                }
            }
            else if($sectionHeadId === $user->id || $section_oic_id === $user->id) {
                $official_time_applications = OfficialTimeApplication::with(['employeeProfile.assignedArea.section','employeeProfile.personalInformation','logs'])
                ->whereHas('employeeProfile.assignedArea', function ($query) use ($section) {
                    $query->where('section_id', $section);
                })
                ->where('status', 'for-approval-section-head')
                ->orWhere('status', 'for-approval-division-head')
                ->orWhere('status', 'approved')
                ->orwhere('status', 'declined')
                ->get();
                if($official_time_applications->isNotEmpty())
                {
                    $official_time_applications_result = $official_time_applications->map(function ($official_time_application) {
                        $logsData = $official_time_application->logs ? $official_time_application->logs : collect();
                        $division = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('section_id');
                        $recommending_name=null;
                        $recommending_position=null;
                        $recommending_code=null;
                        $approving_name=null;
                        $approving_position=null;
                        $approving_code=null;
                        $division_head=Division::where('chief_employee_profile_id',$official_time_application->employee_profile_id)->count();
                        $section_head=Section::where('supervisor_employee_profile_id',$official_time_application->employee_profile_id)->count();
                        $department_head=Department::where('head_employee_profile_id',$official_time_application->employee_profile_id)->count();
                        $omcc = Division::with('chief.personalInformation')->where('area_id','OMCC-DI-001')->first();
                        if ($division_head > 0) {

                            if($omcc)
                            {

                                if($omcc && $omcc->chief  && $omcc->chief->personalInformation != null)
                                {
                                    $recommending_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                    $recommending_position = $omcc->chief->assignedArea->designation->name ?? null;
                                    $recommending_code = $omcc->chief->assignedArea->designation->code ?? null;

                                    $approving_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                    $approving_position = $omcc->chief->assignedArea->designation->name ?? null;
                                    $approving_code = $omcc->chief->assignedArea->designation->code ?? null;
                                }
                            }
                        }
                        else if($section_head > 0 || $department_head > 0)
                        {

                            if($department)
                            {
                                $division_name = Division::with('chief.personalInformation')->find($division);
                                if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                {
                                    $recommending_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                    $recommending_position = $division_name->chief->assignedArea->designation->name ?? null;
                                    $recommending_code = $division_name->chief->assignedArea->designation->code ?? null;
                                }
                            }
                            if($section)
                            {
                                $division_name = Division::with('chief.personalInformation')->find($division);
                                if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                {
                                    $recommending_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                    $recommending_position = $division_name->chief->assignedArea->designation->name ?? null;
                                    $recommending_code = $division_name->chief->assignedArea->designation->code ?? null;
                                }
                            }
                            if($omcc) {
                                if($omcc && $omcc->chief  && $omcc->chief->personalInformation != null)
                                {
                                    $approving_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                    $approving_position = $omcc->chief->assignedArea->designation->name ?? null;
                                    $approving_code = $omcc->chief->assignedArea->designation->code ?? null;
                                }
                            }

                        }
                        else
                        {
                            if($division) {
                                $division_name = Division::with('chief.personalInformation')->find($division);

                                if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                {
                                    $approving_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                    $approving_position = $division_name->chief->assignedArea->designation->name ?? null;
                                    $approving_code = $division_name->chief->assignedArea->designation->code ?? null;
                                }
                            }
                            if($department)
                            {
                                $department_name = Department::with('head.personalInformation')->find($department);
                                if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                                {
                                    $recommending_name = optional($department_name->head->personalInformation)->last_name . ', ' . optional($department_name->head->personalInformation)->first_name;
                                    $recommending_position = $department_name->head->assignedArea->designation->name ?? null;
                                    $recommending_code = $department_name->head->assignedArea->designation->code ?? null;
                                }
                            }
                            if($section)
                            {
                                $section_name = Section::with('supervisor.personalInformation')->find($section);
                                if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                                {
                                    $recommending_name = optional($section_name->supervisor->personalInformation)->last_name . ', ' . optional($section_name->supervisor->personalInformation)->first_name;
                                    $recommending_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                    $recommending_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                                }
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
                            // 'time_from' => $official_time_application->time_from,
                            // 'time_to' => $official_time_application->time_to,
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
                            'position_code' => $official_time_application->employeeProfile->assignedArea->designation->code ?? null,
                            'position_name' => $official_time_application->employeeProfile->assignedArea->designation->name ?? null,
                            'date_created' => $official_time_application->created_at,
                            'recommending_head' =>$recommending_name,
                            'recommending_head_position'=> $recommending_position,
                            'recommending_head_code'=> $recommending_code,
                            'approving_head' =>$approving_name,
                            'approving_head_position' =>$approving_position,
                            'approving_head_code' =>$approving_code,
                            'division_name' => $official_time_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $official_time_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $official_time_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $official_time_application->employeeProfile->assignedArea->unit->name ?? null,
                            'logs' => $logsData->map(function ($log) {
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
                                    'official_time_id' => $log->official_time_application_id,
                                    'action_by' => "{$first_name} {$last_name}" ,
                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),
                        ];
                    });
                    return response()->json(['data' => $official_time_applications_result]);
                }
                else
                {
                    return response()->json(['message' => 'No records available'], Response::HTTP_OK);
                }
            }

        } catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'getOtApplications', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function getUserOtApplication(Request $request)
    {
        try{
            $user=$request->user;
            $ot_applications = OfficialTimeApplication::with(['employeeProfile.personalInformation','logs'])
            ->where('employee_profile_id', $user->id)
            ->get();
            if($ot_applications->isNotEmpty())
            {
                $ot_applications_result = $ot_applications->map(function ($ot_application) {
                    $logsData = $ot_application->logs ? $ot_application->logs : collect();
                    $division = AssignArea::where('employee_profile_id',$ot_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$ot_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$ot_application->employee_profile_id)->value('section_id');
                    $recommending_name=null;
                    $recommending_position=null;
                    $recommending_code=null;
                    $approving_name=null;
                    $approving_position=null;
                    $approving_code=null;
                    $division_head=Division::where('chief_employee_profile_id',$ot_application->employee_profile_id)->count();
                    $section_head=Section::where('supervisor_employee_profile_id',$ot_application->employee_profile_id)->count();
                    $department_head=Department::where('head_employee_profile_id',$ot_application->employee_profile_id)->count();
                    $omcc = Division::with('chief.personalInformation')->where('area_id','OMCC-DI-001')->first();
                    if ($division_head > 0) {

                        if($omcc)
                        {

                            if($omcc && $omcc->chief  && $omcc->chief->personalInformation != null)
                            {
                                $recommending_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                $recommending_position = $omcc->chief->assignedArea->designation->name ?? null;
                                $recommending_code = $omcc->chief->assignedArea->designation->code ?? null;

                                $approving_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                $approving_position = $omcc->chief->assignedArea->designation->name ?? null;
                                $approving_code = $omcc->chief->assignedArea->designation->code ?? null;
                            }
                        }
                    }
                    else if($section_head > 0 || $department_head > 0)
                    {

                        if($department)
                        {
                            $division_name = Division::with('chief.personalInformation')->find($division);
                            if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                            {
                                $recommending_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                $recommending_position = $division_name->chief->assignedArea->designation->name ?? null;
                                $recommending_code = $division_name->chief->assignedArea->designation->code ?? null;
                            }
                        }
                        if($section)
                        {
                            $division_name = Division::with('chief.personalInformation')->find($division);
                            if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                            {
                                $recommending_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                $recommending_position = $division_name->chief->assignedArea->designation->name ?? null;
                                $recommending_code = $division_name->chief->assignedArea->designation->code ?? null;
                            }
                        }
                        if($omcc) {
                            if($omcc && $omcc->chief  && $omcc->chief->personalInformation != null)
                            {
                                $approving_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                $approving_position = $omcc->chief->assignedArea->designation->name ?? null;
                                $approving_code = $omcc->chief->assignedArea->designation->code ?? null;
                            }
                        }

                    }
                    else
                    {
                        if($division) {
                            $division_name = Division::with('chief.personalInformation')->find($division);

                            if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                            {
                                $approving_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                $approving_position = $division_name->chief->assignedArea->designation->name ?? null;
                                $approving_code = $division_name->chief->assignedArea->designation->code ?? null;
                            }
                        }
                        if($department)
                        {
                            $department_name = Department::with('head.personalInformation')->find($department);
                            if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                            {
                                $recommending_name = optional($department_name->head->personalInformation)->last_name . ', ' . optional($department_name->head->personalInformation)->first_name;
                                $recommending_position = $department_name->head->assignedArea->designation->name ?? null;
                                $recommending_code = $department_name->head->assignedArea->designation->code ?? null;
                            }
                        }
                        if($section)
                        {
                            $section_name = Section::with('supervisor.personalInformation')->find($section);
                            if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                            {
                                $recommending_name = optional($section_name->supervisor->personalInformation)->last_name . ', ' . optional($section_name->supervisor->personalInformation)->first_name;
                                $recommending_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                $recommending_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                            }
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
                        // 'time_from' => $ot_application->time_from,
                        // 'time_to' => $ot_application->time_to,
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
                        'position_code' => $ot_application->employeeProfile->assignedArea->designation->code ?? null,
                        'position_name' => $ot_application->employeeProfile->assignedArea->designation->name ?? null,
                        'date_created' => $ot_application->created_at,
                        'recommending_head' =>$recommending_name,
                        'recommending_head_position'=> $recommending_position,
                        'recommending_head_code'=> $recommending_code,
                        'approving_head' =>$approving_name,
                        'approving_head_position' =>$approving_position,
                        'approving_head_code' =>$approving_code,
                        'division_name' => $ot_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $ot_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $ot_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $ot_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $logsData->map(function ($log) {
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
                                'official_time_id' => $log->official_time_application_id,
                                'action_by' => "{$first_name} {$last_name}" ,
                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),

                    ];
                });
                    return response()->json(['data' => $ot_applications_result], Response::HTTP_OK);
            }
            else
            {
                return response()->json(['data'=> $ot_applications,'message' => 'No records available'], Response::HTTP_OK);
            }
        } catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'getUserOtApplication', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function store(Request $request)
    {
        try{
            $validatedData = $request->validate([
                'date_from.*' => 'required|date_format:Y-m-d',
                'date_to.*' => [
                    'required',
                    'date_format:Y-m-d',
                    function ($attribute, $value, $fail) use ($request) {
                        $index = explode('.', $attribute)[1];
                        $dateFrom = $request->input('date_from.' . $index);
                        if ($value < $dateFrom) {
                            $fail("The date to must be greater than date from.");
                        }
                    },
                ],
                'certificate_of_appearance' =>'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
                'personal_order' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
                'reason' => 'required|string|max:512',
            ]);

            $user=$request->user;
            $area = AssignArea::where('employee_profile_id',$user->id)->value('division_id');
            // $division = Division::where('id',$area)->value('is_medical');
            $division=true;
            DB::beginTransaction();
            $division_head=Division::where('chief_employee_profile_id',$user->id)->count();
            $section_head=Section::where('supervisor_employee_profile_id',$user->id)->count();
            $department_head=Department::where('head_employee_profile_id',$user->id)->count();
                $official_time_application = new OfficialTimeApplication();
                $official_time_application->employee_profile_id = $user->id;
                $official_time_application->date_from = $request->date_from;
                $official_time_application->date_to = $request->date_to;
                // $official_time_application->time_from = $request->time_from;
                // $official_time_application->time_to = $request->time_to;

                if ($division_head > 0) {
                    $status='for-approval-omcc-head';
                }
                else if($section_head > 0 || $department_head > 0)
                {
                    $status='for-approval-division-head';
                }
                else{
                    $divisions = Division::where('id',$area)->first();
                    if ($divisions->area_id === 'NS-DI-004' || $divisions->area_id === 'MS-DI-002') {
                        $status='for-approval-department-head';

                    }
                    else
                    {
                        $status='for-approval-section-head';

                    }
                }
                $official_time_application->status = $status;
                $official_time_application->reason =$request->reason;
                $official_time_application->date = date('Y-m-d');
                $official_time_application->time =  date('H:i:s');

                if ($request->hasFile('personal_order')) {
                    $fileName=pathinfo($request->file('personal_order')->getClientOriginalName(), PATHINFO_FILENAME);
                    $size = filesize($request->file('personal_order'));
                    $file_name_encrypted = Helpers::checkSaveFile($request->file('personal_order'), '/official_time');

                    $official_time_application->personal_order = $fileName;
                    $official_time_application->personal_order_path = $file_name_encrypted;
                    $official_time_application->personal_order_size = $size;
                }
                if ($request->hasFile('certificate_of_appearance')) {
                    $fileName=pathinfo($request->file('personal_order')->getClientOriginalName(), PATHINFO_FILENAME);
                    $size = filesize($request->file('certificate_of_appearance'));
                    $file_name_encrypted = Helpers::checkSaveFile($request->file('certificate_of_appearance'), '/official_time');

                    $official_time_application->certificate_of_appearance = $fileName;
                    $official_time_application->certificate_of_appearance_path = $file_name_encrypted;
                    $official_time_application->certificate_of_appearance_size = $size;
                }

                $official_time_application->save();
                $ot_id=$official_time_application->id;
                $columnsString="";
                $process_name="Applied";
                $this->storeOfficialTimeApplicationLog($ot_id,$process_name,$columnsString,$user->id);
            DB::commit();

            $official_time_applications = OfficialTimeApplication::with(['employeeProfile.personalInformation','logs'])
            ->where('id',$official_time_application->id)->get();
            $official_time_applications_result = $official_time_applications->map(function ($official_time_application) {
                $logsData = $official_time_application->logs ? $official_time_application->logs : collect();
                 $division = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('section_id');
                    $recommending_name=null;
                    $recommending_position=null;
                    $recommending_code=null;
                    $approving_name=null;
                    $approving_position=null;
                    $approving_code=null;
                    $division_head=Division::where('chief_employee_profile_id',$official_time_application->employee_profile_id)->count();
                    $section_head=Section::where('supervisor_employee_profile_id',$official_time_application->employee_profile_id)->count();
                    $department_head=Department::where('head_employee_profile_id',$official_time_application->employee_profile_id)->count();
                    $omcc = Division::with('chief.personalInformation')->where('area_id','OMCC-DI-001')->first();
                    if ($division_head > 0) {

                        if($omcc)
                        {

                            if($omcc && $omcc->chief  && $omcc->chief->personalInformation != null)
                            {
                                $recommending_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                $recommending_position = $omcc->chief->assignedArea->designation->name ?? null;
                                $recommending_code = $omcc->chief->assignedArea->designation->code ?? null;

                                $approving_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                $approving_position = $omcc->chief->assignedArea->designation->name ?? null;
                                $approving_code = $omcc->chief->assignedArea->designation->code ?? null;
                            }
                        }
                    }
                    else if($section_head > 0 || $department_head > 0)
                    {

                        if($department)
                        {
                            $division_name = Division::with('chief.personalInformation')->find($division);
                            if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                            {
                                $recommending_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                $recommending_position = $division_name->chief->assignedArea->designation->name ?? null;
                                $recommending_code = $division_name->chief->assignedArea->designation->code ?? null;
                            }
                        }
                        if($section)
                        {
                            $division_name = Division::with('chief.personalInformation')->find($division);
                            if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                            {
                                $recommending_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                $recommending_position = $division_name->chief->assignedArea->designation->name ?? null;
                                $recommending_code = $division_name->chief->assignedArea->designation->code ?? null;
                            }
                        }
                        if($omcc) {
                            if($omcc && $omcc->chief  && $omcc->chief->personalInformation != null)
                            {
                                $approving_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                $approving_position = $omcc->chief->assignedArea->designation->name ?? null;
                                $approving_code = $omcc->chief->assignedArea->designation->code ?? null;
                            }
                        }

                    }
                    else
                    {
                        if($division) {
                            $division_name = Division::with('chief.personalInformation')->find($division);

                            if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                            {
                                $approving_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                $approving_position = $division_name->chief->assignedArea->designation->name ?? null;
                                $approving_code = $division_name->chief->assignedArea->designation->code ?? null;
                            }
                        }
                        if($department)
                        {
                            $department_name = Department::with('head.personalInformation')->find($department);
                            if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                            {
                                $recommending_name = optional($department_name->head->personalInformation)->last_name . ', ' . optional($department_name->head->personalInformation)->first_name;
                                $recommending_position = $department_name->head->assignedArea->designation->name ?? null;
                                $recommending_code = $department_name->head->assignedArea->designation->code ?? null;
                            }
                        }
                        if($section)
                        {
                            $section_name = Section::with('supervisor.personalInformation')->find($section);
                            if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                            {
                                $recommending_name = optional($section_name->supervisor->personalInformation)->last_name . ', ' . optional($section_name->supervisor->personalInformation)->first_name;
                                $recommending_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                $recommending_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                            }
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
                        // 'time_from' => $official_time_application->time_from,
                        // 'time_to' => $official_time_application->time_to,
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
                        'position_code' => $official_time_application->employeeProfile->assignedArea->designation->code ?? null,
                        'position_name' => $official_time_application->employeeProfile->assignedArea->designation->name ?? null,
                        'date_created' => $official_time_application->created_at,
                        'recommending_head' =>$recommending_name,
                        'recommending_head_position'=> $recommending_position,
                        'recommending_head_code'=> $recommending_code,
                        'approving_head' =>$approving_name,
                        'approving_head_position' =>$approving_position,
                        'approving_head_code' =>$approving_code,
                        'division_name' => $official_time_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $official_time_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $official_time_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $official_time_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $logsData->map(function ($log) {
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
                                'official_time_id' => $log->official_time_application_id,
                                'action_by' => "{$first_name} {$last_name}" ,
                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),

                    ];
                });
                $singleArray = array_merge(...$official_time_applications_result);
            return response()->json(['message' => 'Official Time Application has been sucessfully saved','data' => $singleArray ], Response::HTTP_OK);
        }catch(\Throwable $th){
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function declineOtApplication($id,Request $request)
    {
        try {
                $user=$request->user;
                $ot_applications = OfficialTimeApplication::where('id','=', $id)
                                                            ->first();
                if($ot_applications)
                {
                    $password_decrypted = Crypt::decryptString($user['password_encrypted']);
                    $password = strip_tags($request->password);
                        if (!Hash::check($password.config('app.salt_value'), $password_decrypted)) {
                            return response()->json(['message' => "Password incorrect."], Response::HTTP_FORBIDDEN);
                        }
                        else
                        {
                            DB::beginTransaction();
                                $ot_application_log = new ModelsOtApplicationLog();
                                $ot_application_log->action = 'declined';
                                $ot_application_log->official_time_application_id = $id;
                                $ot_application_log->date = date('Y-m-d');
                                $ot_application_log->time =  date('H:i:s');
                                $ot_application_log->action_by_id = $user->id;
                                $ot_application_log->save();

                                $ot_application = OfficialTimeApplication::findOrFail($id);
                                $ot_application->status = 'declined';
                                $ot_application->decline_reason = $request->decline_reason;
                                $ot_application->update();
                            DB::commit();

                                $official_time_applications = OfficialTimeApplication::with(['employeeProfile.personalInformation','logs'])
                                ->where('id',$ot_application->id)->get();
                                $official_time_applications_result = $official_time_applications->map(function ($official_time_application) {
                                    $logsData = $official_time_application->logs ? $official_time_application->logs : collect();
                                     $division = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('division_id');
                                        $department = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('department_id');
                                        $section = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('section_id');
                                        $recommending_name=null;
                                        $recommending_position=null;
                                        $recommending_code=null;
                                        $approving_name=null;
                                        $approving_position=null;
                                        $approving_code=null;
                                        $division_head=Division::where('chief_employee_profile_id',$official_time_application->employee_profile_id)->count();
                                        $section_head=Section::where('supervisor_employee_profile_id',$official_time_application->employee_profile_id)->count();
                                        $department_head=Department::where('head_employee_profile_id',$official_time_application->employee_profile_id)->count();
                                        $omcc = Division::with('chief.personalInformation')->where('area_id','OMCC-DI-001')->first();
                                        if ($division_head > 0) {

                                            if($omcc)
                                            {

                                                if($omcc && $omcc->chief  && $omcc->chief->personalInformation != null)
                                                {
                                                    $recommending_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                                    $recommending_position = $omcc->chief->assignedArea->designation->name ?? null;
                                                    $recommending_code = $omcc->chief->assignedArea->designation->code ?? null;

                                                    $approving_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                                    $approving_position = $omcc->chief->assignedArea->designation->name ?? null;
                                                    $approving_code = $omcc->chief->assignedArea->designation->code ?? null;
                                                }
                                            }
                                        }
                                        else if($section_head > 0 || $department_head > 0)
                                        {

                                            if($department)
                                            {
                                                $division_name = Division::with('chief.personalInformation')->find($division);
                                                if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                                {
                                                    $recommending_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                                    $recommending_position = $division_name->chief->assignedArea->designation->name ?? null;
                                                    $recommending_code = $division_name->chief->assignedArea->designation->code ?? null;
                                                }
                                            }
                                            if($section)
                                            {
                                                $division_name = Division::with('chief.personalInformation')->find($division);
                                                if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                                {
                                                    $recommending_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                                    $recommending_position = $division_name->chief->assignedArea->designation->name ?? null;
                                                    $recommending_code = $division_name->chief->assignedArea->designation->code ?? null;
                                                }
                                            }
                                            if($omcc) {
                                                if($omcc && $omcc->chief  && $omcc->chief->personalInformation != null)
                                                {
                                                    $approving_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                                    $approving_position = $omcc->chief->assignedArea->designation->name ?? null;
                                                    $approving_code = $omcc->chief->assignedArea->designation->code ?? null;
                                                }
                                            }

                                        }
                                        else
                                        {
                                            if($division) {
                                                $division_name = Division::with('chief.personalInformation')->find($division);

                                                if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                                {
                                                    $approving_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                                    $approving_position = $division_name->chief->assignedArea->designation->name ?? null;
                                                    $approving_code = $division_name->chief->assignedArea->designation->code ?? null;
                                                }
                                            }
                                            if($department)
                                            {
                                                $department_name = Department::with('head.personalInformation')->find($department);
                                                if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                                                {
                                                    $recommending_name = optional($department_name->head->personalInformation)->last_name . ', ' . optional($department_name->head->personalInformation)->first_name;
                                                    $recommending_position = $department_name->head->assignedArea->designation->name ?? null;
                                                    $recommending_code = $department_name->head->assignedArea->designation->code ?? null;
                                                }
                                            }
                                            if($section)
                                            {
                                                $section_name = Section::with('supervisor.personalInformation')->find($section);
                                                if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                                                {
                                                    $recommending_name = optional($section_name->supervisor->personalInformation)->last_name . ', ' . optional($section_name->supervisor->personalInformation)->first_name;
                                                    $recommending_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                                    $recommending_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                                                }
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
                                            // 'time_from' => $official_time_application->time_from,
                                            // 'time_to' => $official_time_application->time_to,
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
                                            'position_code' => $official_time_application->employeeProfile->assignedArea->designation->code ?? null,
                                            'position_name' => $official_time_application->employeeProfile->assignedArea->designation->name ?? null,
                                            'date_created' => $official_time_application->created_at,
                                            'recommending_head' =>$recommending_name,
                                            'recommending_head_position'=> $recommending_position,
                                            'recommending_head_code'=> $recommending_code,
                                            'approving_head' =>$approving_name,
                                            'approving_head_position' =>$approving_position,
                                            'approving_head_code' =>$approving_code,
                                            'division_name' => $official_time_application->employeeProfile->assignedArea->division->name ?? null,
                                            'department_name' => $official_time_application->employeeProfile->assignedArea->department->name ?? null,
                                            'section_name' => $official_time_application->employeeProfile->assignedArea->section->name ?? null,
                                            'unit_name' => $official_time_application->employeeProfile->assignedArea->unit->name ?? null,
                                            'logs' => $logsData->map(function ($log) {
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
                                                    'official_time_id' => $log->official_time_application_id,
                                                    'action_by' => "{$first_name} {$last_name}" ,
                                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                                    'action' => $log->action,
                                                    'date' => $formatted_date,
                                                    'time' => $log->time,
                                                    'process' => $action
                                                ];
                                            }),

                                        ];
                                    });
                                    $singleArray = array_merge(...$official_time_applications_result);
                                return response(['message' => 'Application has been sucessfully declined', 'data' => $singleArray], Response::HTTP_OK);

                        }

                }
        } catch (\Exception $e) {
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'declineOtApplicationtore', $e->getMessage());
            return response()->json(['message' => $e->getMessage(),  'error'=>true], Response::HTTP_INTERNAL_SERVER_ERROR);
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
                            DB::beginTransaction();
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
                            DB::commit();

                                $official_time_applications = OfficialTimeApplication::with(['employeeProfile.personalInformation','logs'])
                                ->where('id',$ot_application->id)->get();
                                $official_time_applications_result = $official_time_applications->map(function ($official_time_application) {
                                    $logsData = $official_time_application->logs ? $official_time_application->logs : collect();
                                     $division = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('division_id');
                                        $department = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('department_id');
                                        $section = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('section_id');
                                        $chief_name=null;
                                        $chief_position=null;
                                        $chief_code=null;
                                        $head_name=null;
                                        $head_position=null;
                                        $head_code=null;
                                        $supervisor_name=null;
                                        $supervisor_position=null;
                                        $supervisor_code=null;
                                        if($division) {
                                            $division_name = Division::with('chief.personalInformation')->find($division);

                                            if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                            {
                                                $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
                                                $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                                $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
                                            }
                                        }
                                        if($department)
                                        {
                                            $department_name = Department::with('head.personalInformation')->find($department);
                                            if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                                            {
                                                $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                                $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                                $head_code = $department_name->head->assignedArea->designation->code ?? null;
                                            }
                                        }
                                        if($section)
                                        {
                                            $section_name = Section::with('supervisor.personalInformation')->find($section);
                                            if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                                            {
                                                $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                                $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                                $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
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
                                            // 'time_from' => $official_time_application->time_from,
                                            // 'time_to' => $official_time_application->time_to,
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
                                            'position_code' => $official_time_application->employeeProfile->assignedArea->designation->code ?? null,
                                            'position_name' => $official_time_application->employeeProfile->assignedArea->designation->name ?? null,
                                            'date_created' => $official_time_application->created_at,
                                            'division_head' =>$chief_name,
                                            'division_head_position'=> $chief_position,
                                            'division_head_code'=> $chief_code,
                                            'department_head' =>$head_name,
                                            'department_head_position' =>$head_position,
                                            'department_head_code' =>$head_code,
                                            'section_head' =>$supervisor_name,
                                            'section_head_position' =>$supervisor_position,
                                            'section_head_code' =>$supervisor_code,
                                            'division_name' => $official_time_application->employeeProfile->assignedArea->division->name ?? null,
                                            'department_name' => $official_time_application->employeeProfile->assignedArea->department->name ?? null,
                                            'section_name' => $official_time_application->employeeProfile->assignedArea->section->name ?? null,
                                            'unit_name' => $official_time_application->employeeProfile->assignedArea->unit->name ?? null,
                                            'logs' => $logsData->map(function ($log) {
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
                                                    'official_time_id' => $log->official_time_application_id,
                                                    'action_by' => "{$first_name} {$last_name}" ,
                                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                                    'action' => $log->action,
                                                    'date' => $formatted_date,
                                                    'time' => $log->time,
                                                    'process' => $action
                                                ];
                                            }),

                                        ];
                                    });
                                    $singleArray = array_merge(...$official_time_applications_result);
                                return response(['message' => 'Application has been sucessfully cancelled', 'data' => $singleArray], Response::HTTP_OK);

                        //     }
                        //  }
                }
        } catch (\Exception $e) {
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'cancelOtApplication', $e->getMessage());
            return response()->json(['message' => $e->getMessage(),  'error'=>true], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function updateStatus ($id,$status,Request $request)
    {
        try {
                $user = $request->user;
                $password_decrypted = Crypt::decryptString($user['password_encrypted']);
                $password = strip_tags($request->password);
                if (!Hash::check($password.config('app.salt_value'), $password_decrypted)) {
                    return response()->json(['message' => "Password incorrect."], Response::HTTP_FORBIDDEN);
                }
                else
                {
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
                            else if($status == 'for-approval-omcc-head'){
                                $action = 'Aprroved by OMCC Head';
                                $new_status='approved';
                                $message_action="Approved";
                            }
                            $ot_applications = OfficialTimeApplication::where('id','=', $id)
                                                                    ->first();
                            if($ot_applications){
                                DB::beginTransaction();
                                    $ot_application_log = new ModelsOtApplicationLog();
                                    $ot_application_log->action = $action;
                                    $ot_application_log->official_time_application_id = $id;
                                    $ot_application_log->action_by_id = $user->id;
                                    $ot_application_log->date = date('Y-m-d');
                                    $ot_application_log->time =  date('H:i:s');
                                    $ot_application_log->save();

                                    $ot_application = OfficialTimeApplication::findOrFail($id);
                                    $ot_application->status = $new_status;
                                    $ot_application->update();
                                DB::commit();

                                $official_time_applications = OfficialTimeApplication::with(['employeeProfile.personalInformation','logs'])
                                ->where('id',$ot_application->id)->get();
                                $official_time_applications_result = $official_time_applications->map(function ($official_time_application) {
                                    $logsData = $official_time_application->logs ? $official_time_application->logs : collect();
                                     $division = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('division_id');
                                        $department = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('department_id');
                                        $section = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('section_id');
                                        $recommending_name=null;
                                        $recommending_position=null;
                                        $recommending_code=null;
                                        $approving_name=null;
                                        $approving_position=null;
                                        $approving_code=null;
                                        $division_head=Division::where('chief_employee_profile_id',$official_time_application->employee_profile_id)->count();
                                        $section_head=Section::where('supervisor_employee_profile_id',$official_time_application->employee_profile_id)->count();
                                        $department_head=Department::where('head_employee_profile_id',$official_time_application->employee_profile_id)->count();
                                        $omcc = Division::with('chief.personalInformation')->where('area_id','OMCC-DI-001')->first();
                                        if ($division_head > 0) {

                                            if($omcc)
                                            {

                                                if($omcc && $omcc->chief  && $omcc->chief->personalInformation != null)
                                                {
                                                    $recommending_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                                    $recommending_position = $omcc->chief->assignedArea->designation->name ?? null;
                                                    $recommending_code = $omcc->chief->assignedArea->designation->code ?? null;

                                                    $approving_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                                    $approving_position = $omcc->chief->assignedArea->designation->name ?? null;
                                                    $approving_code = $omcc->chief->assignedArea->designation->code ?? null;
                                                }
                                            }
                                        }
                                        else if($section_head > 0 || $department_head > 0)
                                        {

                                            if($department)
                                            {
                                                $division_name = Division::with('chief.personalInformation')->find($division);
                                                if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                                {
                                                    $recommending_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                                    $recommending_position = $division_name->chief->assignedArea->designation->name ?? null;
                                                    $recommending_code = $division_name->chief->assignedArea->designation->code ?? null;
                                                }
                                            }
                                            if($section)
                                            {
                                                $division_name = Division::with('chief.personalInformation')->find($division);
                                                if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                                {
                                                    $recommending_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                                    $recommending_position = $division_name->chief->assignedArea->designation->name ?? null;
                                                    $recommending_code = $division_name->chief->assignedArea->designation->code ?? null;
                                                }
                                            }
                                            if($omcc) {
                                                if($omcc && $omcc->chief  && $omcc->chief->personalInformation != null)
                                                {
                                                    $approving_name = optional($omcc->chief->personalInformation)->last_name . ', ' . optional($omcc->chief->personalInformation)->first_name;
                                                    $approving_position = $omcc->chief->assignedArea->designation->name ?? null;
                                                    $approving_code = $omcc->chief->assignedArea->designation->code ?? null;
                                                }
                                            }

                                        }
                                        else
                                        {
                                            if($division) {
                                                $division_name = Division::with('chief.personalInformation')->find($division);

                                                if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                                {
                                                    $approving_name = optional($division_name->chief->personalInformation)->last_name . ', ' . optional($division_name->chief->personalInformation)->first_name;
                                                    $approving_position = $division_name->chief->assignedArea->designation->name ?? null;
                                                    $approving_code = $division_name->chief->assignedArea->designation->code ?? null;
                                                }
                                            }
                                            if($department)
                                            {
                                                $department_name = Department::with('head.personalInformation')->find($department);
                                                if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                                                {
                                                    $recommending_name = optional($department_name->head->personalInformation)->last_name . ', ' . optional($department_name->head->personalInformation)->first_name;
                                                    $recommending_position = $department_name->head->assignedArea->designation->name ?? null;
                                                    $recommending_code = $department_name->head->assignedArea->designation->code ?? null;
                                                }
                                            }
                                            if($section)
                                            {
                                                $section_name = Section::with('supervisor.personalInformation')->find($section);
                                                if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                                                {
                                                    $recommending_name = optional($section_name->supervisor->personalInformation)->last_name . ', ' . optional($section_name->supervisor->personalInformation)->first_name;
                                                    $recommending_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                                    $recommending_code = $section_name->supervisor->assignedArea->designation->code ?? null;
                                                }
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
                                            // 'time_from' => $official_time_application->time_from,
                                            // 'time_to' => $official_time_application->time_to,
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
                                            'position_code' => $official_time_application->employeeProfile->assignedArea->designation->code ?? null,
                                            'position_name' => $official_time_application->employeeProfile->assignedArea->designation->name ?? null,
                                            'date_created' => $official_time_application->created_at,
                                            'recommending_head' =>$recommending_name,
                                            'recommending_head_position'=> $recommending_position,
                                            'recommending_head_code'=> $recommending_code,
                                            'approving_head' =>$approving_name,
                                            'approving_head_position' =>$approving_position,
                                            'approving_head_code' =>$approving_code,
                                            'division_name' => $official_time_application->employeeProfile->assignedArea->division->name ?? null,
                                            'department_name' => $official_time_application->employeeProfile->assignedArea->department->name ?? null,
                                            'section_name' => $official_time_application->employeeProfile->assignedArea->section->name ?? null,
                                            'unit_name' => $official_time_application->employeeProfile->assignedArea->unit->name ?? null,
                                            'logs' => $logsData->map(function ($log) {
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
                                                    'official_time_id' => $log->official_time_application_id,
                                                    'action_by' => "{$first_name} {$last_name}" ,
                                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                                    'action' => $log->action,
                                                    'date' => $formatted_date,
                                                    'time' => $log->time,
                                                    'process' => $action
                                                ];
                                            }),

                                        ];
                                    });
                                    $singleArray = array_merge(...$official_time_applications_result);
                                return response(['message' => 'Application has been sucessfully '.$message_action, 'data' => $singleArray], Response::HTTP_OK);
                                }
                }

        } catch (\Exception $e) {
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'updateStatus', $e->getMessage());
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
    }
    public function getDivisionOtApplications(Request $request)
    {
        try{
            $id='1';
            $OfficialTimeApplication = [];
            $division = AssignArea::where('employee_profile_id',$id)->value('division_id');
            $divisionHeadId = Division::where('id', $division)->value('chief_employee_profile_id');
            if($divisionHeadId == $id) {
                $OfficialTimeApplication = OfficialTimeApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','logs'])
                        ->whereHas('employeeProfile.assignedArea', function ($query) use ($division) {
                            $query->where('id', $division);
                        })
                        // ->where('status', 'for-approval-division-head')
                        ->get();
                if($OfficialTimeApplication->isNotEmpty())
                {
                    $official_time_applications_result = $OfficialTimeApplication->map(function ($official_time_application) {
                        $logsData = $official_time_application->logs ? $official_time_application->logs : collect();
                        $division = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('section_id');
                        $chief_name=null;
                        $chief_position=null;
                        $chief_code=null;
                        $head_name=null;
                        $head_position=null;
                        $head_code=null;
                        $supervisor_name=null;
                        $supervisor_position=null;
                        $supervisor_code=null;
                        if($division) {
                            $division_name = Division::with('chief.personalInformation')->find($division);

                            if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                            {
                                $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
                                $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
                            }
                        }
                        if($department)
                        {
                            $department_name = Department::with('head.personalInformation')->find($department);
                            if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                            {
                                $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                $head_code = $department_name->head->assignedArea->designation->code ?? null;
                            }
                        }
                        if($section)
                        {
                            $section_name = Section::with('supervisor.personalInformation')->find($section);
                            if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                            {
                                $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
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
                        // 'time_from' => $official_time_application->time_from,
                        // 'time_to' => $official_time_application->time_to,
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
                        'position_code' => $official_time_application->employeeProfile->assignedArea->designation->code ?? null,
                        'position_name' => $official_time_application->employeeProfile->assignedArea->designation->name ?? null,
                        'date_created' => $official_time_application->created_at,
                        'division_head' =>$chief_name,
                        'division_head_position'=> $chief_position,
                        'division_head_code'=> $chief_code,
                        'department_head' =>$head_name,
                        'department_head_position' =>$head_position,
                        'department_head_code' =>$head_code,
                        'section_head' =>$supervisor_name,
                        'section_head_position' =>$supervisor_position,
                        'section_head_code' =>$supervisor_code,
                        'division_name' => $official_time_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $official_time_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $official_time_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $official_time_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $logsData->map(function ($log) {
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
                                'official_time_id' => $log->official_time_application_id,
                                'action_by' => "{$first_name} {$last_name}" ,
                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),

                    ];
                    });
                    return response()->json(['data' => $official_time_applications_result]);
                }
                else
                {
                    return response()->json(['message' => 'No records available'], Response::HTTP_OK);
                }
            }
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'getDivisionOtApplications', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function getDepartmentOtApplications(Request $request)
    {
        try{
            $id='1';
            $department = AssignArea::where('employee_profile_id',$id)->value('department_id');
            $departmentHeadId = Department::where('id', $department)->value('head_employee_profile_id');
            $training_officer_id = Department::where('id', $department)->value('training_officer_employee_profile_id');
            if($departmentHeadId == $id || $training_officer_id == $id) {
                $OfficialTimeApplication = OfficialTimeApplication::with(['employeeProfile.assignedArea.department','employeeProfile.personalInformation','logs' ])
                ->whereHas('employeeProfile.assignedArea', function ($query) use ($department) {
                    $query->where('id', $department);
                })
                // ->where('status', 'for-approval-department-head')
                ->get();
                if($OfficialTimeApplication->isNotEmpty())
                {
                    $official_time_applications_result = $OfficialTimeApplication->map(function ($official_time_application) {
                        $logsData = $official_time_application->logs ? $official_time_application->logs : collect();
                        $division = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('section_id');
                        $chief_name=null;
                        $chief_position=null;
                        $chief_code=null;
                        $head_name=null;
                        $head_position=null;
                        $head_code=null;
                        $supervisor_name=null;
                        $supervisor_position=null;
                        $supervisor_code=null;
                        if($division) {
                            $division_name = Division::with('chief.personalInformation')->find($division);

                            if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                            {
                                $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
                                $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
                            }
                        }
                        if($department)
                        {
                            $department_name = Department::with('head.personalInformation')->find($department);
                            if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                            {
                                $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                $head_code = $department_name->head->assignedArea->designation->code ?? null;
                            }
                        }
                        if($section)
                        {
                            $section_name = Section::with('supervisor.personalInformation')->find($section);
                            if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                            {
                                $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
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
                            // 'time_from' => $official_time_application->time_from,
                            // 'time_to' => $official_time_application->time_to,
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
                            'position_code' => $official_time_application->employeeProfile->assignedArea->designation->code ?? null,
                            'position_name' => $official_time_application->employeeProfile->assignedArea->designation->name ?? null,
                            'date_created' => $official_time_application->created_at,
                            'division_head' =>$chief_name,
                            'division_head_position'=> $chief_position,
                            'division_head_code'=> $chief_code,
                            'department_head' =>$head_name,
                            'department_head_position' =>$head_position,
                            'department_head_code' =>$head_code,
                            'section_head' =>$supervisor_name,
                            'section_head_position' =>$supervisor_position,
                            'section_head_code' =>$supervisor_code,
                            'division_name' => $official_time_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $official_time_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $official_time_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $official_time_application->employeeProfile->assignedArea->unit->name ?? null,
                            'logs' => $logsData->map(function ($log) {
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
                                    'official_time_id' => $log->official_time_application_id,
                                    'action_by' => "{$first_name} {$last_name}" ,
                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),

                        ];
                        });
                    return response()->json(['data' => $official_time_applications_result]);
                }
                else
                {
                    return response()->json(['message' => 'No records available'], Response::HTTP_OK);
                }
            }
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'getDepartmentOtApplications', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function getSectionOtApplications(Request $request)
    {
        try{
            $id='1';
            $section = AssignArea::where('employee_profile_id',$id)->value('section_id');
                $sectionHeadId = Section::where('id', $section)->value('supervisor_employee_profile_id');
                if($sectionHeadId == $id) {
                    $official_time_applications = OfficialTimeApplication::with(['employeeProfile.assignedArea.section','employeeProfile.personalInformation','logs'])
                    ->whereHas('employeeProfile.assignedArea', function ($query) use ($section) {
                        $query->where('id', $section);
                    })
                    // ->where('status', 'for-approval-section-head')
                    ->get();
                    if($official_time_applications->isNotEmpty())
                    {
                        $official_time_applications_result = $official_time_applications->map(function ($official_time_application) {
                            $logsData = $official_time_application->logs ? $official_time_application->logs : collect();
                            $division = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('division_id');
                            $department = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('department_id');
                            $section = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('section_id');
                            $chief_name=null;
                            $chief_position=null;
                            $chief_code=null;
                            $head_name=null;
                            $head_position=null;
                            $head_code=null;
                            $supervisor_name=null;
                            $supervisor_position=null;
                            $supervisor_code=null;
                            if($division) {
                                $division_name = Division::with('chief.personalInformation')->find($division);

                                if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                {
                                    $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
                                    $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                    $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
                                }
                            }
                            if($department)
                            {
                                $department_name = Department::with('head.personalInformation')->find($department);
                                if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                                {
                                    $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                    $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                    $head_code = $department_name->head->assignedArea->designation->code ?? null;
                                }
                            }
                            if($section)
                            {
                                $section_name = Section::with('supervisor.personalInformation')->find($section);
                                if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                                {
                                    $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                    $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                    $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
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
                                // 'time_from' => $official_time_application->time_from,
                                // 'time_to' => $official_time_application->time_to,
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
                                'position_code' => $official_time_application->employeeProfile->assignedArea->designation->code ?? null,
                                'position_name' => $official_time_application->employeeProfile->assignedArea->designation->name ?? null,
                                'date_created' => $official_time_application->created_at,
                                'division_head' =>$chief_name,
                                'division_head_position'=> $chief_position,
                                'division_head_code'=> $chief_code,
                                'department_head' =>$head_name,
                                'department_head_position' =>$head_position,
                                'department_head_code' =>$head_code,
                                'section_head' =>$supervisor_name,
                                'section_head_position' =>$supervisor_position,
                                'section_head_code' =>$supervisor_code,
                                'division_name' => $official_time_application->employeeProfile->assignedArea->division->name ?? null,
                                'department_name' => $official_time_application->employeeProfile->assignedArea->department->name ?? null,
                                'section_name' => $official_time_application->employeeProfile->assignedArea->section->name ?? null,
                                'unit_name' => $official_time_application->employeeProfile->assignedArea->unit->name ?? null,
                                'logs' => $logsData->map(function ($log) {
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
                                        'official_time_id' => $log->official_time_application_id,
                                        'action_by' => "{$first_name} {$last_name}" ,
                                        'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                        'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                        'action' => $log->action,
                                        'date' => $formatted_date,
                                        'time' => $log->time,
                                        'process' => $action
                                    ];
                                }),
                            ];
                        });
                        return response()->json(['data' => $official_time_applications_result]);
                    }
                    else
                    {
                        return response()->json(['message' => 'No records available'], Response::HTTP_OK);
                    }
                }
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'getSectionOtApplications', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function getDeclinedOtApplications(Request $request)
    {
        try{
            $id='1';
            $official_time_applications = OfficialTimeApplication::with(['employeeProfile.personalInformation','logs'])
            // ->where('status', 'declined')
            ->get();
            if($official_time_applications->isNotEmpty())
            {
                $official_time_applications_result = $official_time_applications->map(function ($official_time_application) {
                    $logsData = $official_time_application->logs ? $official_time_application->logs : collect();
                    $division = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$official_time_application->employee_profile_id)->value('section_id');
                    $chief_name=null;
                    $chief_position=null;
                    $chief_code=null;
                    $head_name=null;
                    $head_position=null;
                    $head_code=null;
                    $supervisor_name=null;
                    $supervisor_position=null;
                    $supervisor_code=null;
                    if($division) {
                        $division_name = Division::with('chief.personalInformation')->find($division);

                        if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                        {
                            $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;
                            $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                            $chief_code = $division_name->chief->assignedArea->designation->code ?? null;
                        }
                    }
                    if($department)
                    {
                        $department_name = Department::with('head.personalInformation')->find($department);
                        if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                        {
                            $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                            $head_position = $department_name->head->assignedArea->designation->name ?? null;
                            $head_code = $department_name->head->assignedArea->designation->code ?? null;
                        }
                    }
                    if($section)
                    {
                        $section_name = Section::with('supervisor.personalInformation')->find($section);
                        if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                        {
                            $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                            $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                            $supervisor_code = $section_name->supervisor->assignedArea->designation->code ?? null;
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
                        // 'time_from' => $official_time_application->time_from,
                        // 'time_to' => $official_time_application->time_to,
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
                        'position_code' => $official_time_application->employeeProfile->assignedArea->designation->code ?? null,
                        'position_name' => $official_time_application->employeeProfile->assignedArea->designation->name ?? null,
                        'date_created' => $official_time_application->created_at,
                        'division_head' =>$chief_name,
                        'division_head_position'=> $chief_position,
                        'division_head_code'=> $chief_code,
                        'department_head' =>$head_name,
                        'department_head_position' =>$head_position,
                        'department_head_code' =>$head_code,
                        'section_head' =>$supervisor_name,
                        'section_head_position' =>$supervisor_position,
                        'section_head_code' =>$supervisor_code,
                        'division_name' => $official_time_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $official_time_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $official_time_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $official_time_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $logsData->map(function ($log) {
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
                                'official_time_id' => $log->official_time_application_id,
                                'action_by' => "{$first_name} {$last_name}" ,
                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),

                    ];
                });
                return response()->json(['data' => $official_time_applications_result]);
            }
            else
            {
                return response()->json(['message' => 'No records available'], Response::HTTP_OK);
            }
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'getDeclinedOtApplications', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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
            $official_time_logs = $this->storeOfficialTimeApplicationLog($official_time_application_id,$process_name,$columnsString,1);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'updateOtApplication', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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
            Helpers::errorLog($this->CONTROLLER_NAME, 'storeOfficialTimeApplicationRequirement', $e->getMessage());
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
    }
    public function storeOfficialTimeApplicationLog($ot_id,$process_name,$changedFields,$user_id)
    {
        try {
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
            Helpers::errorLog($this->CONTROLLER_NAME, 'storeOfficialTimeApplicationLog', $e->getMessage());
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
    }
}
