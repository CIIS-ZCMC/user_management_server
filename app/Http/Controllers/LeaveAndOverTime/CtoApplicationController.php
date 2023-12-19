<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Models\CtoApplication;
use App\Http\Controllers\Controller;
use App\Models\AssignArea;
use App\Models\CtoApplicationLog;
use App\Models\Department;
use App\Models\Division;
use App\Models\EmployeeOvertimeCredit;
use App\Models\Section;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
class CtoApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            $cto_applications = CtoApplication::with(['employeeProfile.personalInformation','logs'])->get();
            $cto_applications_result = $cto_applications->map(function ($cto_application) {
                    $division = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('section_id');
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
                $first_name = optional($cto_application->employeeProfile->personalInformation)->first_name ?? null;
                $last_name = optional($cto_application->employeeProfile->personalInformation)->last_name ?? null;
                return [
                    'id' => $cto_application->id,
                    'purpose' => $cto_application->purpose,
                    'status' => $cto_application->status,
                    'employee_id' => $cto_application->employee_profile_id,
                    'employee_name' => "{$first_name} {$last_name}" ,
                    'division_head' =>$chief_name,
                    'department_head' =>$head_name,
                    'section_head' =>$supervisor_name,
                    'division_name' => $cto_application->employeeProfile->assignedArea->division->name ?? null,
                    'department_name' => $cto_application->employeeProfile->assignedArea->department->name ?? null,
                    'section_name' => $cto_application->employeeProfile->assignedArea->section->name ?? null,
                    'unit_name' => $cto_application->employeeProfile->assignedArea->unit->name ?? null,
                    'logs' => $cto_application->logs->map(function ($log) {
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

                 return response()->json(['data' => $cto_applications_result], Response::HTTP_OK);
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

    /**
     * Store a newly created resource in storage.
     */
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
            $cto_application->purpose = $request->purpose;
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
            $columnsString="";
            $process_name="Applied";


            $this->storeCTOApplicationLog($cto_id,$process_name,$columnsString);
            $cto_applications = CtoApplication::with(['employeeProfile.personalInformation','logs'])
            ->where('id',$cto_application->id)->get();
            $cto_applications_result = $cto_applications->map(function ($cto_application) {
                    $division = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('section_id');
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
                $first_name = optional($cto_application->employeeProfile->personalInformation)->first_name ?? null;
                $last_name = optional($cto_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $cto_application->id,
                        'purpose' => $cto_application->purpose,
                        'status' => $cto_application->status,
                        'employee_id' => $cto_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'division_head' =>$chief_name,
                        'department_head' =>$head_name,
                        'section_head' =>$supervisor_name,
                        'division_name' => $cto_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $cto_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $cto_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $cto_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $cto_application->logs->map(function ($log) {
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
                                $cto_application->update();
                                return response(['message' => 'Application has been sucessfully declined', 'data' => $cto_application], Response::HTTP_CREATED);

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

                    $cto_applications = CtoApplication::where('id','=', $id)
                                                            ->first();
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

                                $cto_application = CtoApplicationLog::findOrFail($id);
                                $cto_application->status = 'cancelled';
                                $cto_application->update();
                                return response(['message' => 'Application has been sucessfully cancelled', 'data' => $cto_application], Response::HTTP_CREATED);

                        //     }
                        //  }
                }
            } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),  'error'=>true]);
        }
    }

    public function getUserCtoApplication($id)
    {
        try{
        $cto_applications = CtoApplication::with(['employeeProfile.personalInformation','logs'])
        ->where('employee_profile_id', $id)
        ->get();
            $cto_applications_result = $cto_applications->map(function ($cto_application) {
            $division = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('division_id');
            $department = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('department_id');
            $section = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('section_id');
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
            $first_name = optional($cto_application->employeeProfile->personalInformation)->first_name ?? null;
            $last_name = optional($cto_application->employeeProfile->personalInformation)->last_name ?? null;
            return [
                'id' => $cto_application->id,
                'purpose' => $cto_application->purpose,
                'status' => $cto_application->status,
                'employee_id' => $cto_application->employee_profile_id,
                'employee_name' => "{$first_name} {$last_name}" ,
                'division_head' =>$chief_name,
                'department_head' =>$head_name,
                'section_head' =>$supervisor_name,
                'division_name' => $cto_application->employeeProfile->assignedArea->division->name ?? null,
                'department_name' => $cto_application->employeeProfile->assignedArea->department->name ?? null,
                'section_name' => $cto_application->employeeProfile->assignedArea->section->name ?? null,
                'unit_name' => $cto_application->employeeProfile->assignedArea->unit->name ?? null,
                'logs' => $cto_application->logs->map(function ($log) {
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
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function getCtoApplications($id,$status,Request $request)
    {

        $cto_applications = [];
        $division = AssignArea::where('employee_profile_id',$id)->value('division_id');
        if($status == 'applied'){
            $section = AssignArea::where('employee_profile_id',$id)->value('section_id');
            $hr_head_id = Section::where('id', $section)->value('supervisor_employee_profile_id');
            if($hr_head_id == $id) {
                $cto_applications = CtoApplication::with(['employeeProfile.personalInformation','logs'])
                ->where('status', 'applied')
                ->get();
                $cto_applications_result = $cto_applications->map(function ($cto_application) {
                    $division = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('division_id');
                    $department = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('department_id');
                    $section = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('section_id');
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
                    $first_name = optional($cto_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($cto_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $cto_application->id,
                        'purpose' => $cto_application->purpose,
                        'status' => $cto_application->status,
                        'employee_id' => $cto_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'division_head' =>$chief_name,
                        'department_head' =>$head_name,
                        'section_head' =>$supervisor_name,
                        'division_name' => $cto_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $cto_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $cto_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $cto_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $cto_application->logs->map(function ($log) {
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


                return response()->json(['cto_applications' => $cto_applications_result]);
            }
        }
        else if($status == 'for-approval-division-head'){
                $divisionHeadId = Division::where('id', $division)->value('chief_employee_profile_id');
                if($divisionHeadId == $id) {
                    $cto_applications = CtoApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','logs'])
                    ->whereHas('employeeProfile.assignedArea', function ($query) use ($division) {
                        $query->where('id', $division);
                    })
                    ->where('status', 'for-approval-division-head')
                    ->get();

                    $cto_applications_result = $cto_applications->map(function ($cto_application) {
                        $division = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('division_id');
                        $department = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('department_id');
                        $section = AssignArea::where('employee_profile_id',$cto_application->employee_profile_id)->value('section_id');
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
                    $first_name = optional($cto_application->employeeProfile->personalInformation)->first_name ?? null;
                    $last_name = optional($cto_application->employeeProfile->personalInformation)->last_name ?? null;
                    return [
                        'id' => $cto_application->id,
                        'purpose' => $cto_application->purpose,
                        'status' => $cto_application->status,
                        'employee_id' => $cto_application->employee_profile_id,
                        'employee_name' => "{$first_name} {$last_name}" ,
                        'division_head' =>$chief_name,
                        'department_head' =>$head_name,
                        'section_head' =>$supervisor_name,
                        'division_name' => $cto_application->employeeProfile->assignedArea->division->name ?? null,
                        'department_name' => $cto_application->employeeProfile->assignedArea->department->name ?? null,
                        'section_name' => $cto_application->employeeProfile->assignedArea->section->name ?? null,
                        'unit_name' => $cto_application->employeeProfile->assignedArea->unit->name ?? null,
                        'logs' => $cto_application->logs->map(function ($log) {
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


                    return response()->json(['cto_applications' => $cto_applications_result ]);
                }
        }
        else if($status == 'for-approval-department-head'){
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
                return response()->json(['leave_applications' => $leave_applications_result]);
            }
        }
        else if($status == 'for-approval-section-head'){
            $section = AssignArea::where('employee_profile_id',$id)->value('section_id');
            $sectionHeadId = Section::where('id', $section)->value('supervisor_employee_profile_id');
            if($sectionHeadId == $id) {

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


                return response()->json(['leave_applications' => $leave_applications_result]);
        }
        else{
            $leave_applications = LeaveApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','dates','logs', 'requirements', 'leaveType'])
            ->where('employee_profile_id',$id )->get();
        }
        // if (isset($request->search)) {
        //     $search = $request->search;
        //     $leave_applications = $leave_applications->where('reference_number','like', '%' .$search . '%');

        //     $leave_applications = isset($search) && $search;
        // }
        // return ResourcesLeaveApplication::collection($leave_applications->paginate(50));
    }
    public function show(CtoApplication $ctoApplication)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CtoApplication $ctoApplication)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CtoApplication $ctoApplication)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CtoApplication $ctoApplication)
    {
        //
    }
}
