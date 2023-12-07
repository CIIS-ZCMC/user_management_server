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
use Illuminate\Support\Facades\Storage;
use App\Services\FileService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
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
            else if($section)
            {
                $section = Section::with('supervisor.personalInformation')->find($section);
                $head_name = optional($section->supervisor->personalInformation)->first_name ?? null . '' . optional($section->head->personalInformation)->last_name ?? null;
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
    public function getUserLeaveCreditsLogs($id)
    {
        try{

            $leaveCredits = EmployeeLeaveCredit::with('leaveType')
            ->where('employee_profile_id', $id)
            ->get();
            $formattedLeaveCredits = [];

            foreach ($leaveCredits as $credit) {
                $leaveTypeName = $credit->leaveType->name;
                if($credit->reason == 'Undertime')
                {
                    $name="Undertime_Total";
                    $total=$credit->undertime_total;
                }
                else if($credit->reason == 'Absent')
                {
                    $name="Absent_total";
                    $total=$credit->absent_total;
                }
                else if($credit->reason == 'Monthly Leave Credits')
                {
                    $name="Working_hours_total";
                    $total=$credit->absent_total;
                }
                else{

                }
                $formattedLeaveCredits[] = [
                    'leave_type_name' => $leaveTypeName,
                    'credit_total' => $credit->credit_value,
                    'operation' => $credit->operation,
                    'reason' => $credit->reason,
                    'date' => $credit->date,
                    $name=> $total,


                ];
            }

            // Now $formattedLeaveCredits contains the formatted data
            return $formattedLeaveCredits;


             return response()->json(['data' => $leaveCredits], Response::HTTP_OK);
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    public function getUserLeaveApplication($id)
    {
        try{
        $leave_applications = LeaveApplication::with(['employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
        ->where('employee_profile_id', $id)
        ->get();
        $leave_applications_result = $leave_applications->map(function ($leave_application) {
            $division = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('division_id');
            $department = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('department_id');
            $section = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');
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

        $leaveCredits = EmployeeLeaveCredit::with('leaveType')
        ->where('employee_profile_id', $id)
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
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    public function getLeaveApplications($id,$status,Request $request)
    {

        $leave_applications = [];
        $division = AssignArea::where('employee_profile_id',$id)->value('division_id');
        if($status == 'applied'){
            $section = AssignArea::where('employee_profile_id',$id)->value('section_id');
            $hr_head_id = Section::where('id', $section)->value('supervisor_employee_profile_id');
            if($hr_head_id === $id) {
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
                if($divisionHeadId === $id) {
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
            $department = AssignArea::where('employee_profile_id',$id)->value('department_id');
            $departmentHeadId = Department::where('id', $department)->value('head_employee_profile_id');
            $training_officer_id = Department::where('id', $department)->value('training_officer_employee_profile_id');
            if($departmentHeadId == $id || $training_officer_id === $id) {
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
            $section = AssignArea::where('employee_profile_id',$id)->value('section_id');
            $sectionHeadId = Section::where('id', $section)->value('supervisor_employee_profile_id');
            if($sectionHeadId === $id) {

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
            ->whereHas('logs', function ($query) use ($id) {
                $query->where('action_by_id', $id);
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

    public function updateLeaveApplicationStatus ($id,$status,Request $request)
    {
        try {
                // $employee_id = $request->employee_id;
                // $user_id = Auth::user()->id;
                // $user = EmployeeProfile::where('id','=',$user_id)->first();
                // $division = AssignArea::where('employee_profile_id',$employee_id)->value('is_medical');
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

                            $leave_applications = LeaveApplication::where('id','=', $id)
                                                                    ->first();
                            if($leave_applications){
                                $leave_application_log = new LeaveApplicationLog();
                                $leave_application_log->action = $action;
                                $leave_application_log->leave_application_id = $id;
                                $leave_application_log->action_by_id = '1';
                                $leave_application_log->date = date('Y-m-d');
                                $leave_application_log->time = date('h-i-s');
                                $leave_application_log->save();

                                $leave_application = LeaveApplication::findOrFail($id);
                                $leave_application->status = $new_status;
                                $leave_application->update();

                                if($new_status=="approved")
                                {
                                    $leave_application_date_time=LeaveApplicationDateTime::findOrFail($id);
                                    $total_days = 0;

                                    foreach ($leave_application_date_time as $leave_date_time) {
                                        $date_from = Carbon::parse($leave_date_time->date_from);
                                        $date_to = Carbon::parse($leave_date_time->date_to);
                                        $total_days += $date_to->diffInDays($date_from) + 1; // Add 1 to include both the start and end dates

                                    }

                                    $employee_leave_credits = new EmployeeLeaveCredit();
                                    $employee_leave_credits->employee_profile_id = '1';
                                    $employee_leave_credits->leave_application_id = $id;
                                    $employee_leave_credits->operation = "deduct";
                                    $employee_leave_credits->operation = "Leave";
                                    $employee_leave_credits->leave_credit = $total_days;
                                    $employee_leave_credits->date = date('Y-m-d');;
                                    $employee_leave_credits->save();

                                }
                                return response(['message' => 'Application has been sucessfully '.$message_action, 'data' => $leave_application], Response::HTTP_CREATED);
                            // }
                }
            }
         catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }

    }


    public function declineLeaveApplication($id,Request $request)
    {
        try {
                    // $leave_application_id = $request->leave_application_id;

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
                            // if($user_id){
                                $leave_application_log = new LeaveApplicationLog();
                                $leave_application_log->action = 'declined';
                                $leave_application_log->leave_application_id =$id;
                                $leave_application_log->date = date('Y-m-d');
                                $leave_application_log->time = date('h-i-s');
                                $leave_application_log->action_by_id = '1';
                                $leave_application_log->save();

                                $leave_application = LeaveApplication::findOrFail($id);
                                $leave_application->status = 'declined';
                                $leave_application->update();
                                return response(['message' => 'Application has been sucessfully declined', 'data' => $leave_application], Response::HTTP_CREATED);

                            // }
                        //  }
                }
            } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),  'error'=>true]);
        }
    }

    public function cancelLeaveApplication($id,Request $request)
    {
        try {
                    // $leave_application_id = $request->leave_application_id;
                    $leave_application_id = '1';
                    $leave_applications = LeaveApplication::where('id','=', $leave_application_id)
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
                                $leave_application_log = new LeaveApplicationLog();
                                $leave_application_log->action = 'cancel';
                                $leave_application_log->leave_application_id = $leave_application_id;
                                $leave_application_log->date = date('Y-m-d');
                                $leave_application_log->action_by_id = '1';
                                $leave_application_log->save();

                                $leave_application = LeaveApplication::findOrFail($leave_application_id);
                                $leave_application->status = 'cancelled';
                                $leave_application->update();
                                return response(['message' => 'Application has been sucessfully cancelled', 'data' => $leave_application], Response::HTTP_CREATED);

                        //     }
                        //  }
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
            // $user_id = Auth::user()->id;
            $user = EmployeeProfile::where('id','=','1')->first();
            // $user_status = $user->status;
            $division = AssignArea::where('employee_profile_id','1')->value('division_id');

            // if($user_status == 'Permanent')
            // {
                $employee_leave_credit=EmployeeLeaveCredit::where('employee_profile_id','=','1')
                                                    ->where('leave_type_id','=','54')
                                                    ->get();
                if($employee_leave_credit)
                {
                    $total_leave_credit = $employee_leave_credit->mapToGroups(function ($credit) {
                    return [$credit->operation => $credit->credit_value];
                    })->map(function ($operationCredits, $operation) {
                    return $operation === 'add' ? $operationCredits->sum() : -$operationCredits->sum();
                    })->sum();

                    $fromDates = $request->input('date_from');
                    $toDates = $request->input('date_to');
                    $fromTime = $request->input('time_from');
                    $toTime = $request->input('time_to');
                    $total_days = 0;

                    if (count($fromDates) !== count($toDates) || count($fromTime) !== count($toTime)) {
                        return response()->json(['error' => 'Mismatched date and time'], 400);
                    }

                    for ($i = 0; $i < count($fromDates); $i++) {
                        $startDate = Carbon::createFromFormat('Y-m-d', $fromDates[$i]);
                        $endDate = Carbon::createFromFormat('Y-m-d', $toDates[$i]);

                        $numberOfDays = $startDate->diffInDays($endDate) + 1;
                        $total_days += $numberOfDays;


                    }
                        if($total_leave_credit >  $total_days)
                        {

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
                                $leave_application->employee_profile_id ="1";
                                $leave_application->leave_type_id ="54";
                                $leave_application->save();
                                $leave_application_id = $leave_application->id;

                                    for ($i = 0; $i < count($fromDates); $i++) {
                                        LeaveApplicationDateTime::create([
                                            'leave_application_id' => $leave_application->id,
                                            'date_to' => $fromDates[$i],
                                            'date_from' => $toDates[$i],
                                            'time_to' => $toTime[$i],
                                            'time_from' => $fromTime[$i],
                                        ]);
                                    }


                                $columnsString="";
                                $name = $request->input('name');
                                $leave_application_id = $leave_application->id;
                                if ($request->hasFile('requirements')) {
                                    $requirements = $request->file('requirements');
                                    if($requirements){

                                        foreach ($request->file('requirements') as $file) {
                                            $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                                            $extension = $file->getClientOriginalExtension();
                                            $uniqueFileName = $fileName . '_' . time() . '.' . $extension;
                                            $folderName = 'requirements';
                                            Storage::makeDirectory('public/' . $folderName);
                                            $path = $file->storeAs('public/' . $folderName, $uniqueFileName);
                                            for ($i = 0; $i < count($fromDates); $i++) {
                                                LeaveApplicationRequirement::create([
                                                    'leave_application_id' => $leave_application->id,
                                                    'file_name' => $fileName,
                                                    'name' => $name[$i],
                                                    'path' => $path,
                                                ]);
                                            }
                                        }

                                    }
                                }
                                $process_name="Applied";
                                $this->storeLeaveApplicationLog($leave_application_id,$process_name,$columnsString);
                                return response()->json(['data' => 'Success'], Response::HTTP_OK);
                        }
                        else
                        {
                            return response()->json(['message' => 'Insufficient Leave Credit Value'], Response::HTTP_OK);
                        }



                }


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
    public function storeLeaveApplicationLog($leave_application_id,$process_name,$changedfields)
    {
        try {
            $user_id="1";

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





}
