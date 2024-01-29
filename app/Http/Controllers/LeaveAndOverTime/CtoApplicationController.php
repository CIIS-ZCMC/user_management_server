<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Helpers\Helpers;
use App\Http\Resources\EmployeeOvertimeCreditResource;
use DateTime;
use Illuminate\Support\Facades\DB;
use App\Models\CtoApplication;
use App\Http\Controllers\Controller;
use App\Http\Requests\CtoApplicationRequest;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Resources\CtoApplicationResource;
use App\Models\CtoApplicationLog;
use App\Models\Division;
use App\Models\EmployeeOvertimeCredit;
use App\Models\EmployeeOvertimeCreditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class CtoApplicationController extends Controller
{

    public function index(Request $request)
    {
        try {
            $employee_profile   = $request->user;
            $recommending = ["for recommending approval", "for approving approval", "approved", "declined"];
            $approving = ["for approving approval", "approved", "declined"];
            $position = $employee_profile->position();
            $employeeId = $employee_profile->id;

            /**
             * Division Head [approving, recommending] - applications of assigned area
             *  - recommending => [for recommending approval, for approving approval, approved, declined]
             *  - approving => [ for approving approval, approved, declined]
             * 
             * Department Head [recommending] - applications of assigned area
             *  - recommending => [for recommending approval, for approving approval, approved, declined]
             * 
             * Section Supervisor [recommending] - applications of assigned area
             *  - recommending => [for recommending approval, for approving approval, approved, declined]
             * 
             */
            
           
             /** FOR NORMAL EMPLOYEE */
            if($employee_profile->position() === null){
                $cto_application = CtoApplication::where('employee_profile_id', $employee_profile->id)->get();
                 
                return response()->json([
                    'data' => CtoApplicationResource::collection($cto_application),
                    'message' => 'Retrieved all CTO application'
                ], Response::HTTP_OK);
            }

            $cto_application = CtoApplication::select('cto_applications.*')
                ->where(function ($query) use ($recommending, $approving, $employeeId) {
                    $query->whereIn('cto_applications.status', $recommending)
                        ->where('cto_applications.recommending_officer', $employeeId);
                })
                ->orWhere(function ($query) use ($recommending, $approving, $employeeId) {
                    $query->whereIn('cto_applications.status', $approving)
                        ->where('cto_applications.approving_officer', $employeeId);
                })
                ->groupBy(
                    'cto_applications.id',
                    'cto_applications.date_from',
                    'cto_applications.date_to',
                    'cto_applications.time_from',
                    'cto_applications.time_to',
                    'cto_applications.status',
                    'cto_applications.purpose',
                    'cto_applications.personal_order_file',
                    'cto_applications.personal_order_path',
                    'cto_applications.personal_order_size',
                    'cto_applications.certificate_of_appearance',
                    'cto_applications.certificate_of_appearance_path',
                    'cto_applications.certificate_of_appearance_size',
                    'cto_applications.recommending_officer',
                    'cto_applications.approving_officer',
                    'cto_applications.remarks',
                    'cto_applications.employee_profile_id',
                    'user_management_db.cto_applications.created_at',
                    'user_management_db.cto_applications.updated_at',
                )
                ->get();

            return response()->json([
                'data' => CtoApplicationResource::collection($cto_application),
                'message' => 'Retrieved all official business application'
            ], Response::HTTP_OK);
            
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create(Request $request) {
        try {

            $user = $request->user;
            $sql = CtoApplication::where('employee_profile_id', $user->id)->get();
            $employeeCredit = EmployeeOvertimeCredit::where('employee_profile_id', $user->id)->get();
            return response()->json(['data' => CtoApplicationResource::collection($sql),
                                    'employee_credit' => EmployeeOvertimeCreditResource::collection($employeeCredit)], Response::HTTP_OK);

        } catch (\Throwable $th) {
            
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approved($id, PasswordApprovalRequest $request)
    {
        try {
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password . env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $cto_application = CtoApplication::find($id);

            if (!$cto_application) {
                return response()->json(["message" => "No leave application with id " . $id], Response::HTTP_NOT_FOUND);
            }

            $position = $employee_profile->position();
            $status = '';

            switch ($cto_application->status) {
                case 'Applied':
                    if ($position === null || str_contains($position->position, 'Unit')) {
                        return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
                    }
                    $status = 'For approving approval';
                    $cto_application->update(['status' => $status]);
                    break;
                case 'For approving approval':
                    $employee_sector_and_area = $cto_application->employeeProfile->assignedArea;
                    $division_details = null;

                    switch ($employee_sector_and_area->findDetails()->sector) {
                        case "Division":
                            $division_details = $employee_sector_and_area->division;
                            break;
                        case "Department":
                            $division_details = $employee_sector_and_area->department->division;
                            break;
                        case "Section":
                            if ($employee_sector_and_area->section->department_id !== null) {
                                $division_details = $employee_sector_and_area->section->department->division;
                                break;
                            }
                            $division_details = $employee_sector_and_area->section->division;
                            break;
                        case "":
                            if ($employee_sector_and_area->unit->section->department_id !== null) {
                                $division_details = $employee_sector_and_area->unit->section->department->division;
                                break;
                            }
                            $division_details = $employee_sector_and_area->unit->section->division;
                            break;

                    }

                    if ($division_details->chief_employee_profile_id !== $employee_profile->id) {
                        return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
                    }

                    $status = 'Approved';
                    $cto_application->update(['status' => $status]);
                    break;
            }

            CtoApplicationLog::create([
                'action_by' => $employee_profile->id,
                'cto_application_id' => $cto_application->id,
                'action' => $status
            ]);

            return response()->json([
                'data' => new CtoApplicationResource($cto_application),
                'message' => 'Successfully approved application.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(CtoApplicationRequest $request)
    {
        try {
            $employee_profile = $request->user;
            $recommending_and_approving = Helpers::getRecommendingAndApprovingOfficer($employee_profile->assignedArea->findDetails(), $employee_profile->id);
            $cto_applications = [];

            $reason = [];
            $failed = [];

            if (!$employee_profile) {
                return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
            }

            $cleanData = [];
          
            foreach ($request->all() as $key => $value) {
                if (empty($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                if (is_int($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                if (is_array($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            
            foreach (json_decode($request->cto_applications) as $key=>$value) {
               
                if ($employee_credit->earn_credit_by_hour < $value->applied_credits) {
                    $failed[] = $value;
                    $reason[] = 'Insuficient overtime credit.';
                    continue;
                }
            
                $cleanData['employee_profile_id'] = $employee_profile->id;
                $cleanData['date'] = $value->date;
                $cleanData['applied_credits'] = $value->applied_credits;
                $cleanData['purpose'] = $value->purpose;
                $cleanData['remarks'] = $value->remarks;
                $cleanData['status'] = 'Applied';
                $cleanData['recommending_officer'] = $recommending_and_approving['recommending_officer'];
                $cleanData['approving_officer'] = $recommending_and_approving['approving_officer'];

                $credits = $value->applied_credits;
                $cto_application = CtoApplication::create($cleanData);
                
                $employee_credit = EmployeeOvertimeCredit::where('employee_profile_id', $employee_profile->id)->first();
                $current_overtime_credit = $employee_credit->earned_credit_by_hour;
                $employee_credit->update(['earned_credit_by_hour' => DB::raw("earned_credit_by_hour - $credits")]);

                $logs =  EmployeeOvertimeCreditLog::create([
                    'employee_ot_credit_id' => $employee_credit->id,
                    'cto_application_id' => $cto_application->id,
                    'action' => 'CTO',
                    'previous_overtime_hours' => $current_overtime_credit,
                    'hours' => $value->applied_credits
                ]);

                CtoApplicationLog::create([
                    'action_by' => $employee_profile->id,
                    'cto_application_id' => $cto_application->id,
                    'action' => 'Applied'
                ]);

                $cto_applications[] = $cto_application;
            }

            if (count($failed) === count(json_decode($request->cto_applications, true))) {
                return response()->json([
                    'failed' => $failed,
                    'reason' => $reason,
                    'message' => 'Failed to register all compensatory time off applications.'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (count($failed) > 0) {
                return response()->json([
                    'data' => new CtoApplicationResource($cto_applications),
                    'failed' => $failed,
                    'reason' => $reason,
                    'message' => count($cto_applications) . ' of ' . count($request->cto_applications) . ' registered and ' . count($failed) . ' failed.'
                ], Response::HTTP_BAD_REQUEST);
            }

            return response()->json([
                'data' => CtoApplicationResource::collection($cto_applications),
                'message' => 'Retrieve all Compensatory time off application records.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id, Request $request)
    {
        try {
            $cto_applications = CtoApplication::find($id);

            return response()->json([
                'data' => new CtoApplicationResource($cto_applications),
                'message' => 'Retrieve compensatory application record.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function declined($id, PasswordApprovalRequest $request)
    {
        try {
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password . env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $cto_application = CtoApplication::find($id);

            $cto_application->update([
                'status' => 'Declined',
                'reason' => strip_tags($request->reason)
            ]);

            $employee_credit = EmployeeOvertimeCredit::where('employee_profile_id', $cto_application->employee_profile_id)->first();

            $current_overtime_credit = $employee_credit->earn_credit_by_hour;

            $employee_credit->update([
                'earn_credit_by_hour' => DB::raw("earn_credit_by_hour + $cto_application->applied_credits")
            ]);

            EmployeeOvertimeCreditLog::create([
                'employee_ot_credit_id' => $employee_credit->id,
                'cto_application_id' => $cto_application->id,
                'action' => 'Declined',
                'previous_overtime_hours' => $current_overtime_credit,
                'hours' => $cto_application->applied_credits
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
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
