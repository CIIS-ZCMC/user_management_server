<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use Illuminate\Http\Response;
use App\Models\LeaveApplication;
use App\Http\Controllers\Controller;
// use App\Http\Resources\EmployeeLeaveCredit as ResourcesEmployeeLeaveCredit;
// use App\Http\Resources\LeaveApplication as ResourcesLeaveApplication;
use App\Models\AssignArea;
use App\Models\Department;
use App\Models\Division;
use App\Models\EmployeeLeaveCredit;
use App\Models\EmployeeProfile;
use App\Models\LeaveApplicationDateTime;
use App\Models\LeaveApplicationLog;
use App\Models\LeaveApplicationRequirement;
use App\Models\LeaveType;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\FileService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
class LeaveApplicationController extends Controller
{
    protected $file_service;
    public function __construct(
        FileService $file_service
    ) {
        $this->file_service = $file_service;
    }

    public function checkUserLeaveCredit(Request $request)
    {
        $user = $request->user;
        $leaveCredits = EmployeeLeaveCredit::with('leaveType')
        ->where('employee_profile_id', $user->id)
        ->get();

        $totalLeaveCredits = [];
        foreach ($leaveCredits as $credit) {
            $leaveTypeName = $credit->leaveType->name;
            $operation = $credit->operation;
            $creditTotal = $credit->credit_value;
            if (!isset($totalLeaveCredits[$leaveTypeName])) {
                $totalLeaveCredits[$leaveTypeName] = 0;
            }

            if ($operation === 'add') {
                $totalLeaveCredits[$leaveTypeName] += $creditTotal;
            } elseif ($operation === 'deduct') {
                $totalLeaveCredits[$leaveTypeName] -= $creditTotal;
            }
        }
        return response()->json(['balance' => $totalLeaveCredits], Response::HTTP_OK);
    }

    public function index()
    {
        try{
            $leave_applications=[];
            $leave_applications =LeaveApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','dates','logs', 'requirements','employeeProfile.leaveCredits.leaveType'])->get();
            if($leave_applications->isNotEmpty())
            {
                $leave_applications_result = $leave_applications->map(function ($leave_application) {
                    $datesData = $leave_application->dates ? $leave_application->dates : collect();
                    $logsData = $leave_application->logs ? $leave_application->logs : collect();
                    $requirementsData = $leave_application->requirements ? $leave_application->requirements : collect();
                    $add=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                    ->where('operation', 'add')
                    ->sum('credit_value');
                    $deduct=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                    ->where('operation', 'deduct')
                    ->sum('credit_value');
                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                    $hr = Section::with('supervisor.personalInformation')->where('name','HRMO')->first();

                    $chief_name=null;
                    $chief_position=null;
                    $chief_code=null;
                    $head_name=null;
                    $head_position=null;
                    $head_code=null;
                    $supervisor_name=null;
                    $supervisor_position=null;
                    $supervisor_code=null;
                    $hr_name=null;
                    $hr_position=null;
                    $hr_code=null;
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
                    if($hr)
                    {

                        if($hr && $hr->supervisor  && $hr->supervisor->personalInformation != null)
                        {
                            $hr_name = optional($hr->supervisor->personalInformation)->first_name . ' ' . optional($hr->supervisor->personalInformation)->last_name;
                            $hr_position = $hr->supervisor->assignedArea->designation->name ?? null;
                            $hr_code = $hr->supervisor->assignedArea->designation->code ?? null;
                        }
                    }

                    $total_days=0;
                    foreach($leave_application->dates as $date)
                    {
                        $startDate = Carbon::createFromFormat('Y-m-d', $date->date_from);
                        $endDate = Carbon::createFromFormat('Y-m-d', $date->date_to);

                        $numberOfDays = $startDate->diffInDays($endDate) + 1;
                        $total_days += $numberOfDays;
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
                        'days_total' => $total_days,
                        'status' => $leave_application->status ,
                        'remarks' => $leave_application->remarks ,
                        'date' => $leave_application->created_at ,
                        'with_pay' => $leave_application->with_pay ,
                        'employee_id' => $leave_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'position_code' => $leave_application->employeeProfile->assignedArea->designation->code ?? null,
                        'position_name' => $leave_application->employeeProfile->assignedArea->designation->name ?? null,
                        'date_created' => $leave_application->date,
                        'division_head' =>$chief_name,
                        'division_head_position'=> $chief_position,
                        'division_head_code'=> $chief_code,
                        'department_head' =>$head_name,
                        'department_head_position' =>$head_position,
                        'department_head_code' =>$head_code,
                        'section_head' =>$supervisor_name,
                        'section_head_position' =>$supervisor_position,
                        'section_head_code' =>$supervisor_code,
                        'hr_head' =>$hr_name,
                        'hr_head_position' =>$hr_position,
                        'hr_head_code' =>$hr_code,
                        'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
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
                                'leave_application_id' => $log->leave_application_id,
                                'action_by' => "{$first_name} {$last_name}" ,
                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),
                        'requirements' => $requirementsData->map(function ($requirement) {
                            return [
                                'id' => $requirement->id,
                                'leave_application_id' => $requirement->leave_application_id,
                                'name' => $requirement->name,
                                'file_name' => $requirement->file_name,
                                'path' => $requirement->path,
                                'size' => $requirement->size,
                            ];
                        }),
                        'dates' => $datesData->map(function ($date) {
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
            }
            else
            {
                return response()->json(['message' => 'No records available'], Response::HTTP_OK);
            }
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function getUserLeaveApplication(Request $request)
    {
        try{
               $user = $request->user;
                $leave_applications = LeaveApplication::with(['employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
                ->where('employee_profile_id', $user->id)
                ->get();
                if($leave_applications->isNotEmpty())
                {
                    $leave_applications_result = $leave_applications->map(function ($leave_application) {
                        $datesData = $leave_application->dates ? $leave_application->dates : collect();
                        $logsData = $leave_application->logs ? $leave_application->logs : collect();
                        $requirementsData = $leave_application->requirements ? $leave_application->requirements : collect();
                        $add=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                        ->where('operation', 'add')
                        ->sum('credit_value');
                        $deduct=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                        ->where('operation', 'deduct')
                        ->sum('credit_value');
                        $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                        $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                        $hr = Section::with('supervisor.personalInformation')->where('code','HRMO')->first();
                    $chief_name=null;
                    $chief_position=null;
                    $chief_code=null;
                    $head_name=null;
                    $head_position=null;
                    $head_code=null;
                    $supervisor_name=null;
                    $supervisor_position=null;
                    $supervisor_code=null;
                    $hr_name=null;
                    $hr_position=null;
                    $hr_code=null;
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
                    if($hr)
                    {

                        if($hr && $hr->supervisor  && $hr->supervisor->personalInformation != null)
                        {
                            $hr_name = optional($hr->supervisor->personalInformation)->first_name . ' ' . optional($hr->supervisor->personalInformation)->last_name;
                            $hr_position = $hr->supervisor->assignedArea->designation->name ?? null;
                            $hr_code = $hr->supervisor->assignedArea->designation->code ?? null;
                        }
                    }

                        $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                        $total_days=0;
                        foreach($leave_application->dates as $date)
                        {
                            $startDate = Carbon::createFromFormat('Y-m-d', $date->date_from);
                            $endDate = Carbon::createFromFormat('Y-m-d', $date->date_to);

                            $numberOfDays = $startDate->diffInDays($endDate) + 1;
                            $total_days += $numberOfDays;
                        }
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
                            'days_total' => $total_days ,
                            'status' => $leave_application->status ,
                            'remarks' => $leave_application->remarks ,
                            'date' => $leave_application->created_at ,
                            'with_pay' => $leave_application->with_pay ,
                            'employee_id' => $leave_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}" ,
                            'position_code' => $leave_application->employeeProfile->assignedArea->designation->code ?? null,
                            'position_name' => $leave_application->employeeProfile->assignedArea->designation->name ?? null,
                            'date_created' => $leave_application->date,
                            'division_head' =>$chief_name,
                            'division_head_position'=> $chief_position,
                            'division_head_code'=> $chief_code,
                            'department_head' =>$head_name,
                            'department_head_position' =>$head_position,
                            'department_head_code' =>$head_code,
                            'section_head' =>$supervisor_name,
                            'section_head_position' =>$supervisor_position,
                            'section_head_code' =>$supervisor_code,
                            'hr_head' =>$hr_name,
                            'hr_head_position' =>$hr_position,
                            'hr_head_code' =>$hr_code,
                            'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                            'logs' => $logsData->map(function ($log) {
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
                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),
                            'requirements' => $requirementsData->map(function ($requirement) {
                                return [
                                    'id' => $requirement->id,
                                    'leave_application_id' => $requirement->leave_application_id,
                                    'name' => $requirement->name,
                                    'file_name' => $requirement->file_name,
                                    'path' => $requirement->path,
                                    'size' => $requirement->size,
                                ];
                            }),
                            'dates' => $datesData->map(function ($date) {
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

                    $leaveCredits = EmployeeLeaveCredit::with('leaveType')
                    ->where('employee_profile_id', $user->id)
                    ->get();

                    $totalLeaveCredits = [];
                    foreach ($leaveCredits as $credit) {
                        $leaveTypeName = $credit->leaveType->name;
                        $operation = $credit->operation;
                        $creditTotal = $credit->credit_value;
                        if (!isset($totalLeaveCredits[$leaveTypeName])) {
                            $totalLeaveCredits[$leaveTypeName] = 0;
                        }

                        if ($operation === 'add') {
                            $totalLeaveCredits[$leaveTypeName] += $creditTotal;
                        } elseif ($operation === 'deduct') {
                            $totalLeaveCredits[$leaveTypeName] -= $creditTotal;
                        }
                    }


                 return response()->json(['data' => $leave_applications_result,'balance' => $totalLeaveCredits], Response::HTTP_OK);

                }
                else
                {
                    return response()->json(['message' => 'No records available'], Response::HTTP_OK);
                }
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function getLeaveApplications(Request $request)
    {
        $user = $request->user;
        $leave_applications = [];
        // $section_hr = AssignArea::  where('employee_profile_id',$user->id)->value('section_id');
        $hr_head_id = Section::where('code', 'HRMO')->value('supervisor_employee_profile_id');

        $division = AssignArea::where('employee_profile_id',$user->id)->value('division_id');
        $divisionHeadId = Division::where('id', $division)->value('chief_employee_profile_id');
        $department = AssignArea::where('employee_profile_id',$user->id)->value('department_id');
        $departmentHeadId = Department::where('id', $department)->value('head_employee_profile_id');
        $training_officer_id = Department::where('id', $department)->value('training_officer_employee_profile_id');
        $section = AssignArea::where('employee_profile_id',$user->id)->value('section_id');
        $sectionHeadId = Section::where('id', $section)->value('supervisor_employee_profile_id');
        if($hr_head_id == $user->id) {
            $leave_applications = LeaveApplication::with(['employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
            // ->where('status', 'applied')
            ->get();
            if($leave_applications->isNotEmpty())
            {
                $leave_applications_result = $leave_applications->map(function ($leave_application) {
                    $datesData = $leave_application->dates ? $leave_application->dates : collect();
                    $logsData = $leave_application->logs ? $leave_application->logs : collect();
                    $requirementsData = $leave_application->requirements ? $leave_application->requirements : collect();
                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                    $hr = Section::with('supervisor.personalInformation')->where('name','HRMO')->first();
                    $add=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                    ->where('operation', 'add')
                    ->sum('credit_value');
                    $deduct=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                    ->where('operation', 'deduct')
                    ->sum('credit_value');
                    $chief_name=null;
                    $chief_position=null;
                    $chief_code=null;
                    $head_name=null;
                    $head_position=null;
                    $head_code=null;
                    $supervisor_name=null;
                    $supervisor_position=null;
                    $supervisor_code=null;
                    $hr_name=null;
                    $hr_position=null;
                    $hr_code=null;
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
                    if($hr)
                    {

                        if($hr && $hr->supervisor  && $hr->supervisor->personalInformation != null)
                        {
                            $hr_name = optional($hr->supervisor->personalInformation)->first_name . ' ' . optional($hr->supervisor->personalInformation)->last_name;
                            $hr_position = $hr->supervisor->assignedArea->designation->name ?? null;
                            $hr_code = $hr->supervisor->assignedArea->designation->code ?? null;
                        }
                    }

                    $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                    $total_days=0;
                    foreach($leave_application->dates as $date)
                    {
                        $startDate = Carbon::createFromFormat('Y-m-d', $date->date_from);
                        $endDate = Carbon::createFromFormat('Y-m-d', $date->date_to);

                        $numberOfDays = $startDate->diffInDays($endDate) + 1;
                        $total_days += $numberOfDays;
                    }
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
                        'days_total' =>$total_days ,
                        'status' => $leave_application->status ,
                        'remarks' => $leave_application->remarks ,
                        'date' => $leave_application->created_at ,
                        'with_pay' => $leave_application->with_pay ,
                        'employee_id' => $leave_application->employeeProfile->employee_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'position_code' => $leave_application->employeeProfile->assignedArea->designation->code ?? null,
                        'position_name' => $leave_application->employeeProfile->assignedArea->designation->name ?? null,
                        'date_created' => $leave_application->date,
                        'division_head' =>$chief_name,
                        'division_head_position'=> $chief_position,
                        'division_head_code'=> $chief_code,
                        'department_head' =>$head_name,
                        'department_head_position' =>$head_position,
                        'department_head_code' =>$head_code,
                        'section_head' =>$supervisor_name,
                        'section_head_position' =>$supervisor_position,
                        'section_head_code' =>$supervisor_code,
                        'hr_head' =>$hr_name,
                        'hr_head_position' =>$hr_position,
                        'hr_head_code' =>$hr_code,
                        'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $logsData->map(function ($log) {
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
                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),
                        'requirements' => $requirementsData->map(function ($requirement) {
                            return [
                                'id' => $requirement->id,
                                'leave_application_id' => $requirement->leave_application_id,
                                'name' => $requirement->name,
                                'file_name' => $requirement->file_name,
                                'path' => $requirement->path,
                                'size' => $requirement->size,
                            ];
                        }),
                        'dates' => $datesData->map(function ($date) {
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
                return response()->json(['data' => $leave_applications_result]);
            }
            else
            {
                return response()->json(['message' => 'No records available'], Response::HTTP_OK);
            }

        }
        else if($divisionHeadId == $user->id)
        {
            $leave_applications = LeaveApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
            ->whereHas('employeeProfile.assignedArea', function ($query) use ($division) {
                $query->where('id', $division);
            })
            ->where('status', 'for-approval-division-head')
            ->orwhere('status', 'approved')
            ->orwhere('status', 'declined')
            ->get();
            if($leave_applications->isNotEmpty())
            {
                $leave_applications_result = $leave_applications->map(function ($leave_application) {
                    $datesData = $leave_application->dates ? $leave_application->dates : collect();
                    $logsData = $leave_application->logs ? $leave_application->logs : collect();
                    $requirementsData = $leave_application->requirements ? $leave_application->requirements : collect();
                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                    $hr = Section::with('supervisor.personalInformation')->where('name','HRMO')->first();
                    $add=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                    ->where('operation', 'add')
                    ->sum('credit_value');
                    $deduct=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                    ->where('operation', 'deduct')
                    ->sum('credit_value');
                    $chief_name=null;
                    $chief_position=null;
                    $chief_code=null;
                    $head_name=null;
                    $head_position=null;
                    $head_code=null;
                    $supervisor_name=null;
                    $supervisor_position=null;
                    $supervisor_code=null;
                    $hr_name=null;
                    $hr_position=null;
                    $hr_code=null;
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
                    if($hr)
                    {

                        if($hr && $hr->supervisor  && $hr->supervisor->personalInformation != null)
                        {
                            $hr_name = optional($hr->supervisor->personalInformation)->first_name . ' ' . optional($hr->supervisor->personalInformation)->last_name;
                            $hr_position = $hr->supervisor->assignedArea->designation->name ?? null;
                            $hr_code = $hr->supervisor->assignedArea->designation->code ?? null;
                        }
                    }

                    $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                    $total_days=0;
                    foreach($leave_application->dates as $date)
                    {
                        $startDate = Carbon::createFromFormat('Y-m-d', $date->date_from);
                        $endDate = Carbon::createFromFormat('Y-m-d', $date->date_to);

                        $numberOfDays = $startDate->diffInDays($endDate) + 1;
                        $total_days += $numberOfDays;
                    }
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
                        'days_total' => $total_days,
                        'status' => $leave_application->status ,
                        'remarks' => $leave_application->remarks ,
                        'date' => $leave_application->created_at ,
                        'with_pay' => $leave_application->with_pay ,
                        'employee_id' => $leave_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'position_code' => $leave_application->employeeProfile->assignedArea->designation->code ?? null,
                        'position_name' => $leave_application->employeeProfile->assignedArea->designation->name ?? null,
                        'date_created' => $leave_application->date,
                        'division_head' =>$chief_name,
                        'division_head_position'=> $chief_position,
                        'division_head_code'=> $chief_code,
                        'department_head' =>$head_name,
                        'department_head_position' =>$head_position,
                        'department_head_code' =>$head_code,
                        'section_head' =>$supervisor_name,
                        'section_head_position' =>$supervisor_position,
                        'section_head_code' =>$supervisor_code,
                        'hr_head' =>$hr_name,
                        'hr_head_position' =>$hr_position,
                        'hr_head_code' =>$hr_code,
                        'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $logsData->map(function ($log) {
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
                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),
                        'requirements' => $requirementsData->map(function ($requirement) {
                            return [
                                'id' => $requirement->id,
                                'leave_application_id' => $requirement->leave_application_id,
                                'name' => $requirement->name,
                                'file_name' => $requirement->file_name,
                                'path' => $requirement->path,
                                'size' => $requirement->size,
                            ];
                        }),
                        'dates' => $datesData->map(function ($date) {
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
                return response()->json(['data' => $leave_applications_result]);
            }
            else
            {
                return response()->json(['message' => 'No records available'], Response::HTTP_OK);
            }

        }
        else if($departmentHeadId == $user->id || $training_officer_id == $user->id) {
            $leave_applications = LeaveApplication::with(['employeeProfile.assignedArea.department','employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
            ->whereHas('employeeProfile.assignedArea', function ($query) use ($department) {
                $query->where('id', $department);
            })
            ->where('status', 'for-approval-department-head')
            ->orwhere('status', 'for-approval-division-head')
            ->orwhere('status', 'declined')
            ->get();
            if($leave_applications->isNotEmpty())
            {
                $leave_applications_result = $leave_applications->map(function ($leave_application) {
                    $datesData = $leave_application->dates ? $leave_application->dates : collect();
                    $logsData = $leave_application->logs ? $leave_application->logs : collect();
                    $requirementsData = $leave_application->requirements ? $leave_application->requirements : collect();
                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                    $hr = Section::with('supervisor.personalInformation')->where('name','HRMO')->first();
                    $add=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                    ->where('operation', 'add')
                    ->sum('credit_value');
                    $deduct=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                    ->where('operation', 'deduct')
                    ->sum('credit_value');
                    $chief_name=null;
                    $chief_position=null;
                    $chief_code=null;
                    $head_name=null;
                    $head_position=null;
                    $head_code=null;
                    $supervisor_name=null;
                    $supervisor_position=null;
                    $supervisor_code=null;
                    $hr_name=null;
                    $hr_position=null;
                    $hr_code=null;
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
                    if($hr)
                    {

                        if($hr && $hr->supervisor  && $hr->supervisor->personalInformation != null)
                        {
                            $hr_name = optional($hr->supervisor->personalInformation)->first_name . ' ' . optional($hr->supervisor->personalInformation)->last_name;
                            $hr_position = $hr->supervisor->assignedArea->designation->name ?? null;
                            $hr_code = $hr->supervisor->assignedArea->designation->code ?? null;
                        }
                    }

                    $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                    $total_days=0;
                    foreach($leave_application->dates as $date)
                    {
                        $startDate = Carbon::createFromFormat('Y-m-d', $date->date_from);
                        $endDate = Carbon::createFromFormat('Y-m-d', $date->date_to);

                        $numberOfDays = $startDate->diffInDays($endDate) + 1;
                        $total_days += $numberOfDays;
                    }
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
                        'days_total' => $total_days ,
                        'status' => $leave_application->status ,
                        'remarks' => $leave_application->remarks ,
                        'date' => $leave_application->created_at ,
                        'with_pay' => $leave_application->with_pay ,
                        'employee_id' => $leave_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'position_code' => $leave_application->employeeProfile->assignedArea->designation->code ?? null,
                        'position_name' => $leave_application->employeeProfile->assignedArea->designation->name ?? null,
                        'date_created' => $leave_application->date,
                        'division_head' =>$chief_name,
                        'division_head_position'=> $chief_position,
                        'division_head_code'=> $chief_code,
                        'department_head' =>$head_name,
                        'department_head_position' =>$head_position,
                        'department_head_code' =>$head_code,
                        'section_head' =>$supervisor_name,
                        'section_head_position' =>$supervisor_position,
                        'section_head_code' =>$supervisor_code,
                        'hr_head' =>$hr_name,
                        'hr_head_position' =>$hr_position,
                        'hr_head_code' =>$hr_code,
                        'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $logsData->map(function ($log) {
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
                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),
                        'requirements' => $requirementsData->map(function ($requirement) {
                            return [
                                'id' => $requirement->id,
                                'leave_application_id' => $requirement->leave_application_id,
                                'name' => $requirement->name,
                                'file_name' => $requirement->file_name,
                                'path' => $requirement->path,
                                'size' => $requirement->size,
                            ];
                        }),
                        'dates' => $datesData->map(function ($date) {
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
                return response()->json(['data' => $leave_applications_result]);
            }
            else
            {
                return response()->json(['message' => 'No records available'], Response::HTTP_OK);
            }
        }
        else if($sectionHeadId == $user->id) {

            $leave_applications = LeaveApplication::with(['employeeProfile.assignedArea.section','employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
            ->whereHas('employeeProfile.assignedArea', function ($query) use ($section) {
                $query->where('id', $section);
            })
            ->where('status', 'for-approval-section-head')
            ->orwhere('status', 'for-approval-division-head')
            ->orwhere('status', 'declined')
            ->get();
            if($leave_applications->isNotEmpty())
            {
                $leave_applications_result = $leave_applications->map(function ($leave_application) {
                    $datesData = $leave_application->dates ? $leave_application->dates : collect();
                    $logsData = $leave_application->logs ? $leave_application->logs : collect();
                    $requirementsData = $leave_application->requirements ? $leave_application->requirements : collect();
                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                    $hr = Section::with('supervisor.personalInformation')->where('name','HRMO')->first();
                    $add=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                    ->where('operation', 'add')
                    ->sum('credit_value');
                    $deduct=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                    ->where('operation', 'deduct')
                    ->sum('credit_value');
                    $chief_name=null;
                    $chief_position=null;
                    $chief_code=null;
                    $head_name=null;
                    $head_position=null;
                    $head_code=null;
                    $supervisor_name=null;
                    $supervisor_position=null;
                    $supervisor_code=null;
                    $hr_name=null;
                    $hr_position=null;
                    $hr_code=null;
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
                    if($hr)
                    {

                        if($hr && $hr->supervisor  && $hr->supervisor->personalInformation != null)
                        {
                            $hr_name = optional($hr->supervisor->personalInformation)->first_name . ' ' . optional($hr->supervisor->personalInformation)->last_name;
                            $hr_position = $hr->supervisor->assignedArea->designation->name ?? null;
                            $hr_code = $hr->supervisor->assignedArea->designation->code ?? null;
                        }
                    }

                    $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                    $total_days=0;
                    foreach($leave_application->dates as $date)
                    {
                        $startDate = Carbon::createFromFormat('Y-m-d', $date->date_from);
                        $endDate = Carbon::createFromFormat('Y-m-d', $date->date_to);

                        $numberOfDays = $startDate->diffInDays($endDate) + 1;
                        $total_days += $numberOfDays;
                    }
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
                        'days_total' => $total_days ,
                        'status' => $leave_application->status ,
                        'remarks' => $leave_application->remarks ,
                        'date' => $leave_application->created_at ,
                        'with_pay' => $leave_application->with_pay ,
                        'employee_id' => $leave_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'position_code' => $leave_application->employeeProfile->assignedArea->designation->code ?? null,
                        'position_name' => $leave_application->employeeProfile->assignedArea->designation->name ?? null,
                        'date_created' => $leave_application->date,
                        'division_head' =>$chief_name,
                        'division_head_position'=> $chief_position,
                        'division_head_code'=> $chief_code,
                        'department_head' =>$head_name,
                        'department_head_position' =>$head_position,
                        'department_head_code' =>$head_code,
                        'section_head' =>$supervisor_name,
                        'section_head_position' =>$supervisor_position,
                        'section_head_code' =>$supervisor_code,
                        'hr_head' =>$hr_name,
                        'hr_head_position' =>$hr_position,
                        'hr_head_code' =>$hr_code,
                        'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $logsData->map(function ($log) {
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
                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),
                        'requirements' => $requirementsData->map(function ($requirement) {
                            return [
                                'id' => $requirement->id,
                                'leave_application_id' => $requirement->leave_application_id,
                                'name' => $requirement->name,
                                'file_name' => $requirement->file_name,
                                'path' => $requirement->path,
                                'size' => $requirement->size,
                            ];
                        }),
                        'dates' => $datesData->map(function ($date) {
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
                return response()->json(['data' => $leave_applications_result]);
            }
            else
            {
                return response()->json(['message' => 'No records available'], Response::HTTP_OK);
            }

        }

    }

    public function getHRLeaveApplications(Request $request)
    {
        try{
            $id='1';
            $status = $request->status;
            $leave_applications = [];
            $section = AssignArea::where('employee_profile_id',$id)->value('section_id');
            $hr_head_id = Section::where('id', $section)->value('supervisor_employee_profile_id');
            if($hr_head_id == $id) {
                $leave_applications = LeaveApplication::with(['employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
                ->where('status', 'applied')
                ->get();
                if($leave_applications->isNotEmpty())
                {
                    $leave_applications_result = $leave_applications->map(function ($leave_application) {
                        $datesData = $leave_application->dates ? $leave_application->dates : collect();
                        $logsData = $leave_application->logs ? $leave_application->logs : collect();
                        $requirementsData = $leave_application->requirements ? $leave_application->requirements : collect();
                        $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                        $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                        $hr = Section::with('supervisor.personalInformation')->where('name','HR')->orWhere('name','Human Resource')->first();
                        $add=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                        ->where('operation', 'add')
                        ->sum('credit_value');
                        $deduct=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                        ->where('operation', 'deduct')
                        ->sum('credit_value');
                        $chief_name=null;
                        $chief_position=null;
                        $chief_code=null;
                        $head_name=null;
                        $head_position=null;
                        $head_code=null;
                        $supervisor_name=null;
                        $supervisor_position=null;
                        $supervisor_code=null;
                        $hr_name=null;
                        $hr_position=null;
                        $hr_code=null;
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
                        if($hr)
                        {

                            if($hr && $hr->supervisor  && $hr->supervisor->personalInformation != null)
                            {
                                $hr_name = optional($hr->supervisor->personalInformation)->first_name . ' ' . optional($hr->supervisor->personalInformation)->last_name;
                                $hr_position = $hr->supervisor->assignedArea->designation->name ?? null;
                                $hr_code = $hr->supervisor->assignedArea->designation->code ?? null;
                            }
                        }

                        $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                        $total_days=0;
                        foreach($leave_application->dates as $date)
                        {
                            $startDate = Carbon::createFromFormat('Y-m-d', $date->date_from);
                            $endDate = Carbon::createFromFormat('Y-m-d', $date->date_to);

                            $numberOfDays = $startDate->diffInDays($endDate) + 1;
                            $total_days += $numberOfDays;
                        }
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
                            'days_total' =>$total_days ,
                            'status' => $leave_application->status ,
                            'remarks' => $leave_application->remarks ,
                            'date' => $leave_application->created_at ,
                            'with_pay' => $leave_application->with_pay ,
                            'employee_id' => $leave_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}" ,
                            'position_code' => $leave_application->employeeProfile->assignedArea->designation->code ?? null,
                            'position_name' => $leave_application->employeeProfile->assignedArea->designation->name ?? null,
                            'date_created' => $leave_application->date,
                            'division_head' =>$chief_name,
                            'division_head_position'=> $chief_position,
                            'division_head_code'=> $chief_code,
                            'department_head' =>$head_name,
                            'department_head_position' =>$head_position,
                            'department_head_code' =>$head_code,
                            'section_head' =>$supervisor_name,
                            'section_head_position' =>$supervisor_position,
                            'section_head_code' =>$supervisor_code,
                            'hr_head' =>$hr_name,
                            'hr_head_position' =>$hr_position,
                            'hr_head_code' =>$hr_code,
                            'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                            'logs' => $logsData->map(function ($log) {
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
                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),
                            'requirements' => $requirementsData->map(function ($requirement) {
                                return [
                                    'id' => $requirement->id,
                                    'leave_application_id' => $requirement->leave_application_id,
                                    'name' => $requirement->name,
                                    'file_name' => $requirement->file_name,
                                    'path' => $requirement->path,
                                    'size' => $requirement->size,
                                ];
                            }),
                            'dates' => $datesData->map(function ($date) {
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
                    return response()->json(['data' => $leave_applications_result]);
                }
                else
                {
                    return response()->json(['message' => 'No records available'], Response::HTTP_OK);
                }

            }

        }
        catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }

    }

    public function getDivisionLeaveApplications(Request $request)
    {
        try{
            $id='1';
            $status = $request->status;
            $leave_applications = [];
            $division = AssignArea::where('employee_profile_id',$id)->value('division_id');
            $divisionHeadId = Division::where('id', $division)->value('chief_employee_profile_id');
            if($divisionHeadId == $id) {
                $leave_applications = LeaveApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
                ->whereHas('employeeProfile.assignedArea', function ($query) use ($division) {
                    $query->where('id', $division);
                })
                ->where('status', 'for-approval-division-head')
                ->get();
                if($leave_applications->isNotEmpty())
                {
                    $leave_applications_result = $leave_applications->map(function ($leave_application) {
                        $datesData = $leave_application->dates ? $leave_application->dates : collect();
                        $logsData = $leave_application->logs ? $leave_application->logs : collect();
                        $requirementsData = $leave_application->requirements ? $leave_application->requirements : collect();
                        $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                        $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                        $hr = Section::with('supervisor.personalInformation')->where('name','HR')->orWhere('name','Human Resource')->first();
                        $add=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                        ->where('operation', 'add')
                        ->sum('credit_value');
                        $deduct=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                        ->where('operation', 'deduct')
                        ->sum('credit_value');
                        $chief_name=null;
                        $chief_position=null;
                        $chief_code=null;
                        $head_name=null;
                        $head_position=null;
                        $head_code=null;
                        $supervisor_name=null;
                        $supervisor_position=null;
                        $supervisor_code=null;
                        $hr_name=null;
                        $hr_position=null;
                        $hr_code=null;
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
                        if($hr)
                        {

                            if($hr && $hr->supervisor  && $hr->supervisor->personalInformation != null)
                            {
                                $hr_name = optional($hr->supervisor->personalInformation)->first_name . ' ' . optional($hr->supervisor->personalInformation)->last_name;
                                $hr_position = $hr->supervisor->assignedArea->designation->name ?? null;
                                $hr_code = $hr->supervisor->assignedArea->designation->code ?? null;
                            }
                        }

                        $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                        $total_days=0;
                        foreach($leave_application->dates as $date)
                        {
                            $startDate = Carbon::createFromFormat('Y-m-d', $date->date_from);
                            $endDate = Carbon::createFromFormat('Y-m-d', $date->date_to);

                            $numberOfDays = $startDate->diffInDays($endDate) + 1;
                            $total_days += $numberOfDays;
                        }
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
                            'days_total' => $total_days,
                            'status' => $leave_application->status ,
                            'remarks' => $leave_application->remarks ,
                            'date' => $leave_application->created_at ,
                            'with_pay' => $leave_application->with_pay ,
                            'employee_id' => $leave_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}" ,
                            'position_code' => $leave_application->employeeProfile->assignedArea->designation->code ?? null,
                            'position_name' => $leave_application->employeeProfile->assignedArea->designation->name ?? null,
                            'date_created' => $leave_application->date,
                            'division_head' =>$chief_name,
                            'division_head_position'=> $chief_position,
                            'division_head_code'=> $chief_code,
                            'department_head' =>$head_name,
                            'department_head_position' =>$head_position,
                            'department_head_code' =>$head_code,
                            'section_head' =>$supervisor_name,
                            'section_head_position' =>$supervisor_position,
                            'section_head_code' =>$supervisor_code,
                            'hr_head' =>$hr_name,
                            'hr_head_position' =>$hr_position,
                            'hr_head_code' =>$hr_code,
                            'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                            'logs' => $logsData->map(function ($log) {
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
                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),
                            'requirements' => $requirementsData->map(function ($requirement) {
                                return [
                                    'id' => $requirement->id,
                                    'leave_application_id' => $requirement->leave_application_id,
                                    'name' => $requirement->name,
                                    'file_name' => $requirement->file_name,
                                    'path' => $requirement->path,
                                    'size' => $requirement->size,
                                ];
                            }),
                            'dates' => $datesData->map(function ($date) {
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
                    return response()->json(['data' => $leave_applications_result]);
                }
                else
                {
                    return response()->json(['message' => 'No records available'], Response::HTTP_OK);
                }

            }
        }
        catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }

    }

    public function getDepartmentLeaveApplications(Request $request)
    {
        try{
            $id='1';
            $status = $request->status;
            $leave_applications = [];
            $department = AssignArea::where('employee_profile_id',$id)->value('department_id');
            $departmentHeadId = Department::where('id', $department)->value('head_employee_profile_id');
            $training_officer_id = Department::where('id', $department)->value('training_officer_employee_profile_id');
            if($departmentHeadId == $id || $training_officer_id == $id) {
                $leave_applications = LeaveApplication::with(['employeeProfile.assignedArea.department','employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
                ->whereHas('employeeProfile.assignedArea', function ($query) use ($department) {
                    $query->where('id', $department);
                })
                ->where('status', 'for-approval-department-head')
                ->get();
                if($leave_applications->isNotEmpty())
                {
                    $leave_applications_result = $leave_applications->map(function ($leave_application) {
                        $datesData = $leave_application->dates ? $leave_application->dates : collect();
                        $logsData = $leave_application->logs ? $leave_application->logs : collect();
                        $requirementsData = $leave_application->requirements ? $leave_application->requirements : collect();
                        $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                        $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                        $hr = Section::with('supervisor.personalInformation')->where('name','HR')->orWhere('name','Human Resource')->first();
                        $add=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                        ->where('operation', 'add')
                        ->sum('credit_value');
                        $deduct=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                        ->where('operation', 'deduct')
                        ->sum('credit_value');
                        $chief_name=null;
                        $chief_position=null;
                        $chief_code=null;
                        $head_name=null;
                        $head_position=null;
                        $head_code=null;
                        $supervisor_name=null;
                        $supervisor_position=null;
                        $supervisor_code=null;
                        $hr_name=null;
                        $hr_position=null;
                        $hr_code=null;
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
                        if($hr)
                        {

                            if($hr && $hr->supervisor  && $hr->supervisor->personalInformation != null)
                            {
                                $hr_name = optional($hr->supervisor->personalInformation)->first_name . ' ' . optional($hr->supervisor->personalInformation)->last_name;
                                $hr_position = $hr->supervisor->assignedArea->designation->name ?? null;
                                $hr_code = $hr->supervisor->assignedArea->designation->code ?? null;
                            }
                        }

                        $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                        $total_days=0;
                        foreach($leave_application->dates as $date)
                        {
                            $startDate = Carbon::createFromFormat('Y-m-d', $date->date_from);
                            $endDate = Carbon::createFromFormat('Y-m-d', $date->date_to);

                            $numberOfDays = $startDate->diffInDays($endDate) + 1;
                            $total_days += $numberOfDays;
                        }
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
                            'days_total' => $total_days ,
                            'status' => $leave_application->status ,
                            'remarks' => $leave_application->remarks ,
                            'date' => $leave_application->created_at ,
                            'with_pay' => $leave_application->with_pay ,
                            'employee_id' => $leave_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}" ,
                            'position_code' => $leave_application->employeeProfile->assignedArea->designation->code ?? null,
                            'position_name' => $leave_application->employeeProfile->assignedArea->designation->name ?? null,
                            'date_created' => $leave_application->date,
                            'division_head' =>$chief_name,
                            'division_head_position'=> $chief_position,
                            'division_head_code'=> $chief_code,
                            'department_head' =>$head_name,
                            'department_head_position' =>$head_position,
                            'department_head_code' =>$head_code,
                            'section_head' =>$supervisor_name,
                            'section_head_position' =>$supervisor_position,
                            'section_head_code' =>$supervisor_code,
                            'hr_head' =>$hr_name,
                            'hr_head_position' =>$hr_position,
                            'hr_head_code' =>$hr_code,
                            'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                            'logs' => $logsData->map(function ($log) {
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
                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),
                            'requirements' => $requirementsData->map(function ($requirement) {
                                return [
                                    'id' => $requirement->id,
                                    'leave_application_id' => $requirement->leave_application_id,
                                    'name' => $requirement->name,
                                    'file_name' => $requirement->file_name,
                                    'path' => $requirement->path,
                                    'size' => $requirement->size,
                                ];
                            }),
                            'dates' => $datesData->map(function ($date) {
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
                    return response()->json(['data' => $leave_applications_result]);
                }
                else
                {
                    return response()->json(['message' => 'No records available'], Response::HTTP_OK);
                }
            }
        }
        catch(\Throwable $th){

        return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function getSectionLeaveApplications(Request $request)
    {
        try{
            $id='1';
            $status = $request->status;
            $leave_applications = [];
            $section = AssignArea::where('employee_profile_id',$id)->value('section_id');
            $sectionHeadId = Section::where('id', $section)->value('supervisor_employee_profile_id');
            if($sectionHeadId == $id) {

                $leave_applications = LeaveApplication::with(['employeeProfile.assignedArea.section','employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
                ->whereHas('employeeProfile.assignedArea', function ($query) use ($section) {
                    $query->where('id', $section);
                })
                ->where('status', 'for-approval-section-head')
                ->get();
                if($leave_applications->isNotEmpty())
                {
                    $leave_applications_result = $leave_applications->map(function ($leave_application) {
                        $datesData = $leave_application->dates ? $leave_application->dates : collect();
                        $logsData = $leave_application->logs ? $leave_application->logs : collect();
                        $requirementsData = $leave_application->requirements ? $leave_application->requirements : collect();
                        $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                        $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                        $hr = Section::with('supervisor.personalInformation')->where('name','HR')->orWhere('name','Human Resource')->first();
                        $add=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                        ->where('operation', 'add')
                        ->sum('credit_value');
                        $deduct=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                        ->where('operation', 'deduct')
                        ->sum('credit_value');
                        $chief_name=null;
                        $chief_position=null;
                        $chief_code=null;
                        $head_name=null;
                        $head_position=null;
                        $head_code=null;
                        $supervisor_name=null;
                        $supervisor_position=null;
                        $supervisor_code=null;
                        $hr_name=null;
                        $hr_position=null;
                        $hr_code=null;
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
                        if($hr)
                        {

                            if($hr && $hr->supervisor  && $hr->supervisor->personalInformation != null)
                            {
                                $hr_name = optional($hr->supervisor->personalInformation)->first_name . ' ' . optional($hr->supervisor->personalInformation)->last_name;
                                $hr_position = $hr->supervisor->assignedArea->designation->name ?? null;
                                $hr_code = $hr->supervisor->assignedArea->designation->code ?? null;
                            }
                        }

                        $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                        $total_days=0;
                        foreach($leave_application->dates as $date)
                        {
                            $startDate = Carbon::createFromFormat('Y-m-d', $date->date_from);
                            $endDate = Carbon::createFromFormat('Y-m-d', $date->date_to);

                            $numberOfDays = $startDate->diffInDays($endDate) + 1;
                            $total_days += $numberOfDays;
                        }
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
                            'days_total' => $total_days ,
                            'status' => $leave_application->status ,
                            'remarks' => $leave_application->remarks ,
                            'date' => $leave_application->created_at ,
                            'with_pay' => $leave_application->with_pay ,
                            'employee_id' => $leave_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}" ,
                            'position_code' => $leave_application->employeeProfile->assignedArea->designation->code ?? null,
                            'position_name' => $leave_application->employeeProfile->assignedArea->designation->name ?? null,
                            'date_created' => $leave_application->date,
                            'division_head' =>$chief_name,
                            'division_head_position'=> $chief_position,
                            'division_head_code'=> $chief_code,
                            'department_head' =>$head_name,
                            'department_head_position' =>$head_position,
                            'department_head_code' =>$head_code,
                            'section_head' =>$supervisor_name,
                            'section_head_position' =>$supervisor_position,
                            'section_head_code' =>$supervisor_code,
                            'hr_head' =>$hr_name,
                            'hr_head_position' =>$hr_position,
                            'hr_head_code' =>$hr_code,
                            'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                            'logs' => $logsData->map(function ($log) {
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
                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),
                            'requirements' => $requirementsData->map(function ($requirement) {
                                return [
                                    'id' => $requirement->id,
                                    'leave_application_id' => $requirement->leave_application_id,
                                    'name' => $requirement->name,
                                    'file_name' => $requirement->file_name,
                                    'path' => $requirement->path,
                                    'size' => $requirement->size,
                                ];
                            }),
                            'dates' => $datesData->map(function ($date) {
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
                    return response()->json(['data' => $leave_applications_result]);
                }
                else
                {
                    return response()->json(['message' => 'No records available'], Response::HTTP_OK);
                }

            }
        }
        catch(\Throwable $th){

        return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function getDeclinedLeaveApplications(Request $request)
    {
        try{
            $id='1';
            $status = $request->status;
            $leave_applications = [];
            $leave_applications = LeaveApplication::with(['employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
            ->whereHas('logs', function ($query) use ($id) {
                $query->where('action_by_id', $id);
            })
                ->where('status', 'declined')
                ->get();
            if($leave_applications->isNotEmpty())
            {
                $leave_applications_result = $leave_applications->map(function ($leave_application) {
                    $datesData = $leave_application->dates ? $leave_application->dates : collect();
                    $logsData = $leave_application->logs ? $leave_application->logs : collect();
                    $requirementsData = $leave_application->requirements ? $leave_application->requirements : collect();
                        $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                        $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                        $hr = Section::with('supervisor.personalInformation')->where('name','HR')->orWhere('name','Human Resource')->first();
                        $add=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                        ->where('operation', 'add')
                        ->sum('credit_value');
                        $deduct=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                        ->where('operation', 'deduct')
                        ->sum('credit_value');
                        $chief_name=null;
                        $chief_position=null;
                        $chief_code=null;
                        $head_name=null;
                        $head_position=null;
                        $head_code=null;
                        $supervisor_name=null;
                        $supervisor_position=null;
                        $supervisor_code=null;
                        $hr_name=null;
                        $hr_position=null;
                        $hr_code=null;
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
                        if($hr)
                        {

                            if($hr && $hr->supervisor  && $hr->supervisor->personalInformation != null)
                            {
                                $hr_name = optional($hr->supervisor->personalInformation)->first_name . ' ' . optional($hr->supervisor->personalInformation)->last_name;
                                $hr_position = $hr->supervisor->assignedArea->designation->name ?? null;
                                $hr_code = $hr->supervisor->assignedArea->designation->code ?? null;
                            }
                        }

                        $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                        $total_days=0;
                        foreach($leave_application->dates as $date)
                        {
                            $startDate = Carbon::createFromFormat('Y-m-d', $date->date_from);
                            $endDate = Carbon::createFromFormat('Y-m-d', $date->date_to);
                            $numberOfDays = $startDate->diffInDays($endDate) + 1;
                            $total_days += $numberOfDays;
                        }
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
                            'days_total' => $total_days ,
                            'status' => $leave_application->status ,
                            'remarks' => $leave_application->remarks ,
                            'date' => $leave_application->date ,
                            'with_pay' => $leave_application->with_pay ,
                            'employee_id' => $leave_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}" ,
                            'position_code' => $leave_application->employeeProfile->assignedArea->designation->code ?? null,
                            'position_name' => $leave_application->employeeProfile->assignedArea->designation->name ?? null,
                            'date_created' => $leave_application->created_at,
                            'division_head' =>$chief_name,
                            'division_head_position'=> $chief_position,
                            'division_head_code'=> $chief_code,
                            'department_head' =>$head_name,
                            'department_head_position' =>$head_position,
                            'department_head_code' =>$head_code,
                            'section_head' =>$supervisor_name,
                            'section_head_position' =>$supervisor_position,
                            'section_head_code' =>$supervisor_code,
                            'hr_head' =>$hr_name,
                            'hr_head_position' =>$hr_position,
                            'hr_head_code' =>$hr_code,
                            'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
                            'logs' => $logsData->map(function ($log) {
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
                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),
                            'requirements' => $requirementsData->map(function ($requirement) {
                                return [
                                    'id' => $requirement->id,
                                    'leave_application_id' => $requirement->leave_application_id,
                                    'name' => $requirement->name,
                                    'file_name' => $requirement->file_name,
                                    'path' => $requirement->path,
                                    'size' => $requirement->size,
                                ];
                            }),
                            'dates' => $datesData->map(function ($date) {
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
                    return response()->json(['data' => $leave_applications_result]);
            }
            else
            {
                return response()->json(['message' => 'No records available'], Response::HTTP_OK);
            }

        }
        catch(\Throwable $th){

        return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function updateLeaveApplicationStatus ($id,$status,Request $request)
    {
        try {
                $user = $request->user;
                $leave_applications = LeaveApplication::where('id','=', $id)
                ->first();
                $area = AssignArea::where('employee_profile_id',$leave_applications->employee_profile_id)->value('division_id');
                // $division = Division::where('id',$area)->value('is_medical');
                $division_head=Division::where('chief_employee_profile_id',$leave_applications->employee_profile_id)->count();
                $section_head=Section::where('supervisor_employee_profile_id',$leave_applications->employee_profile_id)->count();
                $department_head=Department::where('head_employee_profile_id',$leave_applications->employee_profile_id)->count();
                $division=true;
                $password_decrypted = Crypt::decryptString($user['password_encrypted']);
                $password = strip_tags($request->password);
                if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                    return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
                }
                else{
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
                                $action = 'Aprroved by Division Head';
                                $new_status='approved';
                                $message_action="Approved";
                            }
                            else if($status == 'applied'){
                                $action = 'Verified by HRMO';
                                if ($division_head > 0) {
                                    $new_status='for-approval-omcc-head';
                                    $message_action="verified";
                                }
                                else if($section_head > 0 || $department_head > 0)
                                {
                                    $new_status='for-approval-division-head';
                                    $message_action="verified";
                                }
                                else{
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


                            }


                            if($leave_applications){
                                DB::beginTransaction();
                                    $leave_application_log = new LeaveApplicationLog();
                                    $leave_application_log->action = $action;
                                    $leave_application_log->leave_application_id = $id;
                                    $leave_application_log->action_by_id = $user->id;
                                    $leave_application_log->date = date('Y-m-d');
                                    $leave_application_log->time = date('h-i-s');
                                    $leave_application_log->save();

                                    $leave_application = LeaveApplication::findOrFail($id);
                                    $leave_application->status = $new_status;
                                    $leave_application->update();

                                    if($new_status=="approved")
                                    {
                                        $leave_application_date_time=LeaveApplicationDateTime::where('leave_application_id',$id)->get();
                                        $total_days = 0;

                                        foreach ($leave_application_date_time as $leave_date_time) {
                                            $date_from = Carbon::parse($leave_date_time->date_from);
                                            $date_to = Carbon::parse($leave_date_time->date_to);
                                            $total_days += $date_to->diffInDays($date_from) + 1; // Add 1 to include both the start and end dates

                                        }

                                        $employee_leave_credits = new EmployeeLeaveCredit();
                                        $employee_leave_credits->employee_profile_id = $leave_applications->employee_profile_id;
                                        $employee_leave_credits->leave_application_id = $id;
                                        $employee_leave_credits->operation = "deduct";
                                        $employee_leave_credits->reason = "Leave";
                                        $employee_leave_credits->leave_credit = $total_days;
                                        $employee_leave_credits->date = date('Y-m-d');
                                        $employee_leave_credits->save();

                                    }
                                DB::commit();
                                $leave_applications =LeaveApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','dates','logs', 'requirements','employeeProfile.leaveCredits.leaveType'])
                                ->where('id',$leave_application->id)->get();
                                $leave_applications_result = $leave_applications->map(function ($leave_application) {
                                    $datesData = $leave_application->dates ? $leave_application->dates : collect();
                                    $logsData = $leave_application->logs ? $leave_application->logs : collect();
                                    $requirementsData = $leave_application->requirements ? $leave_application->requirements : collect();
                                    $add=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                                    ->where('operation', 'add')
                                    ->sum('credit_value');
                                    $deduct=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                                    ->where('operation', 'deduct')
                                    ->sum('credit_value');
                                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                                    $hr = Section::with('supervisor.personalInformation')->where('name','HR')->orWhere('name','Human Resource')->first();

                                    $chief_name=null;
                                    $chief_position=null;
                                    $chief_code=null;
                                    $head_name=null;
                                    $head_position=null;
                                    $head_code=null;
                                    $supervisor_name=null;
                                    $supervisor_position=null;
                                    $supervisor_code=null;
                                    $hr_name=null;
                                    $hr_position=null;
                                    $hr_code=null;
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
                                    if($hr)
                                    {

                                        if($hr && $hr->supervisor  && $hr->supervisor->personalInformation != null)
                                        {
                                            $hr_name = optional($hr->supervisor->personalInformation)->first_name . ' ' . optional($hr->supervisor->personalInformation)->last_name;
                                            $hr_position = $hr->supervisor->assignedArea->designation->name ?? null;
                                            $hr_code = $hr->supervisor->assignedArea->designation->code ?? null;
                                        }
                                    }

                                    $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                                    $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                                    $total_days=0;
                                    foreach($leave_application->dates as $date)
                                    {
                                        $startDate = Carbon::createFromFormat('Y-m-d', $date->date_from);
                                        $endDate = Carbon::createFromFormat('Y-m-d', $date->date_to);

                                        $numberOfDays = $startDate->diffInDays($endDate) + 1;
                                        $total_days += $numberOfDays;
                                    }
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
                                        'days_total' => $total_days ,
                                        'status' => $leave_application->status ,
                                        'remarks' => $leave_application->remarks ,
                                        'date' => $leave_application->created_at ,
                                        'with_pay' => $leave_application->with_pay ,
                                        'employee_id' => $leave_application->employee_profile_id,
                                        'employee_name' => "{$first_name} {$last_name}" ,
                                        'position_code' => $leave_application->employeeProfile->assignedArea->designation->code ?? null,
                                        'position_name' => $leave_application->employeeProfile->assignedArea->designation->name ?? null,
                                        'date_created' => $leave_application->date,
                                        'division_head' =>$chief_name,
                                        'division_head_position'=> $chief_position,
                                        'division_head_code'=> $chief_code,
                                        'department_head' =>$head_name,
                                        'department_head_position' =>$head_position,
                                        'department_head_code' =>$head_code,
                                        'section_head' =>$supervisor_name,
                                        'section_head_position' =>$supervisor_position,
                                        'section_head_code' =>$supervisor_code,
                                        'hr_head' =>$hr_name,
                                        'hr_head_position' =>$hr_position,
                                        'hr_head_code' =>$hr_code,
                                        'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                                        'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                                        'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                                        'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
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
                                                'leave_application_id' => $log->leave_application_id,
                                                'action_by' => "{$first_name} {$last_name}" ,
                                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                                'action' => $log->action,
                                                'date' => $formatted_date,
                                                'time' => $log->time,
                                                'process' => $action
                                            ];
                                        }),
                                        'requirements' => $requirementsData->map(function ($requirement) {
                                            return [
                                                'id' => $requirement->id,
                                                'leave_application_id' => $requirement->leave_application_id,
                                                'name' => $requirement->name,
                                                'file_name' => $requirement->file_name,
                                                'path' => $requirement->path,
                                                'size' => $requirement->size,
                                            ];
                                        }),
                                        'dates' => $datesData->map(function ($date) {
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
                                $singleArray = array_merge(...$leave_applications_result);
                                return response(['message' => 'Application has been sucessfully '.$message_action, 'data' => $singleArray], Response::HTTP_OK);
                            }
                }

            }
         catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }

    }
    public function declineLeaveApplication($id,Request $request)
    {
        try {
                $user=$request->user;
                $leave_applications = LeaveApplication::where('id','=', $id)
                                                            ->first();
                if($leave_applications)
                {
                    $password_decrypted = Crypt::decryptString($user['password_encrypted']);
                    $password = strip_tags($request->password);
                        if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                            return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
                        }
                        else{

                                DB::beginTransaction();
                                    $leave_application_log = new LeaveApplicationLog();
                                    $leave_application_log->action = 'declined';
                                    $leave_application_log->leave_application_id =$id;
                                    $leave_application_log->date = date('Y-m-d');
                                    $leave_application_log->time = date('h-i-s');
                                    $leave_application_log->action_by_id = $user->id;
                                    $leave_application_log->save();

                                    $leave_application = LeaveApplication::findOrFail($id);
                                    $leave_application->status = 'declined';
                                    $leave_application->decline_reason = $request->decline_reason;
                                    $leave_application->update();
                                DB::commit();

                                $leave_applications =LeaveApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','dates','logs', 'requirements','employeeProfile.leaveCredits.leaveType'])
                                ->where('id',$leave_application->id)->get();
                                $leave_applications_result = $leave_applications->map(function ($leave_application) {
                                    $datesData = $leave_application->dates ? $leave_application->dates : collect();
                                    $logsData = $leave_application->logs ? $leave_application->logs : collect();
                                    $requirementsData = $leave_application->requirements ? $leave_application->requirements : collect();
                                    $add=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                                    ->where('operation', 'add')
                                    ->sum('credit_value');
                                    $deduct=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                                    ->where('operation', 'deduct')
                                    ->sum('credit_value');
                                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                                    $hr = Section::with('supervisor.personalInformation')->where('name','HRMO')->first();

                                    $chief_name=null;
                                    $chief_position=null;
                                    $chief_code=null;
                                    $head_name=null;
                                    $head_position=null;
                                    $head_code=null;
                                    $supervisor_name=null;
                                    $supervisor_position=null;
                                    $supervisor_code=null;
                                    $hr_name=null;
                                    $hr_position=null;
                                    $hr_code=null;
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
                                    if($hr)
                                    {

                                        if($hr && $hr->supervisor  && $hr->supervisor->personalInformation != null)
                                        {
                                            $hr_name = optional($hr->supervisor->personalInformation)->first_name . ' ' . optional($hr->supervisor->personalInformation)->last_name;
                                            $hr_position = $hr->supervisor->assignedArea->designation->name ?? null;
                                            $hr_code = $hr->supervisor->assignedArea->designation->code ?? null;
                                        }
                                    }

                                    $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                                    $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                                    $total_days=0;
                                    foreach($leave_application->dates as $date)
                                    {
                                        $startDate = Carbon::createFromFormat('Y-m-d', $date->date_from);
                                        $endDate = Carbon::createFromFormat('Y-m-d', $date->date_to);

                                        $numberOfDays = $startDate->diffInDays($endDate) + 1;
                                        $total_days += $numberOfDays;
                                    }
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
                                        'days_total' => $total_days ,
                                        'status' => $leave_application->status ,
                                        'remarks' => $leave_application->remarks ,
                                        'date' => $leave_application->created_at ,
                                        'with_pay' => $leave_application->with_pay ,
                                        'employee_id' => $leave_application->employee_profile_id,
                                        'employee_name' => "{$first_name} {$last_name}" ,
                                        'position_code' => $leave_application->employeeProfile->assignedArea->designation->code ?? null,
                                        'position_name' => $leave_application->employeeProfile->assignedArea->designation->name ?? null,
                                        'date_created' => $leave_application->date,
                                        'division_head' =>$chief_name,
                                        'division_head_position'=> $chief_position,
                                        'division_head_code'=> $chief_code,
                                        'department_head' =>$head_name,
                                        'department_head_position' =>$head_position,
                                        'department_head_code' =>$head_code,
                                        'section_head' =>$supervisor_name,
                                        'section_head_position' =>$supervisor_position,
                                        'section_head_code' =>$supervisor_code,
                                        'hr_head' =>$hr_name,
                                        'hr_head_position' =>$hr_position,
                                        'hr_head_code' =>$hr_code,
                                        'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                                        'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                                        'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                                        'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
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
                                                'leave_application_id' => $log->leave_application_id,
                                                'action_by' => "{$first_name} {$last_name}" ,
                                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                                'action' => $log->action,
                                                'date' => $formatted_date,
                                                'time' => $log->time,
                                                'process' => $action
                                            ];
                                        }),
                                        'requirements' => $requirementsData->map(function ($requirement) {
                                            return [
                                                'id' => $requirement->id,
                                                'leave_application_id' => $requirement->leave_application_id,
                                                'name' => $requirement->name,
                                                'file_name' => $requirement->file_name,
                                                'path' => $requirement->path,
                                                'size' => $requirement->size,
                                            ];
                                        }),
                                        'dates' => $datesData->map(function ($date) {
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

                                $singleArray = array_merge(...$leave_applications_result);
                                return response(['message' => 'Application has been sucessfully declined', 'data' => $singleArray], Response::HTTP_OK);


                        }
                }
            } catch (\Exception $e) {
                DB::rollBack();
            return response()->json(['message' => $e->getMessage(),  'error'=>true]);
        }
    }

    public function cancelLeaveApplication($id,Request $request)
    {
        try {
                $user=$request->user;
                $leave_applications = LeaveApplication::where('id','=', $id)
                                                            ->first();
                if($leave_applications)
                {
                        // $user_id = Auth::user()->id;
                        // $user = EmployeeProfile::where('id','=',$user_id)->first();
                        // $user_password=$user->password;
                        // $password=$request->password;
                        // if($user_password==$password)
                        // {
                        //     if($user_id){
                            DB::beginTransaction();
                                $leave_application_log = new LeaveApplicationLog();
                                $leave_application_log->action = 'cancel';
                                $leave_application_log->leave_application_id = $id;
                                $leave_application_log->date = date('Y-m-d');
                                $leave_application_log->action_by_id = $user->id;
                                $leave_application_log->save();

                                $leave_application = LeaveApplication::findOrFail($id);
                                $leave_application->status = 'cancelled';
                                $leave_application->update();
                            DB::commit();

                                $leave_applications =LeaveApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','dates','logs', 'requirements','employeeProfile.leaveCredits.leaveType'])
                                ->where('id',$leave_application->id)->get();
                                $leave_applications_result = $leave_applications->map(function ($leave_application) {
                                    $datesData = $leave_application->dates ? $leave_application->dates : collect();
                                    $logsData = $leave_application->logs ? $leave_application->logs : collect();
                                    $requirementsData = $leave_application->requirements ? $leave_application->requirements : collect();
                                    $add=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                                    ->where('operation', 'add')
                                    ->sum('credit_value');
                                    $deduct=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                                    ->where('operation', 'deduct')
                                    ->sum('credit_value');
                                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                                    $hr = Section::with('supervisor.personalInformation')->where('name','HRMO')->first();

                                    $chief_name=null;
                                    $chief_position=null;
                                    $chief_code=null;
                                    $head_name=null;
                                    $head_position=null;
                                    $head_code=null;
                                    $supervisor_name=null;
                                    $supervisor_position=null;
                                    $supervisor_code=null;
                                    $hr_name=null;
                                    $hr_position=null;
                                    $hr_code=null;
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
                                    if($hr)
                                    {

                                        if($hr && $hr->supervisor  && $hr->supervisor->personalInformation != null)
                                        {
                                            $hr_name = optional($hr->supervisor->personalInformation)->first_name . ' ' . optional($hr->supervisor->personalInformation)->last_name;
                                            $hr_position = $hr->supervisor->assignedArea->designation->name ?? null;
                                            $hr_code = $hr->supervisor->assignedArea->designation->code ?? null;
                                        }
                                    }

                                    $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                                    $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                                    $total_days=0;
                                    foreach($leave_application->dates as $date)
                                    {
                                        $startDate = Carbon::createFromFormat('Y-m-d', $date->date_from);
                                        $endDate = Carbon::createFromFormat('Y-m-d', $date->date_to);

                                        $numberOfDays = $startDate->diffInDays($endDate) + 1;
                                        $total_days += $numberOfDays;
                                    }
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
                                        'days_total' => $total_days ,
                                        'status' => $leave_application->status ,
                                        'remarks' => $leave_application->remarks ,
                                        'date' => $leave_application->created_at ,
                                        'with_pay' => $leave_application->with_pay ,
                                        'employee_id' => $leave_application->employee_profile_id,
                                        'employee_name' => "{$first_name} {$last_name}" ,
                                        'position_code' => $leave_application->employeeProfile->assignedArea->designation->code ?? null,
                                        'position_name' => $leave_application->employeeProfile->assignedArea->designation->name ?? null,
                                        'date_created' => $leave_application->date,
                                        'division_head' =>$chief_name,
                                        'division_head_position'=> $chief_position,
                                        'division_head_code'=> $chief_code,
                                        'department_head' =>$head_name,
                                        'department_head_position' =>$head_position,
                                        'department_head_code' =>$head_code,
                                        'section_head' =>$supervisor_name,
                                        'section_head_position' =>$supervisor_position,
                                        'section_head_code' =>$supervisor_code,
                                        'hr_head' =>$hr_name,
                                        'hr_head_position' =>$hr_position,
                                        'hr_head_code' =>$hr_code,
                                        'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                                        'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                                        'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                                        'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
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
                                                'leave_application_id' => $log->leave_application_id,
                                                'action_by' => "{$first_name} {$last_name}" ,
                                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                                'action' => $log->action,
                                                'date' => $formatted_date,
                                                'time' => $log->time,
                                                'process' => $action
                                            ];
                                        }),
                                        'requirements' => $requirementsData->map(function ($requirement) {
                                            return [
                                                'id' => $requirement->id,
                                                'leave_application_id' => $requirement->leave_application_id,
                                                'name' => $requirement->name,
                                                'file_name' => $requirement->file_name,
                                                'path' => $requirement->path,
                                                'size' => $requirement->size,
                                            ];
                                        }),
                                        'dates' => $datesData->map(function ($date) {
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
                                $singleArray = array_merge(...$leave_applications_result);
                                return response(['message' => 'Application has been sucessfully cancelled', 'data' => $singleArray], Response::HTTP_OK);

                        //     }
                        //  }
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['message' => $e->getMessage(),  'error'=>true]);
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
                'requirements.*' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048'
            ]);
                $leave_type_id = $request->leave_type_id;
                $user=$request->user;
                $division = AssignArea::where('employee_profile_id',$user->id)->value('division_id');
                $employee_leave_credit=EmployeeLeaveCredit::where('employee_profile_id','=',$user->id)
                                                    ->where('leave_type_id','=',$leave_type_id)
                                                    ->get();
                $leavetype=LeaveType::where('id',$leave_type_id)->first();
                    if($leavetype->is_special == false)
                    {
                        if($employee_leave_credit)
                        {
                            $total_leave_credit = $employee_leave_credit->mapToGroups(function ($credit) {
                            return [$credit->operation => $credit->credit_value];
                            })->map(function ($operationCredits, $operation) {
                            return $operation === 'add' ? $operationCredits->sum() : -$operationCredits->sum();
                            })->sum();

                            $fromDates = $request->input('date_from');
                            $toDates = $request->input('date_to');
                            $total_days = 0;

                            if (count($fromDates) !== count($toDates)) {
                                return response()->json(['error' => 'Mismatched date to and date from '], 400);
                            }

                            for ($i = 0; $i < count($fromDates); $i++) {
                                $startDate = Carbon::createFromFormat('Y-m-d', $fromDates[$i]);
                                $endDate = Carbon::createFromFormat('Y-m-d', $toDates[$i]);

                                $numberOfDays = $startDate->diffInDays($endDate) + 1;
                                $total_days += $numberOfDays;
                            }

                            if($total_leave_credit >  $total_days)
                            {

                                        DB::beginTransaction();
                                        $leave_application = new Leaveapplication();
                                        $leave_application->leave_type_id = $leave_type_id;
                                        $leave_application->reference_number = $request->reference_number;
                                        $leave_application->country = $request->country;
                                        $leave_application->city = $request->city;
                                        $leave_application->patient_type = $request->patient_type;
                                        $leave_application->illness = $request->illness;
                                        $leave_application->reason = $request->reason;
                                        $leave_application->with_pay =  $request->has('with_pay');
                                        $leave_application->leave_credit_total = $total_days;
                                        $leave_application->status = "applied";
                                        $leave_application->date = date('Y-m-d');
                                        $leave_application->time =  date('H:i:s');
                                        $leave_application->employee_profile_id =$user->id;
                                        $leave_application->leave_type_id =$leave_type_id;
                                        $leave_application->save();
                                        $leave_application_id = $leave_application->id;

                                        // if($fromDates)
                                        // {
                                        //     for ($i = 0; $i < count($fromDates); $i++) {
                                        //         LeaveApplicationDateTime::create([
                                        //             'leave_application_id' => $leave_application->id,
                                        //             'date_to' => $fromDates[$i],
                                        //             'date_from' => $toDates[$i],

                                        //         ]);
                                        //     }
                                        // }

                                        foreach ($validatedData['date_from'] as $index => $dateFrom) {
                                            LeaveApplicationDateTime::create([
                                                'leave_application_id' => $leave_application->id,
                                                'date_from' => $dateFrom,
                                                'date_to' => $validatedData['date_to'][$index],
                                            ]);
                                        }

                                        $columnsString="";
                                        $name = $request->input('name');
                                        $leave_application_id = $leave_application->id;
                                        if ($request->hasFile('requirements')) {

                                                foreach ($request->file('requirements') as $key => $file) {
                                                    $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                                                    $extension = $file->getClientOriginalExtension();
                                                    $uniqueFileName = $fileName . '_' . time() . '.' . $extension;
                                                    $folderName = 'requirements';
                                                    Storage::makeDirectory('public/' . $folderName);
                                                    $file->storeAs('public/' . $folderName, $uniqueFileName);
                                                    $path = $folderName .'/'. $uniqueFileName;
                                                    $size = $file->getSize();
                                                    $name_array = $name[$key] ?? null;
                                                        LeaveApplicationRequirement::create([
                                                            'leave_application_id' => $leave_application->id,
                                                            'file_name' => $uniqueFileName,
                                                            'name' => $name_array,
                                                            'path' => $path,
                                                            'size' => $size,
                                                        ]);
                                            }

                                        }
                                        $process_name="Applied";
                                        $this->storeLeaveApplicationLog($leave_application_id,$process_name,$columnsString,$user->id);
                                        DB::commit();
                                        $leave_applications =LeaveApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','dates','logs', 'requirements','employeeProfile.leaveCredits.leaveType'])
                                        ->where('id',$leave_application->id)->get();
                                        $leave_applications_result = $leave_applications->map(function ($leave_application) {
                                            $datesData = $leave_application->dates ? $leave_application->dates : collect();
                                            $logsData = $leave_application->logs ? $leave_application->logs : collect();
                                            $requirementsData = $leave_application->requirements ? $leave_application->requirements : collect();
                                            $add=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                                            ->where('operation', 'add')
                                            ->sum('credit_value');
                                            $deduct=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                                            ->where('operation', 'deduct')
                                            ->sum('credit_value');
                                            $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                                            $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                                            $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                                            $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                                            $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                                            $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                                            $hr = Section::with('supervisor.personalInformation')->where('name','HR')->orWhere('name','Human Resource')->first();

                                            $chief_name=null;
                                            $chief_position=null;
                                            $chief_code=null;
                                            $head_name=null;
                                            $head_position=null;
                                            $head_code=null;
                                            $supervisor_name=null;
                                            $supervisor_position=null;
                                            $supervisor_code=null;
                                            $hr_name=null;
                                            $hr_position=null;
                                            $hr_code=null;
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
                                            if($hr)
                                            {

                                                if($hr && $hr->supervisor  && $hr->supervisor->personalInformation != null)
                                                {
                                                    $hr_name = optional($hr->supervisor->personalInformation)->first_name . ' ' . optional($hr->supervisor->personalInformation)->last_name;
                                                    $hr_position = $hr->supervisor->assignedArea->designation->name ?? null;
                                                    $hr_code = $hr->supervisor->assignedArea->designation->code ?? null;
                                                }
                                            }

                                            $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                                            $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                                            $total_days=0;
                                            foreach($leave_application->dates as $date)
                                                {
                                                    $startDate = Carbon::createFromFormat('Y-m-d', $date->date_from);
                                                    $endDate = Carbon::createFromFormat('Y-m-d', $date->date_to);

                                                    $numberOfDays = $startDate->diffInDays($endDate) + 1;
                                                    $total_days += $numberOfDays;
                                                }
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
                                                                    'days_total' => $total_days ,
                                                                    'status' => $leave_application->status ,
                                                                    'remarks' => $leave_application->remarks ,
                                                                    'date' => $leave_application->created_at ,
                                                                    'with_pay' => $leave_application->with_pay ,
                                                                    'employee_id' => $leave_application->employee_profile_id,
                                                                    'employee_name' => "{$first_name} {$last_name}" ,
                                                                    'position_code' => $leave_application->employeeProfile->assignedArea->designation->code ?? null,
                                                                    'position_name' => $leave_application->employeeProfile->assignedArea->designation->name ?? null,
                                                                    'date_created' => $leave_application->date,
                                                                    'division_head' =>$chief_name,
                                                                    'division_head_position'=> $chief_position,
                                                                    'division_head_code'=> $chief_code,
                                                                    'department_head' =>$head_name,
                                                                    'department_head_position' =>$head_position,
                                                                    'department_head_code' =>$head_code,
                                                                    'section_head' =>$supervisor_name,
                                                                    'section_head_position' =>$supervisor_position,
                                                                    'section_head_code' =>$supervisor_code,
                                                                    'hr_head' =>$hr_name,
                                                                    'hr_head_position' =>$hr_position,
                                                                    'hr_head_code' =>$hr_code,
                                                                    'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                                                                    'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                                                                    'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                                                                    'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
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
                                                                            'leave_application_id' => $log->leave_application_id,
                                                                            'action_by' => "{$first_name} {$last_name}" ,
                                                                            'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                                                            'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                                                            'action' => $log->action,
                                                                            'date' => $formatted_date,
                                                                            'time' => $log->time,
                                                                            'process' => $action
                                                                        ];
                                                                    }),
                                                                    'requirements' => $requirementsData->map(function ($requirement) {
                                                                        return [
                                                                            'id' => $requirement->id,
                                                                            'leave_application_id' => $requirement->leave_application_id,
                                                                            'name' => $requirement->name,
                                                                            'file_name' => $requirement->file_name,
                                                                            'path' => $requirement->path,
                                                                            'size' => $requirement->size,
                                                                        ];
                                                                    }),
                                                                    'dates' => $datesData->map(function ($date) {
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
                                                            $singleArray = array_merge(...$leave_applications_result);
                                                        return response()->json(['message' => 'Leave Application has been sucessfully saved','data' => $singleArray ], Response::HTTP_OK);
                            }

                            else
                            {
                                return response()->json(['message' => 'Insufficient Leave Credit Value'], 300);
                            }
                        }

                    }
                    else
                    {


                        // $fromDates = $request->input('date_from');
                        // $toDates = $request->input('date_to');
                        DB::beginTransaction();
                        $total_days = 0;
                        $leave_application = new Leaveapplication();
                        $leave_application->leave_type_id = $leave_type_id;
                        $leave_application->reference_number = $request->reference_number;
                        $leave_application->country = $request->country;
                        $leave_application->city = $request->city;
                        $leave_application->patient_type = $request->patient_type;
                        $leave_application->illness = $request->illness;
                        $leave_application->reason = $request->reason;
                        $leave_application->with_pay =  $request->has('with_pay');
                        $leave_application->status = "applied";
                        $leave_application->date = date('Y-m-d');
                        $leave_application->time =  date('H:i:s');
                        $leave_application->employee_profile_id =$user->id;
                        $leave_application->leave_type_id =$leave_type_id;
                        $leave_application->save();
                        $leave_application_id = $leave_application->id;

                        // if($fromDates)
                        // {
                        //     for ($i = 0; $i < count($fromDates); $i++) {
                        //         LeaveApplicationDateTime::create([
                        //             'leave_application_id' => $leave_application->id,
                        //             'date_to' => $fromDates[$i],
                        //             'date_from' => $toDates[$i],

                        //         ]);
                        //     }
                        // }

                        foreach ($validatedData['date_from'] as $index => $dateFrom) {
                            LeaveApplicationDateTime::create([
                                'leave_application_id' => $leave_application->id,
                                'date_from' => $dateFrom,
                                'date_to' => $validatedData['date_to'][$index],
                            ]);
                        }
                        $columnsString="";
                        $name = $request->input('name');
                        $leave_application_id = $leave_application->id;
                        if ($request->hasFile('requirements')) {
                            foreach ($request->file('requirements') as $key => $file) {
                                $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                                $extension = $file->getClientOriginalExtension();
                                $uniqueFileName = $fileName . '_' . time() . '.' . $extension;
                                $folderName = 'requirements';
                                Storage::makeDirectory('public/' . $folderName);
                                $file->storeAs('public/' . $folderName, $uniqueFileName);
                                $path = $folderName .'/'. $uniqueFileName;
                                $size = $request->file('requirements')->getSize();
                                $name_array = $name[$key] ?? null;
                                    LeaveApplicationRequirement::create([
                                        'leave_application_id' => $leave_application->id,
                                        'file_name' => $fileName,
                                        'name' => $name_array,
                                        'path' => $path,
                                        'size' => $size,
                                    ]);
                        }
                        }
                        $process_name="Applied";
                        $this->storeLeaveApplicationLog($leave_application_id,$process_name,$columnsString,$user->id);
                        DB::commit();
                        $leave_applications =LeaveApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','dates','logs', 'requirements','employeeProfile.leaveCredits.leaveType'])
                        ->where('id',$leave_application->id)->get();
                        $leave_applications_result = $leave_applications->map(function ($leave_application) {
                            $datesData = $leave_application->dates ? $leave_application->dates : collect();
                            $logsData = $leave_application->logs ? $leave_application->logs : collect();
                            $requirementsData = $leave_application->requirements ? $leave_application->requirements : collect();
                            $add=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                            ->where('operation', 'add')
                            ->sum('credit_value');
                            $deduct=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                            ->where('operation', 'deduct')
                            ->sum('credit_value');
                            $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                            $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                            $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                            $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                            $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                            $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                            $hr = Section::with('supervisor.personalInformation')->where('name','HRMO')->first();

                            $chief_name=null;
                            $chief_position=null;
                            $chief_code=null;
                            $head_name=null;
                            $head_position=null;
                            $head_code=null;
                            $supervisor_name=null;
                            $supervisor_position=null;
                            $supervisor_code=null;
                            $hr_name=null;
                            $hr_position=null;
                            $hr_code=null;
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
                            if($hr)
                            {

                                if($hr && $hr->supervisor  && $hr->supervisor->personalInformation != null)
                                {
                                    $hr_name = optional($hr->supervisor->personalInformation)->first_name . ' ' . optional($hr->supervisor->personalInformation)->last_name;
                                    $hr_position = $hr->supervisor->assignedArea->designation->name ?? null;
                                    $hr_code = $hr->supervisor->assignedArea->designation->code ?? null;
                                }
                            }

                            $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                            $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                            $total_days=0;
                            foreach($leave_application->dates as $date)
                                {
                                    $startDate = Carbon::createFromFormat('Y-m-d', $date->date_from);
                                    $endDate = Carbon::createFromFormat('Y-m-d', $date->date_to);

                                    $numberOfDays = $startDate->diffInDays($endDate) + 1;
                                    $total_days += $numberOfDays;
                                }
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
                                                    'days_total' => $total_days ,
                                                    'status' => $leave_application->status ,
                                                    'remarks' => $leave_application->remarks ,
                                                    'date' => $leave_application->created_at ,
                                                    'with_pay' => $leave_application->with_pay ,
                                                    'employee_id' => $leave_application->employee_profile_id,
                                                    'employee_name' => "{$first_name} {$last_name}" ,
                                                    'position_code' => $leave_application->employeeProfile->assignedArea->designation->code ?? null,
                                                    'position_name' => $leave_application->employeeProfile->assignedArea->designation->name ?? null,
                                                    'date_created' => $leave_application->date,
                                                    'division_head' =>$chief_name,
                                                    'division_head_position'=> $chief_position,
                                                    'division_head_code'=> $chief_code,
                                                    'department_head' =>$head_name,
                                                    'department_head_position' =>$head_position,
                                                    'department_head_code' =>$head_code,
                                                    'section_head' =>$supervisor_name,
                                                    'section_head_position' =>$supervisor_position,
                                                    'section_head_code' =>$supervisor_code,
                                                    'hr_head' =>$hr_name,
                                                    'hr_head_position' =>$hr_position,
                                                    'hr_head_code' =>$hr_code,
                                                    'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                                                    'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                                                    'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                                                    'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
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
                                                            'leave_application_id' => $log->leave_application_id,
                                                            'action_by' => "{$first_name} {$last_name}" ,
                                                            'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                                            'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                                            'action' => $log->action,
                                                            'date' => $formatted_date,
                                                            'time' => $log->time,
                                                            'process' => $action
                                                        ];
                                                    }),
                                                    'requirements' => $requirementsData->map(function ($requirement) {
                                                        return [
                                                            'id' => $requirement->id,
                                                            'leave_application_id' => $requirement->leave_application_id,
                                                            'name' => $requirement->name,
                                                            'file_name' => $requirement->file_name,
                                                            'path' => $requirement->path,
                                                            'size' => $requirement->size,
                                                        ];
                                                    }),
                                                    'dates' => $datesData->map(function ($date) {
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
                                            $singleArray = array_merge(...$leave_applications_result);
                                        return response()->json(['message' => 'Leave Application has been sucessfully saved','data' => $singleArray ], Response::HTTP_OK);
                    }

        }catch(\Throwable $th){
            DB::rollBack();
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

    public function storeLeaveApplicationLog($leave_application_id,$process_name,$changedfields,$user_id)
    {
        try {
            $data = [
                'leave_application_id' => $leave_application_id,
                'action_by_id' => $user_id,
                'action' => $process_name,
                'date' => date('Y-m-d'),
                'time' => date('H:i:s'),
                'fields' => $changedfields
            ];

            $leave_application_log = LeaveApplicationLog::create($data);
            return $leave_application_log;
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
    }

    public function printLeaveForm($id)
    {
        try {
                    $leave_applications = LeaveApplication::where('id','=', $id)
                    ->first();
                if($leave_applications)
                {
                                $leave_applications =LeaveApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','dates','logs', 'requirements','employeeProfile.leaveCredits.leaveType'])
                                ->where('id',$leave_applications->id)->get();
                                $leave_applications_result = $leave_applications->map(function ($leave_application) {
                                    $datesData = $leave_application->dates ? $leave_application->dates : collect();
                                    $logsData = $leave_application->logs ? $leave_application->logs : collect();
                                    $requirementsData = $leave_application->requirements ? $leave_application->requirements : collect();
                                    $add=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                                    ->where('operation', 'add')
                                    ->sum('credit_value');
                                    $deduct=EmployeeLeaveCredit::where('employee_profile_id',$leave_application->employee_profile_id)->where('leave_type_id',$leave_application->leave_type_id)
                                    ->where('operation', 'deduct')
                                    ->sum('credit_value');
                                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                                    $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
                                    $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
                                    $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
                                    $hr = Section::with('supervisor.personalInformation')->where('name','HR')->orWhere('name','Human Resource')->first();

                                    $chief_name=null;
                                    $chief_position=null;
                                    $chief_code=null;
                                    $head_name=null;
                                    $head_position=null;
                                    $head_code=null;
                                    $supervisor_name=null;
                                    $supervisor_position=null;
                                    $supervisor_code=null;
                                    $hr_name=null;
                                    $hr_position=null;
                                    $hr_code=null;
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
                                    if($hr)
                                    {

                                        if($hr && $hr->supervisor  && $hr->supervisor->personalInformation != null)
                                        {
                                            $hr_name = optional($hr->supervisor->personalInformation)->first_name . ' ' . optional($hr->supervisor->personalInformation)->last_name;
                                            $hr_position = $hr->supervisor->assignedArea->designation->name ?? null;
                                            $hr_code = $hr->supervisor->assignedArea->designation->code ?? null;
                                        }
                                    }

                                    $first_name = optional($leave_application->employeeProfile->personalInformation)->first_name ?? null;
                                    $last_name = optional($leave_application->employeeProfile->personalInformation)->last_name ?? null;
                                    $total_days=0;
                                    foreach($leave_application->dates as $date)
                                    {
                                        $startDate = Carbon::createFromFormat('Y-m-d', $date->date_from);
                                        $endDate = Carbon::createFromFormat('Y-m-d', $date->date_to);

                                        $numberOfDays = $startDate->diffInDays($endDate) + 1;
                                        $total_days += $numberOfDays;
                                    }
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
                                        'days_total' => $total_days ,
                                        'status' => $leave_application->status ,
                                        'remarks' => $leave_application->remarks ,
                                        'date' => $leave_application->date ,
                                        'with_pay' => $leave_application->with_pay ,
                                        'employee_id' => $leave_application->employee_profile_id,
                                        'employee_name' => "{$first_name} {$last_name}" ,
                                        'position_code' => $leave_application->employeeProfile->assignedArea->designation->code ?? null,
                                        'position_name' => $leave_application->employeeProfile->assignedArea->designation->name ?? null,
                                        'date_created' => $leave_application->date,
                                        'division_head' =>$chief_name,
                                        'division_head_position'=> $chief_position,
                                        'division_head_code'=> $chief_code,
                                        'department_head' =>$head_name,
                                        'department_head_position' =>$head_position,
                                        'department_head_code' =>$head_code,
                                        'section_head' =>$supervisor_name,
                                        'section_head_position' =>$supervisor_position,
                                        'section_head_code' =>$supervisor_code,
                                        'hr_head' =>$hr_name,
                                        'hr_head_position' =>$hr_position,
                                        'hr_head_code' =>$hr_code,
                                        'division_name' => $leave_application->employeeProfile->assignedArea->division->name ?? null,
                                        'department_name' => $leave_application->employeeProfile->assignedArea->department->name ?? null,
                                        'section_name' => $leave_application->employeeProfile->assignedArea->section->name ?? null,
                                        'unit_name' => $leave_application->employeeProfile->assignedArea->unit->name ?? null,
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
                                                'leave_application_id' => $log->leave_application_id,
                                                'action_by' => "{$first_name} {$last_name}" ,
                                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                                'action' => $log->action,
                                                'date' => $formatted_date,
                                                'time' => $log->time,
                                                'process' => $action
                                            ];
                                        }),
                                        'requirements' => $requirementsData->map(function ($requirement) {
                                            return [
                                                'id' => $requirement->id,
                                                'leave_application_id' => $requirement->leave_application_id,
                                                'name' => $requirement->name,
                                                'file_name' => $requirement->file_name,
                                                'path' => $requirement->path,
                                                'size' => $requirement->size,
                                            ];
                                        }),
                                        'dates' => $datesData->map(function ($date) {
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
                                $singleArray = array_merge(...$leave_applications_result);
                             return view('leave_from.leave_application_form', $singleArray);
                }
            } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),  'error'=>true]);
        }
    }


}
