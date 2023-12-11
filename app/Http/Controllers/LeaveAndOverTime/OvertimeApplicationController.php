<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Models\OvertimeApplication;
use App\Http\Controllers\Controller;
use App\Models\AssignArea;
use App\Models\Department;
use App\Models\Division;
use App\Models\EmployeeOvertimeCredit;
use App\Models\EmployeeProfile;
use Illuminate\Support\Facades\Storage;
use App\Models\OvtApplicationActivity;
use App\Models\OvtApplicationDatetime;
use App\Models\OvtApplicationEmployee;
use App\Models\OvtApplicationLog;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Random\Engine\Secure;
use Carbon\Carbon;
class OvertimeApplicationController extends Controller
{

    public function index()
    {
        try{
            $overtime_applications=[];
            $overtime_applications =OvertimeApplication::with(['employeeProfile.assignedArea.division','employeeProfile.personalInformation','logs','activities'])->get();
            $overtime_applications_result = $overtime_applications->map(function ($overtime_application) {
            $division = AssignArea::where('employee_profile_id',$overtime_application->employee_profile_id)->value('division_id');
            $department = AssignArea::where('employee_profile_id',$overtime_application->employee_profile_id)->value('department_id');
            $section = AssignArea::where('employee_profile_id',$overtime_application->employee_profile_id)->value('section_id');
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
            $first_name = optional($overtime_application->employeeProfile->personalInformation)->first_name ?? null;
            $last_name = optional($overtime_application->employeeProfile->personalInformation)->last_name ?? null;
            return [
                'id' => $overtime_application->id,
                'reason' => $overtime_application->reason,
                'remarks' => $overtime_application->remarks,
                'purpose' => $overtime_application->purpose,
                'status' => $overtime_application->status,
                'overtime_letter' => $overtime_application->overtime_letter_of_request,
                'employee_id' => $overtime_application->employee_profile_id,
                'employee_name' => "{$first_name} {$last_name}" ,
                'division_head' =>$chief_name,
                'department_head' =>$head_name,
                'section_head' =>$supervisor_name,
                'division_name' => $overtime_application->employeeProfile->assignedArea->division->name ?? null,
                'department_name' => $overtime_application->employeeProfile->assignedArea->department->name ?? null,
                'section_name' => $overtime_application->employeeProfile->assignedArea->section->name ?? null,
                'unit_name' => $overtime_application->employeeProfile->assignedArea->unit->name ?? null,
                'date' => $overtime_application->date,
                'time' => $overtime_application->time,
                'logs' => $overtime_application->logs->map(function ($log) {
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
                        'overtime_application_id' => $log->overtime_application_id,
                        'action_by' => "{$first_name} {$last_name}" ,
                        'position' => $log->employeeProfile->assignedArea->designation->name ?? null,
                        'action' => $log->action,
                        'date' => $formatted_date,
                        'time' => $log->time,
                        'process' => $action
                    ];
                }),
                'activities' => $overtime_application->activities->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'overtime_application_id' => $activity->overtime_application_id,
                        'name' => $activity->name,
                        'quantity' => $activity->quantity,
                        'man_hour' => $activity->man_hour,
                        'period_covered' => $activity->period_covered,
                        'dates' => $activity->dates->map(function ($date) {
                            return [
                                'id' => $date->id,
                                'ovt_activity_id' =>$date->ovt_application_activity_id,
                                'time_from' => $date->time_from,
                                'time_to' => $date->time_to,
                                'date' => $date->date,
                                'dates' => $date->employees->map(function ($employee) {
                                    $first_name = optional($employee->employeeProfile->personalInformation)->first_name ?? null;
                                    $last_name = optional($employee->employeeProfile->personalInformation)->last_name ?? null;
                                return [
                                        'id' => $employee->id,
                                        'ovt_employee_id' =>$employee->ovt_application_datetime_id,
                                        'employee_id' => $employee->id,
                                        'employee_name' =>"{$first_name} {$last_name}",

                                    ];
                                }),

                            ];
                        }),
                    ];
                }),

            ];



        });

             return response()->json(['data' => $overtime_applications_result], Response::HTTP_OK);
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }

    }

    public function getEmployeeOvertimeTotal()
    {
        $employeeProfiles = EmployeeProfile::with(['overtimeCredits', 'personalInformation'])
        ->get();
        $employeeOvertimeTotals = $employeeProfiles->map(function ($employeeProfile) {
        $totalAddCredits = $employeeProfile->overtimeCredits->where('operation', 'add')->sum('overtime_hours');
        $totalDeductCredits = $employeeProfile->overtimeCredits->where('operation', 'deduct')->sum('overtime_hours');
        $totalOvertimeCredits = $totalAddCredits - $totalDeductCredits;
        return [
            'employee_id' => $employeeProfile->id,
            'employee_name' => $employeeProfile->personalInformation->first_name,
            'total_overtime_credits' => $totalOvertimeCredits,
        ];
        });

        return response()->json(['data' => $employeeOvertimeTotals], Response::HTTP_OK);
    }

    public function getEmployees()
    {
        $currentMonth = date('m');
        $currentYear = date('Y');

        $filteredEmployees = EmployeeProfile::with(['overtimeCredits', 'personalInformation']) // Eager load the 'overtimeCredits' and 'profileInformation' relationships
        ->get()
        ->filter(function ($employeeProfile) use ($currentMonth, $currentYear) {
            $totalAddCredits = $employeeProfile->overtimeCredits->where('operation', 'add')->sum('overtime_hours');
        $totalDeductCredits = $employeeProfile->overtimeCredits->where('operation', 'deduct')->sum('overtime_hours');

        $totalOvertimeCredits = $totalAddCredits - $totalDeductCredits;

        return $totalOvertimeCredits < 40 && $totalOvertimeCredits < 120;
        })
        ->map(function ($employeeProfile) {
            $totalAddCredits = $employeeProfile->overtimeCredits->where('operation', 'add')->sum('overtime_hours');
            $totalDeductCredits = $employeeProfile->overtimeCredits->where('operation', 'deduct')->sum('overtime_hours');

            $totalOvertimeCredits = $totalAddCredits - $totalDeductCredits;

            return [
                'employee_id' => $employeeProfile->id,
                'employee_name' => $employeeProfile->personalInformation->first_name, // Assuming 'name' is the field in the ProfileInformation model representing the employee name
                'total_overtime_credits' => $totalOvertimeCredits,
            ];
        });

            return response()->json(['data' => $filteredEmployees], Response::HTTP_OK);

    }

    public function store(Request $request)
    {
        try{
            // $user_id = Auth::user()->id;
            // $user = EmployeeProfile::where('id','=',$user_id)->first();
            $path="";
            if($request->hasFile('letter_of_request'))
            {
                foreach ($request->file('letter_of_request') as $file) {
                    $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    $uniqueFileName = $fileName . '_' . time() . '.' . $extension;
                    $folderName = 'Letter';
                    Storage::makeDirectory('public/' . $folderName);
                    $path = $file->storeAs('public/' . $folderName, $uniqueFileName);

                }

            }
            $overtime_application = OvertimeApplication::create([
                'employee_profile_id' => '1',
                'reference_number' => '123',
                'status' => 'applied',
                'purpose' => $request->purpose,
                'date' => date('Y-m-d'),
                'time' => date('H:i:s'),
                'overtime_letter_of_request' => $path
            ]);

            $ovt_id=$overtime_application->id;
            $activities = $request->input('activities');
            $quantities = $request->input('quantities');
            $manhours = $request->input('manhours');
            $periods = $request->input('periods');

            for ($i = 0; $i < count($activities); $i++) {
                $activity_application = OvtApplicationActivity::create([
                    'overtime_application_id' => $ovt_id,
                    'activity_name' => $activities[$i],
                    'quantity' => $quantities[$i],
                    'man_hour' => $manhours[$i],
                    'period_covered' => $periods[$i],
                ]);
            }

            $activity_id=$activity_application->id;
            $time_from = $request->input('time_from');
            $time_to = $request->input('time_to');
            $date = $request->input('date');


            for ($i = 0; $i < count($date); $i++) {
               $date_application = OvtApplicationDatetime::create([
                    'ovt_application_activity_id' => $activity_id,
                    'time_from' => $time_from[$i],
                    'time_to' => $time_to[$i],
                    'date' => $date[$i],
                ]);
            }

            $date_id=$date_application->id;
            $time_from = $request->input('time_from');
            $time_to = $request->input('time_to');
            $date = $request->input('date');


            $selectedEmployees = $request->input('employees');
            for ($i = 0; $i < count($selectedEmployees); $i++) {
                OvtApplicationEmployee::create([
                    'ovt_application_datetime_id' => $date_id,
                    'employee_profile_id' => $selectedEmployees[$i],
                ]);
            }

            $columnsString="";
            $process_name="Applied";
            $this->storeOvertimeApplicationLog($ovt_id,$process_name,$columnsString);
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function storeOvertimeApplicationLog($overtime_application_id,$process_name,$changedfields)
    {
        try {
            $user_id="1";

            $data = [
                'overtime_application_id' => $overtime_application_id,
                'action_by_id' => $user_id,
                'action' => $process_name,
                'date' => date('Y-m-d'),
                'time' => date('H:i:s'),
                'fields' => $changedfields
            ];

            $overtime_application_log = OvtApplicationLog::create($data);

            return $overtime_application_log;
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage(),'error'=>true]);
        }
    }
    public function declineOtApplication($id,Request $request)
    {
        try {
                    // $leave_application_id = $request->leave_application_id;

                    $overtime_applications = OvertimeApplication::where('id','=', $id)
                                                            ->first();
                if($overtime_applications)
                {
                        // $user_id = Auth::user()->id;
                        // $user = EmployeeProfile::where('id','=',$user_id)->first();
                        // $user_password=$user->password;
                        // $password=$request->password;
                        // if($user_password==$password)
                        // {
                            // if($user_id){
                                $overtime_application_log = new OvtApplicationLog();
                                $overtime_application_log->action = 'declined';
                                $overtime_application_log->overtime_application_id =$id;
                                $overtime_application_log->date = date('Y-m-d');
                                $overtime_application_log->time = date('h-i-s');
                                $overtime_application_log->action_by_id = '1';
                                $overtime_application_log->save();

                                $overtime_application = overtimeApplication::findOrFail($id);
                                $overtime_application->status = 'declined';
                                $overtime_application->update();
                                return response(['message' => 'Application has been sucessfully declined', 'data' => $overtime_application], Response::HTTP_CREATED);

                            // }
                        //  }
                }
            } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),  'error'=>true]);
        }
    }

    public function cancelOtApplication($id,Request $request)
    {
        try {
                    // $leave_application_id = $request->leave_application_id;
                    $overtime_application_id = '1';
                    $overtime_applications = overtimeApplication::where('id','=', $overtime_application_id)
                                                            ->first();
                if($overtime_applications)
                {
                        // $user_id = Auth::user()->id;
                        // $user = EmployeeProfile::where('id','=',$user_id)->first();
                        // $user_password=$user->password;
                        // $password=$request->password;
                        // if($user_password==$password)
                        // {
                        //     if($user_id){
                                $overtime_application_log = new OvtApplicationLog();
                                $overtime_application_log->action = 'cancel';
                                $overtime_application_log->overtime_application_id = $overtime_application_id;
                                $overtime_application_log->date = date('Y-m-d');
                                $overtime_application_log->action_by_id = '1';
                                $overtime_application_log->save();

                                $overtime_application = OvertimeApplication::findOrFail($overtime_application_id);
                                $overtime_application->status = 'cancelled';
                                $overtime_application->update();
                                return response(['message' => 'Application has been sucessfully cancelled', 'data' => $overtime_application_log], Response::HTTP_CREATED);

                        //     }
                        //  }
                }
            } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(),  'error'=>true]);
        }
    }
}
