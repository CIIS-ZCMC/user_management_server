<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Helpers\Helpers;
use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Resources\MonetizationApplicationResource;
use App\Models\Division;
use App\Models\EmployeeLeaveCredit;
use App\Models\LeaveType;
use App\Models\MonetizationApplication;
use App\Http\Controllers\Controller;
use App\Models\EmployeeProfile;
use App\Models\LeaveType as ModelsLeaveType;
use App\Models\MoneApplicationLog;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MonetizationApplicationController extends Controller
{
    private $CONTROLLER_NAME = "MonetizationApplicationController";

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $employee_profile = $request->user;
            $mone_applications = MonetizationApplication::all();
            $employeeCredit = EmployeeLeaveCredit::where('employee_profile_id', $employee_profile->id)
                ->whereIn('leave_type_id', [1, 2])
                ->get();

            $result = [];

            foreach ($employeeCredit as $leaveCredit) {
                $leaveType = $leaveCredit->leaveType->name;
                $totalCredits = $leaveCredit->total_leave_credits;
                $usedCredits = $leaveCredit->used_leave_credits;

                $result[] = [
                    'leave_type_name' => $leaveType,
                    'total_leave_credits' => $totalCredits,
                    'used_leave_credits' => $usedCredits
                ];
            }
            return response()->json([
                'data' => MonetizationApplicationResource::collection($mone_applications),
                'credits' => $result,
                'message' => "Monetization Application list."
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userBalance(Request $request)
    {
        try {
            $employee_profile = $request->user;
            $employeeCredit = EmployeeLeaveCredit::where('employee_profile_id', $employee_profile->id)->where('name', 'Vacation Leave')->orWhere('name', 'Sick Leave')->get();
            $result = [];

            foreach ($employeeCredit as $leaveCredit) {
                $leaveType = $leaveCredit->leaveType->name;
                $totalCredits = $leaveCredit->total_leave_credits;
                $usedCredits = $leaveCredit->used_leave_credits;

                $result[] = [
                    'leave_type_name' => $leaveType,
                    'total_leave_credits' => $totalCredits,
                    'used_leave_credits' => $usedCredits
                ];
            }

            return response()->json([

                'data' => $result,
                'message' => 'Retrieve all leave application records.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function getMoneApplications(Request $request)
    {
        try {
            $status = $request->status;
            $mone_applications = [];

            if ($status == 'for-approval-supervisor') {
                $mone_applications = MonetizationApplication::where('status', '=', 'for-approval-supervisor');
            } else if ($status == 'for-approval-head') {
                $mone_applications = MonetizationApplication::where('status', '=', 'for-approval-head');
            } else if ($status == 'declined') {
                $mone_applications = MonetizationApplication::where('status', '=', 'declined');
            } else if ($status == 'approved') {
                $mone_applications = MonetizationApplication::where('status', '=', 'approved');
            } else {
                $mone_applications = MonetizationApplication::where('status', '=', $status);
            }


            if (isset($request->search)) {
                $search = $request->search;
                $mone_applications = $mone_applications->where('reference_number', 'like', '%' . $search . '%');

                $mone_applications = isset($search) && $search;
            }

            return response()->json(['data' => $mone_applications], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'getMoneApplications', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateStatus(Request $request)
    {
        try {
            $user_id = Auth::user()->id;
            $user = EmployeeProfile::where('id', '=', $user_id)->first();
            $user_password = $user->password;
            $password = $request->password;
            if ($user_password == $password) {
                $message_action = '';
                $action = '';
                $new_status = '';
                $status = $request->status;

                if ($status == 'for-approval-supervisor') {
                    $action = 'Aprroved by Supervisor';
                    $new_status = 'for-approval-head';
                    $message_action = "Approved";
                } else if ($status == 'for-approval-head') {
                    $action = 'Aprroved by Department Head';
                    $new_status = 'approved';
                    $message_action = "Approved";
                } else {
                    $action = $status;
                }
                $mone_application_id = $request->monetization_application_id;
                $mone_applications = MonetizationApplication::where('id', '=', $mone_application_id)
                    ->first();
                if ($mone_applications) {

                    $mone_application_log = new MoneApplicationLog();
                    $mone_application_log->action = $action;
                    $mone_application_log->mone_application_id = $mone_application_id;
                    $mone_application_log->action_by = $user_id;
                    $mone_application_log->date = date('Y-m-d');
                    $mone_application_log->save();

                    $mone_application = MonetizationApplication::findOrFail($mone_application_id);
                    $mone_application->status = $new_status;
                    $mone_application->update();

                    return response(['message' => 'Application has been sucessfully ' . $message_action, 'data' => $mone_application], Response::HTTP_CREATED);
                }
            }
        } catch (\Exception $e) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'updateStatus', $e->getMessage());
            return response()->json(['message' => $e->getMessage(), 'error' => true], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approvedApplication($id, Request $request)
    {
        try {
            $monetization_application = MonetizationApplication::find($id);

            if (!$monetization_application) {
                return response()->json(['message' => "NO application found record."], Response::HTTP_NOT_FOUND);
            }

            if ($monetization_application->status === 'Applied') {
                $employee_profile = $request->user;

                if ($employee_profile->id !== $monetization_application->recommending->id) {
                    return response()->json(['message' => "Must be approved by the recommending officer."], Response::HTTP_FORBIDDEN);
                }

                $process_name = "Approved by recommending officer";
                $monetization_application->update(['status' => 'for approving officer']);
                $this->storeMonetizationLog($monetization_application->id, $process_name, $employee_profile->id);
            }

            if ($monetization_application->status === 'Approved by recommending officer') {
                $employee_profile = $request->user;

                if ($employee_profile->id !== $monetization_application->approving->id) {
                    return response()->json(['message' => "Must be approved by the recommending officer."], Response::HTTP_FORBIDDEN);
                }

                $process_name = "Approved by approving officer";
                $monetization_application->update(['status' => 'approved']);
                $this->storeMonetizationLog($monetization_application->id, $process_name, $employee_profile->id);
            }

            return response()->json([
                'data' => new MonetizationApplicationResource($monetization_application),
                'message' => "You're request has been filed."
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'approvedApplication', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $employee_profile = $request->user;
            $leave_type_code = strip_tags($request->code);

            $leave_type = LeaveType::where('code', $leave_type_code)->first();
            $credit = EmployeeLeaveCredit::where('leave_type_id', $leave_type->id)->first();

            if ($credit->total_leave_credits < 15) {
                return response()->json(['message' => "Insufficient vacation leave credit to file a monitization."], Response::HTTP_BAD_REQUEST);
            }

            $recommending_officer = Section::where('code', 'HOPPS')->first();
            $approvince_officer = Division::where('code', 'OMCC')->first();

            $cleanData = [];
            $cleanData['employee_profile_id'] = $employee_profile->id;
            $cleanData['leave_type_id'] = $leave_type->id;
            $cleanData['reason'] = strip_tags($request->reason);
            $cleanData['credit_value'] = strip_tags($request->credit_value);
            $cleanData['date'] = date('Y-m-d');
            $cleanData['time'] = date('H:i:s');
            $cleanData['recommending_officer'] = $recommending_officer->chief_employee_profile_id;
            $cleanData['approving_officer'] = $approvince_officer->chief_employee_profile_id;

            try {
                $fileName = Helpers::checkSaveFile($request->attachment, 'monetization/files');
                if (is_string($fileName)) {
                    $cleanData['attachment'] = $request->attachment === null  || $request->attachment === 'null' ? null : $fileName;
                }

                if (is_array($fileName)) {
                    $in_valid_file = true;
                    $cleanData['attachment'] = null;
                }
            } catch (\Throwable $th) {
            }

            $new_monitization = MonetizationApplication::create($cleanData);

            $process_name = "Applied";
            $this->storeMonetizationLog($new_monitization->id, $process_name, $employee_profile->id);

            return response()->json([
                'data' => new MonetizationApplicationResource($new_monitization),
                'message' => "You're request has been filed."
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function declineMoneApplication($id, Request $request)
    {
        try {
            $employee_profile = $request->user;
            $mone_application = MonetizationApplication::find($id);

            if (!$mone_application) {
                return response()->json(['message' => "No monetization application found."], Response::HTTP_NOT_FOUND);
            }

            $mone_application->update(['status' => 'declined']);
            $this->storeMonetizationLog($id, 'declined', $employee_profile->id);

            return response(['message' => 'Application has been sucessfully declined', 'data' => $mone_application], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'declineMoneApplication', $e->getMessage());
            return response()->json(['message' => $e->getMessage(),  'error' => true], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function updateMoneApplication($id, Request $request)
    {
        try {

            $employee_profile = $request->user;
            $leave_type_code = strip_tags($request->code);

            $leave_type = LeaveType::where('code', $leave_type_code)->first();
            $credit = EmployeeLeaveCredit::where('leave_type_id', $leave_type->id)->first();

            if ($credit->total_leave_credits < 15) {
                return response()->json(['message' => "Insufficient vacation leave credit to file a monitization."], Response::HTTP_BAD_REQUEST);
            }

            $monitization = MonetizationApplication::find($id);

            $cleanData = [];
            $cleanData['employee_profile_id'] = $employee_profile->id;
            $cleanData['leave_type_id'] = $leave_type->id;
            $cleanData['reason'] = strip_tags($request->reason);
            $cleanData['credit_value'] = strip_tags($request->credit_value);

            try {
                $fileName = Helpers::checkSaveFile($request->attachment, 'monetization/files');
                if (is_string($fileName)) {
                    $cleanData['attachment'] = $request->attachment === null  || $request->attachment === 'null' ? null : $fileName;
                }

                if (is_array($fileName)) {
                    $in_valid_file = true;
                    $cleanData['attachment'] = null;
                }
            } catch (\Throwable $th) {
            }

            $monitization->update($cleanData);

            $process_name = "Applied";
            $this->storeMonetizationLog($monitization->id, $process_name, $employee_profile->id);

            return response()->json([
                'data' => new MonetizationApplicationResource($monitization),
                'message' => "Monetization application updated."
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'updateMoneApplication', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function storeMonetizationLog($mone_application_id, $process_name, $user_id)
    {
        try {

            $mone_application_log = new MoneApplicationLog();
            $mone_application_log->mone_application_id = $mone_application_id;
            $mone_application_log->action_by = $user_id;
            $mone_application_log->action = $process_name;
            $mone_application_log->status = "applied";
            $mone_application_log->date = date('Y-m-d');
            $mone_application_log->time =  date('H:i:s');
            $mone_application_log->save();

            return $mone_application_log;
        } catch (\Exception $e) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'storeMonetizationLog', $e->getMessage());
            return response()->json(['message' => $e->getMessage(), 'error' => true], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function cancelmoneApplication($id, AuthPinApprovalRequest $request)
    {
        try {
            $employee_profile = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($employee_profile['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $mone_application = MonetizationApplication::find($id);

            $mone_application->update(['status' => 'cancelled']);

            $this->storeMonetizationLog($id, 'cancelled', $employee_profile->id);


            return response([
                'data' => $mone_application,
                'message' => 'Application has been sucessfully cancelled'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'cancelmoneApplication', $e->getMessage());
            return response()->json(['message' => $e->getMessage(),  'error' => true], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id, AuthPinApprovalRequest $request)
    {
        try {
            $employee_profile = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($employee_profile['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $mone_application = MonetizationApplication::find($id);
            MoneApplicationLog::where(['monetization_application_id', $mone_application->id])->delete();
            $mone_application->delete();


            return response([
                'message' => 'Application has been sucessfully deleted.'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'cancelmoneApplication', $e->getMessage());
            return response()->json(['message' => $e->getMessage(),  'error' => true], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
