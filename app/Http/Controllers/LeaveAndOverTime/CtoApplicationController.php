<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Models\CtoApplication;
use App\Http\Controllers\Controller;
use App\Models\AssignArea;
use App\Models\CtoApplicationDate;
use App\Models\CtoApplicationLog;
use App\Models\Department;
use App\Models\Division;
use App\Models\EmployeeOvertimeCredit;
use App\Models\OvtApplicationDatetime;
use App\Models\Section;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
class CtoApplicationController extends Controller
{
    public function index()
    {
        try{
            $cto_applications=[];
            $cto_applications =CtoApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','logs','dates'])->get();
            if($cto_applications->isNotEmpty())
            {
                $cto_applications_result = $cto_applications->map(function ($cto_application) {
                    $datesData = $cto_application->dates ? $cto_application->dates : collect();
                    $logsData = $cto_application->logs ? $cto_application->logs : collect();
                    $division = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('section_id');
                            $chief_name=null;
                            $chief_position=null;
                            $head_name=null;
                            $head_position=null;
                            $supervisor_name=null;
                            $supervisor_position=null;
                            if($division) {
                                $division_name = Division::with('chief.personalInformation')->find($division);

                                if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                {
                                    $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;

                                    $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                }
                            }
                            if($department)
                            {
                                $department_name = Department::with('head.personalInformation')->find($department);
                                if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                                {

                                $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                }
                            }
                            if($section)
                            {
                                $section_name = Section::with('supervisor.personalInformation')->find($section);
                                if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                                {
                                $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                }
                            }
                            $overtimeRecord = CtoApplicationDate::where('cto_application_id',$cto_application->id);
                            if($overtimeRecord)
                            {
                                $dates = $overtimeRecord->pluck('date')->toArray();
                                $total_days = count(array_unique($dates));

                            }
                    $first_name = optional($cto_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($cto_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $cto_application->id,
                        'remarks' => $cto_application->remarks,
                        'purpose' => $cto_application->purpose,
                        'status' => $cto_application->status,
                        'total_days'=> $total_days,
                        'employee_id' => $cto_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'position_code' => $cto_application->employeeProfile->assignedArea->designation->code ?? null,
                        'position_name' => $cto_application->employeeProfile->assignedArea->designation->name ?? null,
                        'date_created' => $cto_application->created_at,
                        'division_head' =>$chief_name,
                        'division_head_position'=> $chief_position,
                        'department_head' =>$head_name,
                        'department_head_position' =>$head_position,
                        'section_head' =>$supervisor_name,
                        'section_head_position' =>$supervisor_position,
                        'division_name' => $cto_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $cto_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $cto_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $cto_application->employeeProfile->assignedArea->unit->name ?? null,
                        'date' => $cto_application->date,
                        'time' => $cto_application->time,
                        'logs' => $logsData ->map(function ($log) {
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
                                'cto_application_id ' => $log->cto_application_id ,
                                'action_by' => "{$first_name} {$last_name}" ,
                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),
                        'dates' => $datesData->map(function ($date) {
                            return [
                                        'id' => $date->id,
                                        'cto_application_id' =>$date->cto_application_id,
                                        'time_from' => $date->time_from,
                                        'time_to' => $date->time_to,
                                        'date' => $date->date,

                            ];
                        }),

                    ];
                });
                return response()->json(['data' => $cto_applications_result], Response::HTTP_OK);
            }
            else {

                return response()->json(['message' => 'No records available'], Response::HTTP_OK);
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
            // $area = AssignArea::where('employee_profile_id',$employee_id)->value('division_id');
            // $division = Division::where('id',$area)->value('is_medical');
            $division=true;
            $cto_application = new CtoApplication();
            $cto_application->employee_profile_id = '1';
            $cto_application->remarks = $request->remarks;
            // $cto_application->purpose = $request->purpose;
            if($division === true)
            {
                $status='for-approval-department-head';
            }
            else
            {
                $status='for-approval-section-head';
            }
            $cto_application->status = $status;
            $cto_application->date = date('Y-m-d');
            $cto_application->time =  date('H:i:s');
            $cto_application->save();

            $cto_id=$cto_application->id;
            $time_from = $request->input('time_from');
            $time_to = $request->input('time_to');
            $date = $request->input('dates');
            $purpose = $request->input('purpose');
            for ($i = 0; $i < count($date); $i++) {
               $date_application = CtoApplicationDate::create([
                    'cto_application_id' => $cto_id,
                    'time_from' => $time_from[$i],
                    'time_to' => $time_to[$i],
                    'date' => $date[$i],
                    'purpose' => $purpose[$i],
                ]);
            }
            $cto_id=$cto_application->id;
            $columnsString="";
            $process_name="Applied";

            $this->storeCTOApplicationLog($cto_id,$process_name,$columnsString);
            $cto_applications = CtoApplication::with(['employeeProfile.personalInformation','dates','logs'])
            ->where('id',$cto_application->id)->get();
            $cto_applications_result = $cto_applications->map(function ($cto_application) {
                $datesData = $cto_application->dates ? $cto_application->dates : collect();
                $logsData = $cto_application->logs ? $cto_application->logs : collect();
                $division = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('division_id');
                $department = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('department_id');
                $section = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('section_id');
                        $chief_name=null;
                        $chief_position=null;
                        $head_name=null;
                        $head_position=null;
                        $supervisor_name=null;
                        $supervisor_position=null;
                        if($division) {
                            $division_name = Division::with('chief.personalInformation')->find($division);

                            if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                            {
                                $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;

                                $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                            }
                        }
                        if($department)
                        {
                            $department_name = Department::with('head.personalInformation')->find($department);
                            if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                            {

                            $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                            $head_position = $department_name->head->assignedArea->designation->name ?? null;
                            }
                        }
                        if($section)
                        {
                            $section_name = Section::with('supervisor.personalInformation')->find($section);
                            if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                            {
                            $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                            $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                            }
                        }
                        $overtimeRecord = CtoApplicationDate::where('cto_application_id',$cto_application->id);
                        if($overtimeRecord)
                        {
                            $dates = $overtimeRecord->pluck('date')->toArray();
                            $total_days = count(array_unique($dates));

                        }
                        $first_name = optional($cto_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($cto_application->employeeProfile->personalInformation)->last_name ?? null;
                        return [
                            'id' => $cto_application->id,
                            'remarks' => $cto_application->remarks,
                            'purpose' => $cto_application->purpose,
                            'status' => $cto_application->status,
                            'total_days'=>$total_days,
                            'employee_id' => $cto_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}" ,
                            'position_code' => $cto_application->employeeProfile->assignedArea->designation->code ?? null,
                            'position_name' => $cto_application->employeeProfile->assignedArea->designation->name ?? null,
                            'date_created' => $cto_application->created_at,
                            'division_head' =>$chief_name,
                            'division_head_position'=> $chief_position,
                            'department_head' =>$head_name,
                            'department_head_position' =>$head_position,
                            'section_head' =>$supervisor_name,
                            'section_head_position' =>$supervisor_position,
                            'division_name' => $cto_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $cto_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $cto_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $cto_application->employeeProfile->assignedArea->unit->name ?? null,
                            'date' => $cto_application->date,
                            'time' => $cto_application->time,
                            'logs' => $logsData ->map(function ($log) {
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
                                    'cto_application_id ' => $log->cto_application_id ,
                                    'action_by' => "{$first_name} {$last_name}" ,
                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),
                            'dates' => $datesData->map(function ($date) {
                                return [
                                            'id' => $date->id,
                                            'cto_application_id' =>$date->cto_application_id,
                                            'time_from' => $date->time_from,
                                            'time_to' => $date->time_to,
                                            'date' => $date->date,

                                ];
                            }),

                        ];
                        });
                $singleArray = array_merge(...$cto_applications_result);

            return response()->json(['message' => 'Compensatory Time Off Application has been sucessfully saved','data' => $singleArray ], Response::HTTP_OK);
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function storeCTOApplicationLog($cto_id,$process_name,$changedFields)
    {
        try {
            $user_id="1";

            $data = [
                'cto_application_id' => $cto_id,
                'action_by_id' => $user_id,
                'action' => $process_name,
                'date' => date('Y-m-d'),
                'time' => date('H:i:s'),
                // 'fields' => $changedFields
            ];

            $cto_log = CtoApplicationLog::create($data);

            return $cto_log;
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
    }

    public function declineCtoApplication($id,Request $request)
    {
        try {

                $cto_applications = CtoApplication::where('id','=', $id)
                                                            ->first();
                if($cto_applications)
                {
                        // $user_id = Auth::user()->id;
                        // $user = EmployeeProfile::where('id','=',$user_id)->first();
                        // $user_password=$user->password;
                        // $password=$request->password;
                        // if($user_password==$password)
                        // {
                        //     if($user_id){
                                $cto_application_log = new CtoApplicationLog();
                                $cto_application_log->action = 'declined';
                                $cto_application_log->cto_application_id  = $id;
                                $cto_application_log->date = date('Y-m-d');
                                $cto_application_log->time =  date('H:i:s');
                                $cto_application_log->action_by_id = '1';
                                $cto_application_log->save();

                                $cto_application = CtoApplication::findOrFail($id);
                                $cto_application->status = 'declined';
                                $cto_application->decline_reason = $request->decline_reason;
                                $cto_application->update();

                                $cto_applications = CtoApplication::with(['employeeProfile.personalInformation','dates','logs'])
                                ->where('id',$cto_application->id)->get();
                                $cto_applications_result = $cto_applications->map(function ($cto_application) {
                                    $datesData = $cto_application->dates ? $cto_application->dates : collect();
                                    $logsData = $cto_application->logs ? $cto_application->logs : collect();
                                    $division = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('division_id');
                                    $department = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('department_id');
                                    $section = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('section_id');
                                            $chief_name=null;
                                            $chief_position=null;
                                            $head_name=null;
                                            $head_position=null;
                                            $supervisor_name=null;
                                            $supervisor_position=null;
                                            if($division) {
                                                $division_name = Division::with('chief.personalInformation')->find($division);

                                                if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                                {
                                                    $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;

                                                    $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                                }
                                            }
                                            if($department)
                                            {
                                                $department_name = Department::with('head.personalInformation')->find($department);
                                                if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                                                {

                                                $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                                $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                                }
                                            }
                                            if($section)
                                            {
                                                $section_name = Section::with('supervisor.personalInformation')->find($section);
                                                if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                                                {
                                                $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                                $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                                }
                                            }
                                            $overtimeRecord = CtoApplicationDate::where('cto_application_id',$cto_application->id);
                                            if($overtimeRecord)
                                            {
                                                $dates = $overtimeRecord->pluck('date')->toArray();
                                                $total_days = count(array_unique($dates));

                                            }
                                            $first_name = optional($cto_application->employeeProfile->personalInformation)->first_name ?? null;
                                            $last_name = optional($cto_application->employeeProfile->personalInformation)->last_name ?? null;
                                            return [
                                                'id' => $cto_application->id,
                                                'remarks' => $cto_application->remarks,
                                                'purpose' => $cto_application->purpose,
                                                'status' => $cto_application->status,
                                                'total_days'=>$total_days,
                                                'employee_id' => $cto_application->employee_profile_id,
                                                'employee_name' => "{$first_name} {$last_name}" ,
                                                'position_code' => $cto_application->employeeProfile->assignedArea->designation->code ?? null,
                                                'position_name' => $cto_application->employeeProfile->assignedArea->designation->name ?? null,
                                                'date_created' => $cto_application->created_at,
                                                'division_head' =>$chief_name,
                                                'division_head_position'=> $chief_position,
                                                'department_head' =>$head_name,
                                                'department_head_position' =>$head_position,
                                                'section_head' =>$supervisor_name,
                                                'section_head_position' =>$supervisor_position,
                                                'division_name' => $cto_application->employeeProfile->assignedArea->division->name ?? null,
                                                'department_name' => $cto_application->employeeProfile->assignedArea->department->name ?? null,
                                                'section_name' => $cto_application->employeeProfile->assignedArea->section->name ?? null,
                                                'unit_name' => $cto_application->employeeProfile->assignedArea->unit->name ?? null,
                                                'date' => $cto_application->date,
                                                'time' => $cto_application->time,
                                                'logs' => $logsData ->map(function ($log) {
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
                                                        'cto_application_id ' => $log->cto_application_id ,
                                                        'action_by' => "{$first_name} {$last_name}" ,
                                                        'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                                        'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                                        'action' => $log->action,
                                                        'date' => $formatted_date,
                                                        'time' => $log->time,
                                                        'process' => $action
                                                    ];
                                                }),
                                                'dates' => $datesData->map(function ($date) {
                                                    return [
                                                                'id' => $date->id,
                                                                'cto_application_id' =>$date->cto_application_id,
                                                                'time_from' => $date->time_from,
                                                                'time_to' => $date->time_to,
                                                                'date' => $date->date,

                                                    ];
                                                }),

                                            ];
                                            });
                                    $singleArray = array_merge(...$cto_applications_result);
                                return response(['message' => 'Application has been sucessfully declined', 'data' => $singleArray], Response::HTTP_CREATED);

                        //     }
                        //  }
                }
            } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),  'error'=>true]);
        }
    }

    public function cancelCtoApplication($id,Request $request)
    {
        try {

                $cto_applications = CtoApplication::where('id','=', $id)->first();
                if($cto_applications)
                {
                //         $user_id = Auth::user()->id;
                //         $user = EmployeeProfile::where('id','=',$user_id)->first();
                //         $user_password=$user->password;
                //         $password=$request->password;
                //         if($user_password==$password)
                //         {
                //             if($user_id){
                                $cto_application_log = new CtoApplicationLog();
                                $cto_application_log->action = 'cancelled';
                                $cto_application_log->cto_application_id  = $id;
                                $cto_application_log->date = date('Y-m-d');
                                $cto_application_log->time =  date('H:i:s');
                                $cto_application_log->action_by_id = '1';
                                $cto_application_log->save();

                                $cto_application = CtoApplication::findOrFail($id);
                                $cto_application->status = 'cancelled';
                                $cto_application->update();
                                $cto_applications = CtoApplication::with(['employeeProfile.personalInformation','dates','logs'])
                                ->where('id',$cto_application->id)->get();
                                $cto_applications_result = $cto_applications->map(function ($cto_application) {
                                    $datesData = $cto_application->dates ? $cto_application->dates : collect();
                                    $logsData = $cto_application->logs ? $cto_application->logs : collect();
                                    $division = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('division_id');
                                    $department = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('department_id');
                                    $section = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('section_id');
                                            $chief_name=null;
                                            $chief_position=null;
                                            $head_name=null;
                                            $head_position=null;
                                            $supervisor_name=null;
                                            $supervisor_position=null;
                                            if($division) {
                                                $division_name = Division::with('chief.personalInformation')->find($division);

                                                if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                                {
                                                    $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;

                                                    $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                                }
                                            }
                                            if($department)
                                            {
                                                $department_name = Department::with('head.personalInformation')->find($department);
                                                if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                                                {

                                                $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                                $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                                }
                                            }
                                            if($section)
                                            {
                                                $section_name = Section::with('supervisor.personalInformation')->find($section);
                                                if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                                                {
                                                $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                                $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                                }
                                            }
                                            $overtimeRecord = CtoApplicationDate::where('cto_application_id',$cto_application->id);
                                            if($overtimeRecord)
                                            {
                                                $dates = $overtimeRecord->pluck('date')->toArray();
                                                $total_days = count(array_unique($dates));

                                            }
                                            $first_name = optional($cto_application->employeeProfile->personalInformation)->first_name ?? null;
                                            $last_name = optional($cto_application->employeeProfile->personalInformation)->last_name ?? null;
                                            return [
                                                'id' => $cto_application->id,
                                                'remarks' => $cto_application->remarks,
                                                'purpose' => $cto_application->purpose,
                                                'status' => $cto_application->status,
                                                'total_days'=>$total_days,
                                                'employee_id' => $cto_application->employee_profile_id,
                                                'employee_name' => "{$first_name} {$last_name}" ,
                                                'position_code' => $cto_application->employeeProfile->assignedArea->designation->code ?? null,
                                                'position_name' => $cto_application->employeeProfile->assignedArea->designation->name ?? null,
                                                'date_created' => $cto_application->created_at,
                                                'division_head' =>$chief_name,
                                                'division_head_position'=> $chief_position,
                                                'department_head' =>$head_name,
                                                'department_head_position' =>$head_position,
                                                'section_head' =>$supervisor_name,
                                                'section_head_position' =>$supervisor_position,
                                                'division_name' => $cto_application->employeeProfile->assignedArea->division->name ?? null,
                                                'department_name' => $cto_application->employeeProfile->assignedArea->department->name ?? null,
                                                'section_name' => $cto_application->employeeProfile->assignedArea->section->name ?? null,
                                                'unit_name' => $cto_application->employeeProfile->assignedArea->unit->name ?? null,
                                                'date' => $cto_application->date,
                                                'time' => $cto_application->time,
                                                'logs' => $logsData ->map(function ($log) {
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
                                                        'cto_application_id ' => $log->cto_application_id ,
                                                        'action_by' => "{$first_name} {$last_name}" ,
                                                        'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                                        'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                                        'action' => $log->action,
                                                        'date' => $formatted_date,
                                                        'time' => $log->time,
                                                        'process' => $action
                                                    ];
                                                }),
                                                'dates' => $datesData->map(function ($date) {
                                                    return [
                                                                'id' => $date->id,
                                                                'cto_application_id' =>$date->cto_application_id,
                                                                'time_from' => $date->time_from,
                                                                'time_to' => $date->time_to,
                                                                'date' => $date->date,

                                                    ];
                                                }),

                                            ];
                                            });
                                    $singleArray = array_merge(...$cto_applications_result);
                                return response(['message' => 'Application has been sucessfully cancelled', 'data' => $singleArray], Response::HTTP_CREATED);

                        //     }
                        //  }
                }
            } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),  'error'=>true]);
        }
    }

    public function getUserCtoApplication()
    {
        try{
            $id='1';
            $cto_applications = CtoApplication::with(['employeeProfile.personalInformation','logs'])
            ->where('employee_profile_id', $id)
            ->get();
            if($cto_applications->isNotEmpty())
            {
                $cto_applications_result = $cto_applications->map(function ($cto_application) {
                    $datesData = $cto_application->dates ? $cto_application->dates : collect();
                    $logsData = $cto_application->logs ? $cto_application->logs : collect();
                    $division = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('section_id');
                            $chief_name=null;
                            $chief_position=null;
                            $head_name=null;
                            $head_position=null;
                            $supervisor_name=null;
                            $supervisor_position=null;
                            if($division) {
                                $division_name = Division::with('chief.personalInformation')->find($division);

                                if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                {
                                    $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;

                                    $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                }
                            }
                            if($department)
                            {
                                $department_name = Department::with('head.personalInformation')->find($department);
                                if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                                {

                                $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                }
                            }
                            if($section)
                            {
                                $section_name = Section::with('supervisor.personalInformation')->find($section);
                                if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                                {
                                $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                }
                            }
                            $overtimeRecord = CtoApplicationDate::where('cto_application_id',$cto_application->id);
                            if($overtimeRecord)
                            {
                                $dates = $overtimeRecord->pluck('date')->toArray();
                                $total_days = count(array_unique($dates));

                            }
                                $first_name = optional($cto_application->employeeProfile->personalInformation)->first_name ?? null;
                                $last_name = optional($cto_application->employeeProfile->personalInformation)->last_name ?? null;
                                return [
                                    'id' => $cto_application->id,
                                    'remarks' => $cto_application->remarks,
                                    'purpose' => $cto_application->purpose,
                                    'status' => $cto_application->status,
                                    'total_days'=> $total_days,
                                    'employee_id' => $cto_application->employee_profile_id,
                                    'employee_name' => "{$first_name} {$last_name}" ,
                                    'position_code' => $cto_application->employeeProfile->assignedArea->designation->code ?? null,
                                    'position_name' => $cto_application->employeeProfile->assignedArea->designation->name ?? null,
                                    'date_created' => $cto_application->created_at,
                                    'division_head' =>$chief_name,
                                    'division_head_position'=> $chief_position,
                                    'department_head' =>$head_name,
                                    'department_head_position' =>$head_position,
                                    'section_head' =>$supervisor_name,
                                    'section_head_position' =>$supervisor_position,
                                    'division_name' => $cto_application->employeeProfile->assignedArea->division->name ?? null,
                                    'department_name' => $cto_application->employeeProfile->assignedArea->department->name ?? null,
                                    'section_name' => $cto_application->employeeProfile->assignedArea->section->name ?? null,
                                    'unit_name' => $cto_application->employeeProfile->assignedArea->unit->name ?? null,
                                    'date' => $cto_application->date,
                                    'time' => $cto_application->time,
                                    'logs' => $logsData ->map(function ($log) {
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
                                            'cto_application_id ' => $log->cto_application_id ,
                                            'action_by' => "{$first_name} {$last_name}" ,
                                            'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                            'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                            'action' => $log->action,
                                            'date' => $formatted_date,
                                            'time' => $log->time,
                                            'process' => $action
                                        ];
                                    }),
                                    'dates' => $datesData->map(function ($date) {
                                        return [
                                                    'id' => $date->id,
                                                    'cto_application_id' =>$date->cto_application_id,
                                                    'time_from' => $date->time_from,
                                                    'time_to' => $date->time_to,
                                                    'date' => $date->date,

                                        ];
                                    }),

                                ];
                            });
                        $OvertimeCredits = EmployeeOvertimeCredit::where('employee_profile_id', $id)
                        ->get();

                        $totalOvertimeCredits = 0;

                        foreach ($OvertimeCredits as $credit) {
                            $operation = $credit->operation;
                            $creditTotal = $credit->credit_value;

                            if ($operation === 'add') {
                                $totalOvertimeCredits += $creditTotal;
                            } elseif ($operation === 'deduct') {
                                $totalOvertimeCredits -= $creditTotal;
                            }
                        }

                        return response()->json(['data' => $cto_applications_result,'balance' => $totalOvertimeCredits], Response::HTTP_OK);

            }
            else
            {
                return response()->json(['message' => 'No records available'], Response::HTTP_OK);
            }

        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function getCtoApplications($id,$status,Request $request)
    {

        $cto_applications = [];
        $division = AssignArea::where('employee_profile_id',$id)->value('division_id');
        if($status == 'for-approval-division-head'){
                $divisionHeadId = Division::where('id', $division)->value('chief_employee_profile_id');
                if($divisionHeadId == $id) {
                    $cto_applications = CtoApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','logs'])
                    ->whereHas('employeeProfile.assignedArea', function ($query) use ($division) {
                        $query->where('id', $division);
                    })
                    ->where('status', 'for-approval-division-head')
                    ->get();

                    $cto_applications_result = $cto_applications->map(function ($cto_application) {
                        $datesData = $cto_application->dates ? $cto_application->dates : collect();
                        $logsData = $cto_application->logs ? $cto_application->logs : collect();
                        $division = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('section_id');
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
                                $overtimeRecord = CtoApplicationDate::where('cto_application_id',$cto_application->id);
                                if($overtimeRecord)
                                {
                                    $dates = $overtimeRecord->pluck('date')->toArray();
                                    $total_days = count(array_unique($dates));

                                }
                        $first_name = optional($cto_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($cto_application->employeeProfile->personalInformation)->last_name ?? null;
                        return [
                            'id' => $cto_application->id,
                            'remarks' => $cto_application->remarks,
                            'purpose' => $cto_application->purpose,
                            'status' => $cto_application->status,
                            'total_days'=> $total_days,
                            'employee_id' => $cto_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}" ,
                            'position' => $cto_application->employeeProfile->assignedArea->designation->name ?? null,
                            'division_head' =>$chief_name,
                            'division_head_position'=> $chief_position,
                            'department_head' =>$head_name,
                            'department_head_position' =>$head_position,
                            'section_head' =>$supervisor_name,
                            'section_head_position' =>$supervisor_position,
                            'division_name' => $cto_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $cto_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $cto_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $cto_application->employeeProfile->assignedArea->unit->name ?? null,
                            'date' => $cto_application->date,
                            'time' => $cto_application->time,
                            'logs' => $logsData ->map(function ($log) {
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
                                    'cto_application_id ' => $log->cto_application_id ,
                                    'action_by' => "{$first_name} {$last_name}" ,
                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),
                            'dates' => $datesData->map(function ($date) {
                                return [
                                            'id' => $date->id,
                                            'cto_application_id' =>$date->cto_application_id,
                                            'time_from' => $date->time_from,
                                            'time_to' => $date->time_to,
                                            'date' => $date->date,

                                ];
                            }),

                        ];
                    });


                    return response()->json(['cto_applications' => $cto_applications_result ]);
                }
        }
        else if($status == 'for-approval-department-head'){
            $department = AssignArea::where('employee_profile_id',$id)->value('department_id');
            $departmentHeadId = Department::where('id', $department)->value('head_employee_profile_id');
            $training_officer_id = Department::where('id', $department)->value('training_officer_employee_profile_id');
            if($departmentHeadId == $id || $training_officer_id == $id) {
                $cto_applications = CtoApplication::with(['employeeProfile.assignedArea.department','employeeProfile.personalInformation','logs'])
                ->whereHas('employeeProfile.assignedArea', function ($query) use ($department) {
                    $query->where('id', $department);
                })
                ->where('status', 'for-approval-department-head')
                ->get();

                $cto_applications_result = $cto_applications->map(function ($cto_application) {
                    $datesData = $cto_application->dates ? $cto_application->dates : collect();
                    $logsData = $cto_application->logs ? $cto_application->logs : collect();
                    $division = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('section_id');
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
                            $overtimeRecord = CtoApplicationDate::where('cto_application_id',$cto_application->id);
                            if($overtimeRecord)
                            {
                                $dates = $overtimeRecord->pluck('date')->toArray();
                                $total_days = count(array_unique($dates));

                            }
                    $first_name = optional($cto_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($cto_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $cto_application->id,
                        'remarks' => $cto_application->remarks,
                        'purpose' => $cto_application->purpose,
                        'status' => $cto_application->status,
                        'total_days'=> $total_days,
                        'employee_id' => $cto_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'position' => $cto_application->employeeProfile->assignedArea->designation->name ?? null,
                        'division_head' =>$chief_name,
                        'division_head_position'=> $chief_position,
                        'department_head' =>$head_name,
                        'department_head_position' =>$head_position,
                        'section_head' =>$supervisor_name,
                        'section_head_position' =>$supervisor_position,
                        'division_name' => $cto_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $cto_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $cto_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $cto_application->employeeProfile->assignedArea->unit->name ?? null,
                        'date' => $cto_application->date,
                        'time' => $cto_application->time,
                        'logs' => $logsData ->map(function ($log) {
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
                                'cto_application_id ' => $log->cto_application_id ,
                                'action_by' => "{$first_name} {$last_name}" ,
                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),
                        'dates' => $datesData->map(function ($date) {
                            return [
                                        'id' => $date->id,
                                        'cto_application_id' =>$date->cto_application_id,
                                        'time_from' => $date->time_from,
                                        'time_to' => $date->time_to,
                                        'date' => $date->date,

                            ];
                        }),

                    ];
                });
                return response()->json(['cto_applications' => $cto_applications_result ]);
            }
        }
        else if($status == 'for-approval-section-head'){
            $section = AssignArea::where('employee_profile_id',$id)->value('section_id');
            $sectionHeadId = Section::where('id', $section)->value('supervisor_employee_profile_id');
            if($sectionHeadId == $id) {

                $cto_applications = ctoApplication::with(['employeeProfile.assignedArea.section','employeeProfile.personalInformation','logs'])
                ->whereHas('employeeProfile.assignedArea', function ($query) use ($section) {
                    $query->where('id', $section);
                })
                ->where('status', 'for-approval-section-head')
                ->get();

                $cto_applications_result = $cto_applications->map(function ($cto_application) {
                    $datesData = $cto_application->dates ? $cto_application->dates : collect();
                    $logsData = $cto_application->logs ? $cto_application->logs : collect();
                    $division = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('section_id');
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
                            $overtimeRecord = CtoApplicationDate::where('cto_application_id',$cto_application->id);
                            if($overtimeRecord)
                            {
                                $dates = $overtimeRecord->pluck('date')->toArray();
                                $total_days = count(array_unique($dates));

                            }
                    $first_name = optional($cto_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($cto_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $cto_application->id,
                        'remarks' => $cto_application->remarks,
                        'purpose' => $cto_application->purpose,
                        'status' => $cto_application->status,
                        'total_days'=> $total_days,
                        'employee_id' => $cto_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'position' => $cto_application->employeeProfile->assignedArea->designation->name ?? null,
                        'division_head' =>$chief_name,
                        'division_head_position'=> $chief_position,
                        'department_head' =>$head_name,
                        'department_head_position' =>$head_position,
                        'section_head' =>$supervisor_name,
                        'section_head_position' =>$supervisor_position,
                        'division_name' => $cto_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $cto_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $cto_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $cto_application->employeeProfile->assignedArea->unit->name ?? null,
                        'date' => $cto_application->date,
                        'time' => $cto_application->time,
                        'logs' => $logsData ->map(function ($log) {
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
                                'cto_application_id ' => $log->cto_application_id ,
                                'action_by' => "{$first_name} {$last_name}" ,
                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),
                        'dates' => $datesData->map(function ($date) {
                            return [
                                        'id' => $date->id,
                                        'cto_application_id' =>$date->cto_application_id,
                                        'time_from' => $date->time_from,
                                        'time_to' => $date->time_to,
                                        'date' => $date->date,

                            ];
                        }),

                    ];
                });


                return response()->json(['cto_applications' => $cto_applications_result ]);
            }
        }
        else if($status == 'declined'){
            $cto_applications = ctoApplication::with(['employeeProfile.assignedArea.section','employeeProfile.personalInformation','logs'])
            ->where('status', 'declined')
            ->get();
            $cto_applications_result = $cto_applications->map(function ($cto_application) {
                $datesData = $cto_application->dates ? $cto_application->dates : collect();
                $logsData = $cto_application->logs ? $cto_application->logs : collect();
                $division = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('division_id');
                $department = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('department_id');
                $section = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('section_id');
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
                        $overtimeRecord = CtoApplicationDate::where('cto_application_id',$cto_application->id);
                        if($overtimeRecord)
                        {
                            $dates = $overtimeRecord->pluck('date')->toArray();
                            $total_days = count(array_unique($dates));

                        }
                $first_name = optional($cto_application->employeeProfile->personalInformation)->first_name ?? null;
                $last_name = optional($cto_application->employeeProfile->personalInformation)->last_name ?? null;
                return [
                    'id' => $cto_application->id,
                    'remarks' => $cto_application->remarks,
                    'purpose' => $cto_application->purpose,
                    'status' => $cto_application->status,
                    'total_days'=> $total_days,
                    'employee_id' => $cto_application->employee_profile_id,
                    'employee_name' => "{$first_name} {$last_name}" ,
                    'position' => $cto_application->employeeProfile->assignedArea->designation->name ?? null,
                    'division_head' =>$chief_name,
                    'division_head_position'=> $chief_position,
                    'department_head' =>$head_name,
                    'department_head_position' =>$head_position,
                    'section_head' =>$supervisor_name,
                    'section_head_position' =>$supervisor_position,
                    'division_name' => $cto_application->employeeProfile->assignedArea->division->name ?? null,
                    'department_name' => $cto_application->employeeProfile->assignedArea->department->name ?? null,
                    'section_name' => $cto_application->employeeProfile->assignedArea->section->name ?? null,
                    'unit_name' => $cto_application->employeeProfile->assignedArea->unit->name ?? null,
                    'date' => $cto_application->date,
                    'time' => $cto_application->time,
                    'logs' => $logsData ->map(function ($log) {
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
                            'cto_application_id ' => $log->cto_application_id ,
                            'action_by' => "{$first_name} {$last_name}" ,
                            'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                            'action' => $log->action,
                            'date' => $formatted_date,
                            'time' => $log->time,
                            'process' => $action
                        ];
                    }),
                    'dates' => $datesData->map(function ($date) {
                        return [
                                    'id' => $date->id,
                                    'cto_application_id' =>$date->cto_application_id,
                                    'time_from' => $date->time_from,
                                    'time_to' => $date->time_to,
                                    'date' => $date->date,

                        ];
                    }),

                ];
                });


                return response()->json(['cto_applications' => $cto_applications_result ]);
        }
        else{

        }
        // if (isset($request->search)) {
        //     $search = $request->search;
        //     $leave_applications = $leave_applications->where('reference_number','like', '%' .$search . '%');

        //     $leave_applications = isset($search) && $search;
        // }
        // return ResourcesLeaveApplication::collection($leave_applications->paginate(50));
    }

    public function getDivisionCtoApplications(Request $request)
    {

        try{
            $id='1';
                $division = AssignArea::where('employee_profile_id',$id)->value('division_id');
                $divisionHeadId = Division::where('id', $division)->value('chief_employee_profile_id');
                if($divisionHeadId == $id) {
                    $cto_applications = CtoApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','logs'])
                    ->whereHas('employeeProfile.assignedArea', function ($query) use ($division) {
                        $query->where('id', $division);
                    })
                    // ->where('status', 'for-approval-division-head')
                    ->get();
                    if($cto_applications->isNotEmpty())
                    {
                        $cto_applications_result = $cto_applications->map(function ($cto_application) {
                            $datesData = $cto_application->dates ? $cto_application->dates : collect();
                            $logsData = $cto_application->logs ? $cto_application->logs : collect();
                            $division = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('division_id');
                            $department = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('department_id');
                            $section = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('section_id');
                                    $chief_name=null;
                                    $chief_position=null;
                                    $head_name=null;
                                    $head_position=null;
                                    $supervisor_name=null;
                                    $supervisor_position=null;
                                    if($division) {
                                        $division_name = Division::with('chief.personalInformation')->find($division);

                                        if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                        {
                                            $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;

                                            $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                        }
                                    }
                                    if($department)
                                    {
                                        $department_name = Department::with('head.personalInformation')->find($department);
                                        if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                                        {

                                        $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                        $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                        }
                                    }
                                    if($section)
                                    {
                                        $section_name = Section::with('supervisor.personalInformation')->find($section);
                                        if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                                        {
                                        $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                        $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                        }
                                    }
                                    $overtimeRecord = CtoApplicationDate::where('cto_application_id',$cto_application->id);
                                    if($overtimeRecord)
                                    {
                                        $dates = $overtimeRecord->pluck('date')->toArray();
                                        $total_days = count(array_unique($dates));

                                    }
                            $first_name = optional($cto_application->employeeProfile->personalInformation)->first_name ?? null;
                            $last_name = optional($cto_application->employeeProfile->personalInformation)->last_name ?? null;
                            return [
                                'id' => $cto_application->id,
                                'remarks' => $cto_application->remarks,
                                'purpose' => $cto_application->purpose,
                                'status' => $cto_application->status,
                                'total_days'=> $total_days,
                                'employee_id' => $cto_application->employee_profile_id,
                                'employee_name' => "{$first_name} {$last_name}" ,
                                'position_code' => $cto_application->employeeProfile->assignedArea->designation->code ?? null,
                                'position_name' => $cto_application->employeeProfile->assignedArea->designation->name ?? null,
                                'date_created' => $cto_application->created_at,
                                'division_head' =>$chief_name,
                                'division_head_position'=> $chief_position,
                                'department_head' =>$head_name,
                                'department_head_position' =>$head_position,
                                'section_head' =>$supervisor_name,
                                'section_head_position' =>$supervisor_position,
                                'division_name' => $cto_application->employeeProfile->assignedArea->division->name ?? null,
                                'department_name' => $cto_application->employeeProfile->assignedArea->department->name ?? null,
                                'section_name' => $cto_application->employeeProfile->assignedArea->section->name ?? null,
                                'unit_name' => $cto_application->employeeProfile->assignedArea->unit->name ?? null,
                                'date' => $cto_application->date,
                                'time' => $cto_application->time,
                                'logs' => $logsData ->map(function ($log) {
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
                                        'cto_application_id ' => $log->cto_application_id ,
                                        'action_by' => "{$first_name} {$last_name}" ,
                                        'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                        'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                        'action' => $log->action,
                                        'date' => $formatted_date,
                                        'time' => $log->time,
                                        'process' => $action
                                    ];
                                }),
                                'dates' => $datesData->map(function ($date) {
                                    return [
                                                'id' => $date->id,
                                                'cto_application_id' =>$date->cto_application_id,
                                                'time_from' => $date->time_from,
                                                'time_to' => $date->time_to,
                                                'date' => $date->date,

                                    ];
                                }),

                            ];
                        });


                        return response()->json(['data' => $cto_applications_result ]);
                    }
                    else
                    {
                        return response()->json(['message' => 'No records available'], Response::HTTP_OK);
                    }


                }
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    public function getDepartmentCtoApplications(Request $request)
    {
        try{
            $id='1';
            $department = AssignArea::where('employee_profile_id',$id)->value('department_id');
            $departmentHeadId = Department::where('id', $department)->value('head_employee_profile_id');
            $training_officer_id = Department::where('id', $department)->value('training_officer_employee_profile_id');
            if($departmentHeadId == $id || $training_officer_id == $id) {
                $cto_applications = CtoApplication::with(['employeeProfile.assignedArea.department','employeeProfile.personalInformation','logs'])
                ->whereHas('employeeProfile.assignedArea', function ($query) use ($department) {
                    $query->where('id', $department);
                })
                // ->where('status', 'for-approval-department-head')
                ->get();
                if($cto_applications->isNotEmpty())
                {
                    $cto_applications_result = $cto_applications->map(function ($cto_application) {
                        $datesData = $cto_application->dates ? $cto_application->dates : collect();
                        $logsData = $cto_application->logs ? $cto_application->logs : collect();
                        $division = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('section_id');
                                $chief_name=null;
                                $chief_position=null;
                                $head_name=null;
                                $head_position=null;
                                $supervisor_name=null;
                                $supervisor_position=null;
                                if($division) {
                                    $division_name = Division::with('chief.personalInformation')->find($division);

                                    if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                    {
                                        $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;

                                        $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                    }
                                }
                                if($department)
                                {
                                    $department_name = Department::with('head.personalInformation')->find($department);
                                    if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                                    {

                                    $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                    $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                    }
                                }
                                if($section)
                                {
                                    $section_name = Section::with('supervisor.personalInformation')->find($section);
                                    if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                                    {
                                    $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                    $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                    }
                                }
                                $overtimeRecord = CtoApplicationDate::where('cto_application_id',$cto_application->id);
                                if($overtimeRecord)
                                {
                                    $dates = $overtimeRecord->pluck('date')->toArray();
                                    $total_days = count(array_unique($dates));

                                }
                        $first_name = optional($cto_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($cto_application->employeeProfile->personalInformation)->last_name ?? null;
                        return [
                            'id' => $cto_application->id,
                            'remarks' => $cto_application->remarks,
                            'purpose' => $cto_application->purpose,
                            'status' => $cto_application->status,
                            'total_days'=> $total_days,
                            'employee_id' => $cto_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}" ,
                            'position_code' => $cto_application->employeeProfile->assignedArea->designation->code ?? null,
                            'position_name' => $cto_application->employeeProfile->assignedArea->designation->name ?? null,
                            'date_created' => $cto_application->created_at,
                            'division_head' =>$chief_name,
                            'division_head_position'=> $chief_position,
                            'department_head' =>$head_name,
                            'department_head_position' =>$head_position,
                            'section_head' =>$supervisor_name,
                            'section_head_position' =>$supervisor_position,
                            'division_name' => $cto_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $cto_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $cto_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $cto_application->employeeProfile->assignedArea->unit->name ?? null,
                            'date' => $cto_application->date,
                            'time' => $cto_application->time,
                            'logs' => $logsData ->map(function ($log) {
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
                                    'cto_application_id ' => $log->cto_application_id ,
                                    'action_by' => "{$first_name} {$last_name}" ,
                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),
                            'dates' => $datesData->map(function ($date) {
                                return [
                                            'id' => $date->id,
                                            'cto_application_id' =>$date->cto_application_id,
                                            'time_from' => $date->time_from,
                                            'time_to' => $date->time_to,
                                            'date' => $date->date,

                                ];
                            }),

                        ];
                    });
                    return response()->json(['data' => $cto_applications_result ]);
                }
                else
                {
                    return response()->json(['message' => 'No records available'], Response::HTTP_OK);
                }

            }
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    public function getSectionCtoApplications(Request $request)
    {

        try{
            $id='1';
            $section = AssignArea::where('employee_profile_id',$id)->value('section_id');
            $sectionHeadId = Section::where('id', $section)->value('supervisor_employee_profile_id');
            if($sectionHeadId == $id) {
                $cto_applications = ctoApplication::with(['employeeProfile.assignedArea.section','employeeProfile.personalInformation','logs'])
                ->whereHas('employeeProfile.assignedArea', function ($query) use ($section) {
                    $query->where('id', $section);
                })
                // ->where('status', 'for-approval-section-head')
                ->get();
                if($cto_applications->isNotEmpty())
                {
                    $cto_applications_result = $cto_applications->map(function ($cto_application) {
                        $datesData = $cto_application->dates ? $cto_application->dates : collect();
                        $logsData = $cto_application->logs ? $cto_application->logs : collect();
                        $division = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('section_id');
                                $chief_name=null;
                                $chief_position=null;
                                $head_name=null;
                                $head_position=null;
                                $supervisor_name=null;
                                $supervisor_position=null;
                                if($division) {
                                    $division_name = Division::with('chief.personalInformation')->find($division);

                                    if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                    {
                                        $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;

                                        $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                    }
                                }
                                if($department)
                                {
                                    $department_name = Department::with('head.personalInformation')->find($department);
                                    if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                                    {

                                    $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                    $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                    }
                                }
                                if($section)
                                {
                                    $section_name = Section::with('supervisor.personalInformation')->find($section);
                                    if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                                    {
                                    $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                    $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                    }
                                }
                                $overtimeRecord = CtoApplicationDate::where('cto_application_id',$cto_application->id);
                                if($overtimeRecord)
                                {
                                    $dates = $overtimeRecord->pluck('date')->toArray();
                                    $total_days = count(array_unique($dates));

                                }
                        $first_name = optional($cto_application->employeeProfile->personalInformation)->first_name ?? null;
                        $last_name = optional($cto_application->employeeProfile->personalInformation)->last_name ?? null;
                        return [
                            'id' => $cto_application->id,
                            'remarks' => $cto_application->remarks,
                            'purpose' => $cto_application->purpose,
                            'status' => $cto_application->status,
                            'total_days'=> $total_days,
                            'employee_id' => $cto_application->employee_profile_id,
                            'employee_name' => "{$first_name} {$last_name}" ,
                            'position_code' => $cto_application->employeeProfile->assignedArea->designation->code ?? null,
                            'position_name' => $cto_application->employeeProfile->assignedArea->designation->name ?? null,
                            'date_created' => $cto_application->created_at,
                            'division_head' =>$chief_name,
                            'division_head_position'=> $chief_position,
                            'department_head' =>$head_name,
                            'department_head_position' =>$head_position,
                            'section_head' =>$supervisor_name,
                            'section_head_position' =>$supervisor_position,
                            'division_name' => $cto_application->employeeProfile->assignedArea->division->name ?? null,
                            'department_name' => $cto_application->employeeProfile->assignedArea->department->name ?? null,
                            'section_name' => $cto_application->employeeProfile->assignedArea->section->name ?? null,
                            'unit_name' => $cto_application->employeeProfile->assignedArea->unit->name ?? null,
                            'date' => $cto_application->date,
                            'time' => $cto_application->time,
                            'logs' => $logsData ->map(function ($log) {
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
                                    'cto_application_id ' => $log->cto_application_id ,
                                    'action_by' => "{$first_name} {$last_name}" ,
                                    'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                    'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                    'action' => $log->action,
                                    'date' => $formatted_date,
                                    'time' => $log->time,
                                    'process' => $action
                                ];
                            }),
                            'dates' => $datesData->map(function ($date) {
                                return [
                                            'id' => $date->id,
                                            'cto_application_id' =>$date->cto_application_id,
                                            'time_from' => $date->time_from,
                                            'time_to' => $date->time_to,
                                            'date' => $date->date,

                                ];
                            }),

                        ];
                    });
                    return response()->json(['data' => $cto_applications_result ]);
                }
                else
                {
                    return response()->json(['message' => 'No records available'], Response::HTTP_OK);
                }

            }
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    public function getDeclinedCtoApplications(Request $request)
    {

        try{
            $id='1';
            $cto_applications = ctoApplication::with(['employeeProfile.assignedArea.section','employeeProfile.personalInformation','logs'])
            // ->where('status', 'declined')
            ->get();
            if($cto_applications->isNotEmpty())
            {
                $cto_applications_result = $cto_applications->map(function ($cto_application) {
                    $datesData = $cto_application->dates ? $cto_application->dates : collect();
                    $logsData = $cto_application->logs ? $cto_application->logs : collect();
                    $division = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('section_id');
                            $chief_name=null;
                            $chief_position=null;
                            $head_name=null;
                            $head_position=null;
                            $supervisor_name=null;
                            $supervisor_position=null;
                            if($division) {
                                $division_name = Division::with('chief.personalInformation')->find($division);

                                if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                {
                                    $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;

                                    $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                }
                            }
                            if($department)
                            {
                                $department_name = Department::with('head.personalInformation')->find($department);
                                if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                                {

                                $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                }
                            }
                            if($section)
                            {
                                $section_name = Section::with('supervisor.personalInformation')->find($section);
                                if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                                {
                                $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                }
                            }
                            $overtimeRecord = CtoApplicationDate::where('cto_application_id',$cto_application->id);
                            if($overtimeRecord)
                            {
                                $dates = $overtimeRecord->pluck('date')->toArray();
                                $total_days = count(array_unique($dates));

                            }
                    $first_name = optional($cto_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($cto_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $cto_application->id,
                        'remarks' => $cto_application->remarks,
                        'purpose' => $cto_application->purpose,
                        'status' => $cto_application->status,
                        'total_days'=> $total_days,
                        'employee_id' => $cto_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'position_code' => $cto_application->employeeProfile->assignedArea->designation->code ?? null,
                        'position_name' => $cto_application->employeeProfile->assignedArea->designation->name ?? null,
                        'date_created' => $cto_application->created_at,
                        'division_head' =>$chief_name,
                        'division_head_position'=> $chief_position,
                        'department_head' =>$head_name,
                        'department_head_position' =>$head_position,
                        'section_head' =>$supervisor_name,
                        'section_head_position' =>$supervisor_position,
                        'division_name' => $cto_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $cto_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $cto_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $cto_application->employeeProfile->assignedArea->unit->name ?? null,
                        'date' => $cto_application->date,
                        'time' => $cto_application->time,
                        'logs' => $logsData ->map(function ($log) {
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
                                'cto_application_id ' => $log->cto_application_id ,
                                'action_by' => "{$first_name} {$last_name}" ,
                                'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                'action' => $log->action,
                                'date' => $formatted_date,
                                'time' => $log->time,
                                'process' => $action
                            ];
                        }),
                        'dates' => $datesData->map(function ($date) {
                            return [
                                        'id' => $date->id,
                                        'cto_application_id' =>$date->cto_application_id,
                                        'time_from' => $date->time_from,
                                        'time_to' => $date->time_to,
                                        'date' => $date->date,
                            ];
                        }),

                    ];
                });
                return response()->json(['data' => $cto_applications_result ]);
            }
            else
            {
                return response()->json(['message' => 'No records available'], Response::HTTP_OK);
            }

        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function updateStatus($id,$status,Request $request)
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
                            $cto_applications = CtoApplication::where('id','=', $id)
                                                                    ->first();
                            if($cto_applications){
                                $cto_application_log = new CtoApplicationLog();
                                $cto_application_log->action = $action;
                                $cto_application_log->cto_application_id = $id;
                                $cto_application_log->action_by_id = '1';
                                $cto_application_log->date = date('Y-m-d');
                                $cto_application_log->time = date('h-i-s');
                                $cto_application_log->save();

                                $cto_application = CtoApplication::findOrFail($id);
                                $cto_application->status = $new_status;
                                $cto_application->update();

                                if($new_status=="approved")
                                {
                                    $cto_application_date_times=CtoApplicationDate::where('cto_application_id',$id)->get();
                                    $totalHours = 0;
                                    foreach ($cto_application_date_times as $cto_application_date_time) {
                                        $timeFrom = Carbon::parse($cto_application_date_time->time_from);
                                        $timeTo = Carbon::parse($cto_application_date_time->time_to);
                                        $totalHours += $timeTo->diffInHours($timeFrom);
                                    }
                                    $employee_cto_credits = new EmployeeOvertimeCredit();
                                    $employee_cto_credits->employee_profile_id = '1';
                                    $employee_cto_credits->cto_application_id = $id;
                                    $employee_cto_credits->operation = "deduct";
                                    // $employee_cto_credits->reason = "cto";
                                    $employee_cto_credits->overtime_hours = $totalHours;
                                    $employee_cto_credits->credit_value = $totalHours;
                                    $employee_cto_credits->date = date('Y-m-d');
                                    $employee_cto_credits->save();

                                    $cto_applications = CtoApplication::with(['employeeProfile.personalInformation','dates','logs'])
                                    ->where('id',$cto_application->id)->get();
                                    $cto_applications_result = $cto_applications->map(function ($cto_application) {
                                        $datesData = $cto_application->dates ? $cto_application->dates : collect();
                                        $logsData = $cto_application->logs ? $cto_application->logs : collect();
                                        $division = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('division_id');
                                        $department = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('department_id');
                                        $section = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('section_id');
                                                $chief_name=null;
                                                $chief_position=null;
                                                $head_name=null;
                                                $head_position=null;
                                                $supervisor_name=null;
                                                $supervisor_position=null;
                                                if($division) {
                                                    $division_name = Division::with('chief.personalInformation')->find($division);

                                                    if($division_name && $division_name->chief  && $division_name->chief->personalInformation != null)
                                                    {
                                                        $chief_name = optional($division_name->chief->personalInformation)->first_name . ' ' . optional($division_name->chief->personalInformation)->last_name;

                                                        $chief_position = $division_name->chief->assignedArea->designation->name ?? null;
                                                    }
                                                }
                                                if($department)
                                                {
                                                    $department_name = Department::with('head.personalInformation')->find($department);
                                                    if($department_name && $department_name->head  && $department_name->head->personalInformation != null)
                                                    {

                                                    $head_name = optional($department_name->head->personalInformation)->first_name . ' ' . optional($department_name->head->personalInformation)->last_name;
                                                    $head_position = $department_name->head->assignedArea->designation->name ?? null;
                                                    }
                                                }
                                                if($section)
                                                {
                                                    $section_name = Section::with('supervisor.personalInformation')->find($section);
                                                    if($section_name && $section_name->supervisor  && $section_name->supervisor->personalInformation != null)
                                                    {
                                                    $supervisor_name = optional($section_name->supervisor->personalInformation)->first_name . ' ' . optional($section_name->supervisor->personalInformation)->last_name;
                                                    $supervisor_position = $section_name->supervisor->assignedArea->designation->name ?? null;
                                                    }
                                                }
                                                $overtimeRecord = CtoApplicationDate::where('cto_application_id',$cto_application->id);
                                                if($overtimeRecord)
                                                {
                                                    $dates = $overtimeRecord->pluck('date')->toArray();
                                                    $total_days = count(array_unique($dates));

                                                }
                                                $first_name = optional($cto_application->employeeProfile->personalInformation)->first_name ?? null;
                                                $last_name = optional($cto_application->employeeProfile->personalInformation)->last_name ?? null;
                                                return [
                                                    'id' => $cto_application->id,
                                                    'remarks' => $cto_application->remarks,
                                                    'purpose' => $cto_application->purpose,
                                                    'status' => $cto_application->status,
                                                    'total_days'=>$total_days,
                                                    'employee_id' => $cto_application->employee_profile_id,
                                                    'employee_name' => "{$first_name} {$last_name}" ,
                                                    'position_code' => $cto_application->employeeProfile->assignedArea->designation->code ?? null,
                                                    'position_name' => $cto_application->employeeProfile->assignedArea->designation->name ?? null,
                                                    'date_created' => $cto_application->created_at,
                                                    'division_head' =>$chief_name,
                                                    'division_head_position'=> $chief_position,
                                                    'department_head' =>$head_name,
                                                    'department_head_position' =>$head_position,
                                                    'section_head' =>$supervisor_name,
                                                    'section_head_position' =>$supervisor_position,
                                                    'division_name' => $cto_application->employeeProfile->assignedArea->division->name ?? null,
                                                    'department_name' => $cto_application->employeeProfile->assignedArea->department->name ?? null,
                                                    'section_name' => $cto_application->employeeProfile->assignedArea->section->name ?? null,
                                                    'unit_name' => $cto_application->employeeProfile->assignedArea->unit->name ?? null,
                                                    'date' => $cto_application->date,
                                                    'time' => $cto_application->time,
                                                    'logs' => $logsData ->map(function ($log) {
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
                                                            'cto_application_id ' => $log->cto_application_id ,
                                                            'action_by' => "{$first_name} {$last_name}" ,
                                                            'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                                                            'position_code' => $log->employeeProfile->assignedArea->designation->code ?? null,
                                                            'action' => $log->action,
                                                            'date' => $formatted_date,
                                                            'time' => $log->time,
                                                            'process' => $action
                                                        ];
                                                    }),
                                                    'dates' => $datesData->map(function ($date) {
                                                        return [
                                                                    'id' => $date->id,
                                                                    'cto_application_id' =>$date->cto_application_id,
                                                                    'time_from' => $date->time_from,
                                                                    'time_to' => $date->time_to,
                                                                    'date' => $date->date,

                                                        ];
                                                    }),

                                                ];
                                                });
                                        $singleArray = array_merge(...$cto_applications_result);

                                }
                                return response(['message' => 'Application has been sucessfully '.$message_action, 'data' => $singleArray], Response::HTTP_CREATED);
                            // }
                }
            }
         catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }

    }

}
