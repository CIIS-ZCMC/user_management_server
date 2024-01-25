<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Helpers\Helpers;
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
            $employee_profile = $request->user;

            $is_division_head_or_oic = Division::where('chief_employee_profile_id', $employee_profile->id)
                ->orWhere('oic_employee_profile_id', $employee_profile->id)->first();

            /**
             * For Division Head
             */
            if ($is_division_head_or_oic !== null) {
                $cto_applications = [];

                $cto_applications = CtoApplication::where('approving_officer', $is_division_head_or_oic->chief_employee_profile_id)
                    ->orWhere('recommending_officer', $is_division_head_or_oic->chief_employee_profile_id)->get();

                return response()->json([
                    'data' => CtoApplicationResource::collection($cto_applications),
                    'message' => 'Retrieve all compensatory time off application records.'
                ], Response::HTTP_OK);
            }

            $position = $employee_profile->position();

            /**
             * For Employee Applications
             */
            if (!$position) {
                $cto_applications = CtoApplication::where('employee_profile_id', $employee_profile->id)->get();

                return response()->json([
                    'data' => CtoApplicationResource::collection($cto_applications),
                    'message' => 'Retrieve all compensatory time off application records.'
                ], Response::HTTP_OK);
            }

            if (str_contains($position->position, "Unit")) {
                return response()->json(['message' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
            }

            /**
             * For Department Head
             */
            if (str_contains($position->position, 'Department') && $position->position !== "Training Officer") {
                $cto_applications = CtoApplication::where('recommending_officer', $position->area->head_employee_profile_id)->get();

                return response()->json([
                    'data' => CtoApplicationResource::collection($cto_applications),
                    'message' => 'Retrieve all compensatory time off application records.'
                ], Response::HTTP_OK);
            }

            /**
             * For Section Head
             */
            $cto_applications = CtoApplication::where('recommending_officer', $position->area->supervisor_employee_profile_id)->get();

            return response()->json([
                'data' => CtoApplicationResource::collection($cto_applications),
                'message' => 'Retrieve all compensatory time off application records.'
            ], Response::HTTP_OK);
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


            foreach ($request->cto_applications as $value) {

                $employee_credit = EmployeeOvertimeCredit::where('employee_profile_id', $employee_profile->id)->first();

                if ($employee_credit->earn_credit_by_hour < $value['applied_credits']) {
                    $failed[] = $value;
                    $reason[] = 'Insuficient overtime credit.';
                    continue;
                }

                $cleanData = [];
                $cleanData['employee_profile_id'] = $employee_profile->id;
                $cleanData['recommending_officer'] = $recommending_and_approving['recommending_officer'];
                $cleanData['approving_officer'] = $recommending_and_approving['approving_officer'];
                $cleanData['status'] = 'Applied';

                foreach ($value->all() as $key => $cto) {

                    if ($cto === 'null') {
                        $cleanData[$key] = $cto;
                        continue;
                    }
                    $cleanData[$key] = strip_tags($cto);
                }

                $credits = $cleanData['applied_credits'];
                $cto_application = CtoApplication::create($cleanData);
                $current_overtime_credit = $employee_credit->earn_credit_by_hour;
                $employee_credit->update(['earn_credit_by_hour' => DB::raw("earn_credit_by_hour - $credits")]);

                EmployeeOvertimeCreditLog::create([
                    'employee_ot_credit_id' => $employee_credit->id,
                    'cto_application_id' => $cto_application->id,
                    'action' => 'CTO',
                    'previous_overtime_hours' => $current_overtime_credit,
                    'hours' => $cleanData['applied_credits'],
                ]);

                CtoApplicationLog::create([
                    'action_by' => $employee_profile->id,
                    'cto_application_id' => $cto_application->id,
                    'action' => 'Applied'
                ]);

                $cto_applications[] = $cto_application;
            }

            if (count($failed) === count($request->cto_applications)) {
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
                'data' => new CtoApplicationResource($cto_applications),
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
