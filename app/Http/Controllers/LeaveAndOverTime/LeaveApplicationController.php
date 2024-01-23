<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use Carbon\Carbon;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\LeaveApplication;
use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveApplicationRequest;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Resources\LeaveApplicationResource;
use App\Models\AssignArea;
use App\Models\Division;
use App\Models\EmployeeLeaveCredit;
use App\Models\EmployeeLeaveCreditLogs;
use App\Models\EmployeeProfile;
use App\Models\LeaveApplicationLog;
use App\Models\LeaveApplicationRequirement;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class LeaveApplicationController extends Controller
{

    public function index(Request $request)
    {
        try{
            $employee_profile = $request->user;

            if(Helpers::getHrmoOfficer() === $employee_profile->id){
                $leave_types = LeaveApplication::all();

                return response()->json([
                    'data' => LeaveApplicationResource::collection($leave_types),
                    'message' => 'Retrieve all leave application records.'
                ], Response::HTTP_OK);
            }

            if(Helpers::getChiefOfficer() === $employee_profile->id){
                $leave_applications = [];
                $divisions = Division::all();

                foreach($divisions as $division){
                    if($division->code === 'OMCC') {
                        $leave_application_under_omcc = LeaveApplication::join('employee_profile as emp', 'emp.', 'employee_profile_id')
                            ->join('assign_areas as aa', 'aa.employee_profile_id', 'emp.id')->where('aa.division_id', $division->id)->get();

                        $leave_applications = [...$leave_applications, $leave_application_under_omcc];
                        continue;
                    }
                    $leave_application_per_division_head = LeaveApplication::where('employee_profile_id', $division->chief_employee_profile_id)->get();
                    $leave_applications = [...$leave_applications, $leave_application_per_division_head];
                }


                return response()->json([
                    'data' => LeaveApplicationResource::collection($leave_applications),
                    'message' => 'Retrieve all leave application records.'
                ], Response::HTTP_OK);
            }


            $leave_types = LeaveApplication::all();

            return response()->json([
                'data' => LeaveApplicationResource::collection($leave_types),
                'message' => 'Retrieve all leave application records.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(LeaveApplicationRequest $request)
    {
        try{
            $employee_profile = $request->user;
            $recommending_and_approving = Helpers::getRecommendingAndApprovingOfficer($employee_profile->assignedArea->findDetails(), $employee_profile->id);
            $hrmo_officer= Helpers::getHrmoOfficer();
            $leave_applications = [];

            $reason = [];
            $failed = [];

            foreach($request->leave_applications as $value){

                $employee_credit = EmployeeLeaveCredit::where('employee_profile_id', $employee_profile->id)
                    ->where('leave_type_id', $value->leave_type_id)->first();

                if($employee_credit->total_leave_credits < $value['applied_credits']){
                    $failed[] = $value;
                    $reason[] = 'Insuficient leave credit.';
                    continue;
                }

                $cleanData = [];
                $cleanData['employee_profile_id'] = $employee_profile->id;
                $cleanData['hrmo_officer'] = $hrmo_officer;
                $cleanData['recommending_officer'] = $recommending_and_approving['recommending_officer'];
                $cleanData['approving_officer'] = $recommending_and_approving['approving_officer'];
                $cleanData['status'] = 'Applied';

                foreach($value->all() as $key => $leave){
                    if($key === 'user' || $key === 'attachments') continue;
                    if($leave === 'null'){
                        $cleanData[$key] = $leave;
                        continue;
                    }
                    $cleanData[$key] = strip_tags($leave);
                }

                $leave_application = LeaveApplication::create($cleanData);
                $employee_credit->update(['total_leave_credits' => $employee_credit->total_leave_credits - $cleanData['applied_credits']]);

                if ($value->hasFile('requirements')) {
                    foreach ($value->file('requirements') as $key => $file) {
                        $fileName=pathinfo($file->attachment->getClientOriginalName(), PATHINFO_FILENAME);
                        $size = filesize($file->attachment);
                        $file_name_encrypted = Helpers::checkSaveFile($file->attachment, '/requirements');

                        LeaveApplicationRequirement::create([
                            'leave_application_id' => $leave_application->id,
                            'file_name' => $fileName,
                            'name' => $file->name,
                            'path' => $file_name_encrypted,
                            'size' => $size,
                        ]);
                    }
                }

                LeaveApplicationLog::create([
                    'action_by' => $employee_profile->id,
                    'leave_application_id' => $leave_application->id,
                    'action' => 'Applied'
                ]);

                $leave_applications[] = $leave_application;
            }

            if(count($failed) === count($request->leave_applications)){
                return response()->json([
                    'data' => LeaveApplicationResource::collection($leave_application),
                    'reason' => $reason,
                    'message' => 'Application request failed reason of failure.'
                ], Response::HTTP_OK);
            }

            /**
             * This return is inteded for having some failed registration of request with reason of insufficient credits.
             */
            if(count($failed) > 0 && count($failed) !== count($request->leave_applications)){
                return response()->json([
                    'data' => LeaveApplicationResource::collection($leave_application),
                    'failed_request' => $failed,
                    'message' => 'Some request has successfully registered.'
                ], Response::HTTP_OK);
            }

            return response()->json([
                'data' => LeaveApplicationResource::collection($leave_application),
                'message' => 'Retrieve all leave application records.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id, Request $request)
    {
        try{
            $leave_types = LeaveApplication::find($id);

            return response()->json([
                'data' => new LeaveApplicationResource($leave_types),
                'message' => 'Retrieve leave application record.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function declined($id, PasswordApprovalRequest $request)
    {
        try{
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $leave_application = LeaveApplication::find($id);

            $leave_application -> update([
                'status' => 'Declined',
                'reason' => strip_tags($request->reason)
            ]);

            $employee_credit = EmployeeLeaveCredit::where('employee_profile_id', $leave_application->employee_profile_id)
                ->where('leave_type_id', $leave_application->leave_type_id)->first();

            $current_leave_credit = $employee_credit->total_leave_credits;

            $employee_credit->update([
                'total_leave_credits' => $current_leave_credit + $leave_application->leave_credits
            ]);

            EmployeeLeaveCreditLogs::create([
                'employee_leave_credit_id' => $employee_credit->id,
                'previous_credit' => $current_leave_credit,
                'leave_credits' => $leave_application->leave_credits
            ]);

            LeaveApplicationLog::create([
                'action_by' => $employee_profile->id,
                'leave_application_id' => $leave_application->id,
                'action' => 'Declined'
            ]);

            return response()->json([
                'data' => new LeaveApplicationResource($leave_application),
                'message' => 'Retrieve leave application record.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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
                                    $hrmo = EmployeeProfile::where('id',$leave_application->hrmo_officer_id)->first();
                            $recommending = EmployeeProfile::where('id',$leave_application->recommending_officer_id)->first();
                            $approving = AssignArea::where('employee_profile_id',$leave_application->employee_profile_id)->value('section_id');

                            $recommending_name=null;
                            $recommending_position=null;
                            $recommending_code=null;
                            $approving_name=null;
                            $approving_position=null;
                            $approving_code=null;
                            $hr_name=null;
                            $hr_position=null;
                            $hr_code=null;

                            if($hrmo)
                            {
                                    $hr_name = $hrmo->last_name . ', ' . $hrmo->last_name;
                                    $hr_position = $hrmo->assignedArea->designation->name ?? null;
                                    $hr_code = $hrmo->assignedArea->designation->code ?? null;

                            }
                            if($recommending)
                            {
                                    $recommending_name = $recommending->last_name . ', ' . $recommending->first_name;
                                    $recommending_position = $recommending->assignedArea->designation->name ?? null;
                                    $recommending_code = $recommending->assignedArea->designation->code ?? null;

                            }
                            if($approving)
                            {
                                    $approving_name = $approving->last_name . ', ' . $approving->first_name;
                                    $approving_position = $approving->assignedArea->designation->name ?? null;
                                    $approving_code = $approving->assignedArea->designation->code ?? null;

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
                                        'employee_id' => $leave_application->employeeProfile->employee_id,
                                        'employee_name' => "{$first_name} {$last_name}" ,
                                        'position_code' => $leave_application->employeeProfile->assignedArea->designation->code ?? null,
                                        'position_name' => $leave_application->employeeProfile->assignedArea->designation->name ?? null,
                                        'date_created' => $leave_application->date,
                                        'recommending_name' =>$recommending_name,
                                        'recommending_position' =>$recommending_position,
                                        'recommending_code' =>$recommending_code,
                                        'hr_name' =>$hr_name,
                                        'hr_position' =>$hr_position,
                                        'hr_code' =>$hr_code,
                                        'approving_name' =>$approving_name,
                                        'approving_position' =>$approving_position,
                                        'approving_code' =>$approving_code,
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
