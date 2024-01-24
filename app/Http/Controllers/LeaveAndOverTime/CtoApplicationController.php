<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Helpers\Helpers;
use Illuminate\Support\Facades\DB;
use App\Models\CtoApplication;
use App\Http\Controllers\Controller;
use App\Http\Requests\CtoApplicationRequest;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Resources\CtoApplicationResource;
use App\Models\AssignArea;
use App\Models\CtoApplicationDate;
use App\Models\CtoApplicationLog;
use App\Models\Department;
use App\Models\Division;
use App\Models\EmployeeOvertimeCredit;
use App\Models\EmployeeOvertimeCreditLog;
use App\Models\OvtApplicationDatetime;
use App\Models\Section;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class CtoApplicationController extends Controller
{

    public function index(Request $request)
    {
        try{
            $employee_profile = $request->user;

            if(Helpers::getHrmoOfficer() === $employee_profile->id){
                $cto_applications = CtoApplication::all();

                return response()->json([
                    'data' => CtoApplicationResource::collection($cto_applications),
                    'message' => 'Retrieve all compensatory time of application records.'
                ], Response::HTTP_OK);
            }

            if(Helpers::getChiefOfficer() === $employee_profile->id){
                $cto_applications = [];
                $divisions = Division::all();

                foreach($divisions as $division){
                    if($division->code === 'OMCC') {
                        $cto_application_under_omcc = CtoApplication::join('employee_profile as emp', 'emp.', 'employee_profile_id')
                            ->join('assign_areas as aa', 'aa.employee_profile_id', 'emp.id')->where('aa.division_id', $division->id)->get();

                        $cto_applications = [...$cto_applications, $cto_application_under_omcc];
                        continue;
                    }
                    $cto_application_per_division_head = CtoApplication::where('employee_profile_id', $division->chief_employee_profile_id)->get();
                    $cto_applications = [...$cto_applications, $cto_application_per_division_head];
                }


                return response()->json([
                    'data' => CtoApplicationResource::collection($cto_applications),
                    'message' => 'Retrieve all compensatory time off application records.'
                ], Response::HTTP_OK);
            }

            $cto_applications = CtoApplication::all();

            return response()->json([
                'data' => CtoApplicationResource::collection($cto_applications),
                'message' => 'Retrieve all leave application records.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(CtoApplicationRequest $request)
    {
        try{
            $employee_profile = $request->user;
            $recommending_and_approving = Helpers::getRecommendingAndApprovingOfficer($employee_profile->assignedArea->findDetails(), $employee_profile->id);
            $hrmo_officer= Helpers::getHrmoOfficer();
            $cto_applications = [];

            $reason = [];
            $failed = [];

            if(!$employee_profile){
                return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
            }

            $cleanData = [];


            foreach($request->cto_applications as $value){

                $employee_credit = EmployeeOvertimeCredit::where('employee_profile_id', $employee_profile->id)->first();

                if($employee_credit->earn_credit_by_hour < $value['applied_credits']){
                    $failed[] = $value;
                    $reason[] = 'Insuficient overtime credit.';
                    continue;
                }

                $cleanData = [];
                $cleanData['employee_profile_id'] = $employee_profile->id;
                $cleanData['hrmo_officer'] = $hrmo_officer;
                $cleanData['recommending_officer'] = $recommending_and_approving['recommending_officer'];
                $cleanData['approving_officer'] = $recommending_and_approving['approving_officer'];
                $cleanData['status'] = 'Applied';

                foreach($value->all() as $key => $cto){

                    if($cto === 'null'){
                        $cleanData[$key] = $cto;
                        continue;
                    }
                    $cleanData[$key] = strip_tags($cto);
                }

                $cto_application = CtoApplication::create($cleanData);
                $employee_credit->update(['earn_credit_by_hour' => $employee_credit->earn_credit_by_hour - $cleanData['applied_credits']]);
                $current_overtime_credit = $employee_credit->earn_credit_by_hour;
                EmployeeOvertimeCreditLog::create([
                    'employee_ot_credit_id' => $employee_credit->id,
                    'cto_application_id' => $cto_application->id,
                    'action'=>'CTO',
                    'previous_overtime_hours' => $current_overtime_credit,
                    'hours' =>  $cleanData['applied_credits'],
                    'is_deduction' => true
                ]);
                CtoApplicationLog::create([
                    'action_by' => $employee_profile->id,
                    'cto_application_id' => $cto_application->id,
                    'action' => 'Applied'
                ]);

                $cto_applications[] = $cto_application;
            }

            return response()->json([
                'data' => new CtoApplicationResource($cto_applications),
                'message' => 'Retrieve all Compensatory time off application records.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id, Request $request)
    {
        try{
            $cto_applications = CtoApplication::find($id);

            return response()->json([
                'data' => new CtoApplicationResource($cto_applications),
                'message' => 'Retrieve compensatory application record.'
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

            $cto_application = CtoApplication::find($id);

            $cto_application -> update([
                'status' => 'Declined',
                'reason' => strip_tags($request->reason)
            ]);

            $employee_credit = EmployeeOvertimeCredit::where('employee_profile_id', $cto_application->employee_profile_id)->first();

            $current_overtime_credit = $employee_credit->earn_credit_by_hour;

            $employee_credit->update([
                'earn_credit_by_hour' => $current_overtime_credit + $cto_application->applied_credits
            ]);

            EmployeeOvertimeCreditLog::create([
                'employee_ot_credit_id' => $employee_credit->id,
                'cto_application_id' => $cto_application->id,
                'action'=>'Declined',
                'previous_overtime_hours' => $current_overtime_credit,
                'hours' =>  $cto_application->applied_credits,
                'is_deduction' => false
            ]);

            CtoApplicationLog::create([
                'action_by' => $employee_profile->id,
                'cto_application_id' => $cto_application->id,
                'action' => 'Declined'
            ]);

            return response()->json([
                'data' => new CtoApplicationResource($cto_application),
                'message' => 'Retrieve compensatory time off application record.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    



}
