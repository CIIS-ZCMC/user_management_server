<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Helpers\Helpers;
use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Resources\LeaveTypeResource;
use App\Http\Resources\MonetizationApplicationResource;
use App\Http\Resources\NotificationResource;
use App\Models\Division;
use App\Models\DocumentNumber;
use App\Models\EmployeeLeaveCredit;
use App\Models\LeaveType;
use App\Models\MonetizationApplication;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\LeaveType as ModelsLeaveType;
use App\Models\MoneApplicationLog;
use App\Models\Notifications;
use App\Models\Section;
use App\Models\Unit;
use App\Models\UserNotifications;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\EmployeeLeaveCreditLogs;
use App\Models\MonitizationPosting;
use Carbon\Carbon;

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

            /**
             * HR division
             * Only newly applied leave application
             */

            if (Helpers::getHrmoOfficer() === $employee_profile->id) {
                $employeeId = $employee_profile->id;
                $hrmo = ["applied","for recommending approval", "for approving approval", "approved", "declined by hrmo officer"];

                $mone_applications = MonetizationApplication::select('monetization_applications.*')
                    ->where(function ($query) use ($hrmo, $employeeId) {
                        $query->whereIn('monetization_applications.status', $hrmo)
                            ->where('monetization_applications.hrmo_officer', $employeeId);
                    })
                    ->groupBy(
                        'id',
                        'employee_profile_id',
                        'leave_type_id',
                        'reason',
                        'status',
                        'is_qualified',
                        'remarks',
                        'credit_value',
                        'attachment',
                        'attachment_size',
                        'attachment_path',
                        'hrmo_officer',
                        'recommending_officer',
                        'approving_officer',
                        'employee_oic_id',
                        'created_at',
                        'updated_at'
                    )
                    ->orderBy('created_at', 'desc')
                    ->get();

                // $leave_applications = collect($leave_applications)->filter(function ($leave_application) use ($employeeId) {
                //     // Keep the leave application if the status is "applied" or if the employee profile ID is not equal to $employeeId
                //     return $leave_application->status === "applied" || $leave_application->employee_profile_id !== $employeeId;
                // })->all();

                return response()->json([
                    'data' => MonetizationApplicationResource::collection($mone_applications),
                    'message' => 'Retrieve all leave monetization records.'
                ], Response::HTTP_OK);
            }

            $employeeId = $employee_profile->id;
            $recommending = ["for recommending approval", "for approving approval", "approved", "declined by recommending officer"];
            $approving = ["for approving approval", "approved", "declined by approving officer"];

            /**
             * Supervisor = for recommending, for approving, approved, de
             */
            $mone_applications = MonetizationApplication::select('monetization_applications.*')
                ->where(function ($query) use ($recommending, $approving, $employeeId) {
                    $query->whereIn('monetization_applications.status', $recommending)
                        ->where('monetization_applications.recommending_officer', $employeeId);
                })
                ->orWhere(function ($query) use ($recommending, $approving, $employeeId) {
                    $query->whereIn('monetization_applications.status', $approving)
                        ->where('monetization_applications.approving_officer', $employeeId);
                })
                ->groupBy(
                    'id',
                    'employee_profile_id',
                    'leave_type_id',
                    'reason',
                    'status',
                    'is_qualified',
                    'remarks',
                    'credit_value',
                    'attachment',
                    'attachment_size',
                    'attachment_path',
                    'hrmo_officer',
                    'recommending_officer',
                    'approving_officer',
                    'employee_oic_id',
                    'created_at',
                    'updated_at'
                )
                ->orderBy('created_at', 'desc')
                ->get();
            return response()->json([
                'data' => MonetizationApplicationResource::collection($mone_applications),
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
    public function userMoneApplication(Request $request)
    {
        try {
            $employee_profile = $request->user;

            $mone_applications = MonetizationApplication::where('employee_profile_id', $employee_profile->id)->get();
            $employeeCredit =  EmployeeLeaveCredit::where('employee_profile_id', $employee_profile->id)
                                ->whereIn('leave_type_id', [1, 2])
                                ->get();
            $monePosting = MonitizationPosting::where('created_by', $employee_profile->id)->get();
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
                'posting' =>$monePosting,
                'credits' => $result,
                'message' => 'Retrieve all leave application records.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    //old function
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

    //old function
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
            $user = $request->user;
            $employee_profile = $user;
            $cleanData['pin'] = strip_tags($request->pin);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }

            $position = $employee_profile->position();
            $status = '';
            $log_status = '';
            $monetization_application = MonetizationApplication::find($id);
            $message='';
            $next_approving=null;

            if (!$monetization_application) {
                return response()->json(['message' => "No application found record."], Response::HTTP_NOT_FOUND);
            }
            $employeeProfile = EmployeeProfile::find($monetization_application->employee_profile_id);
            switch ($monetization_application->status) {
                case 'applied':
                    if($employee_profile->id === $monetization_application->hrmo_officer){
                        $employee_profile = $request->user;
                        $process_name = "Approved by HRMO";
                        $monetization_application->update(['status' => 'for recommending approval']);
                        $message="HRMO";
                        $next_approving=$monetization_application->recommending_officer;

                        $mone_application_log = new MoneApplicationLog();
                        $mone_application_log->monetization_application_id = $monetization_application->id;
                        $mone_application_log->action_by_id =$employee_profile->id;
                        $mone_application_log->action = $process_name;
                        $mone_application_log->save();
                       
                    }else{
                        return response()->json([
                            'message' => 'You have no access to  approve this request.',
                        ], Response::HTTP_FORBIDDEN);
                    }
                    break;

                case 'for recommending approval':
                    if($employee_profile->id === $monetization_application->recommending_officer){
                    $process_name = "Approved by Recommending Officer";
                    $monetization_application->update(['status' => 'for approving approval']);
                    $message="Recommending Officer";
                    $next_approving=$monetization_application->approving_officer;

                    $mone_application_log = new MoneApplicationLog();
                    $mone_application_log->monetization_application_id = $monetization_application->id;
                    $mone_application_log->action_by_id =$employee_profile->id;
                    $mone_application_log->action = $process_name;
                    $mone_application_log->save();
                    }else{
                        return response()->json([
                            'message' => 'You have no access to  approve this request.',
                        ], Response::HTTP_FORBIDDEN);
                    }
                    break;

                case 'for approving approval':
                    if($employee_profile->id === $monetization_application->approving_officer){
                    $process_name = "Approved by Approving Officer";
                    $monetization_application->update(['status' => 'approved']);
                    $message="Approving Officer";

                    $mone_application_log = new MoneApplicationLog();
                    $mone_application_log->monetization_application_id = $monetization_application->id;
                    $mone_application_log->action_by_id =$employee_profile->id;
                    $mone_application_log->action = $process_name;
                    $mone_application_log->save();
                }else{
                    return response()->json([
                        'message' => 'You have no access to  approve this request.',
                    ], Response::HTTP_FORBIDDEN);
                }
                    break;
            }

            $employeeCredit =  EmployeeLeaveCredit::where('employee_profile_id', $monetization_application->employee_profile_id)
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
            if($monetization_application->status === 'approved'){

                //EMPLOYEE
                $title = "Monetization request approved";
                $description = "Your monetization request of has been approved by your " . $message . ".";
                
                
                $notification = Notifications::create([
                    "title" => $title,
                    "description" => $description,
                    "module_path" => '/monetize-leave',
                ]);
    
                $user_notification = UserNotifications::create([
                    'notification_id' => $notification->id,
                    'employee_profile_id' => $monetization_application->employee_profile_id,
                ]);
    
                Helpers::sendNotification([
                    "id" => Helpers::getEmployeeID($monetization_application->employee_profile_id),
                    "data" => new NotificationResource($user_notification)
                ]);
            }
            else
            {
                //NEXT APPROVING
                $notification = Notifications::create([
                    "title" =>  "New Monetization request",
                    "description" => $employeeProfile->personalInformation->name()." filed a new monetization request",
                    "module_path" => '/manage-leave-monetization',
                ]);
    
                $user_notification = UserNotifications::create([
                    'notification_id' => $notification->id,
                    'employee_profile_id' => $next_approving,
                ]);
    
                Helpers::sendNotification([
                    "id" => Helpers::getEmployeeID($next_approving),
                    "data" => new NotificationResource($user_notification)
                ]);

                //EMPLOYEE
                $title = "Monetization request approved";
                $description = "Your monetization request of has been approved by your " . $message . ".";
                
                
                $notification = Notifications::create([
                    "title" => $title,
                    "description" => $description,
                    "module_path" => '/monetize-leave',
                ]);
    
                $user_notification = UserNotifications::create([
                    'notification_id' => $notification->id,
                    'employee_profile_id' => $monetization_application->employee_profile_id,
                ]);
    
                Helpers::sendNotification([
                    "id" => Helpers::getEmployeeID($monetization_application->employee_profile_id),
                    "data" => new NotificationResource($user_notification)
                ]);
            }
            return response()->json([
                'data' => new MonetizationApplicationResource($monetization_application),
                'credits' => $result,
                'message' => "Successfully approved request for monetization"
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

            $cleanData['pin'] = strip_tags($request->pin);

            if ($employee_profile['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }

            $leave_type_code = strip_tags($request->code);

            $leave_type = LeaveType::where('code', $leave_type_code)->first();
            $credit = EmployeeLeaveCredit::where('leave_type_id', $leave_type->id)
                   ->where('employee_profile_id', $employee_profile->id)
                   ->first();


            if ($credit->total_leave_credits < 15) {
                return response()->json(['message' => "Insufficient leave credit to file a monetization."], Response::HTTP_BAD_REQUEST);
            }

            $hrmo_officer = Helpers::getHrmoOfficer();
            $recommending_officer = Division::where('area_id', 'HOPSS-DI-003')->first();
            $approving_officer = Division::where('area_id', 'OMCC-DI-001')->first();


            if($recommending_officer === null || $approving_officer === null || $hrmo_officer === null){
                return response()->json(['message' => "No recommending officer and/or supervising officer assigned."], Response::HTTP_BAD_REQUEST);
            }
            $cleanData = [];


            $cleanData['employee_profile_id'] = $employee_profile->id;
            $cleanData['leave_type_id'] = $leave_type->id;
            $cleanData['reason'] = strip_tags($request->reason);
            $cleanData['credit_value'] = strip_tags($request->credit_value);
            $cleanData['is_qualified'] = 1;
            $cleanData['status'] = 'applied';
            $cleanData['hrmo_officer'] = $hrmo_officer;
            $cleanData['recommending_officer'] = $recommending_officer->chief_employee_profile_id;
            $cleanData['approving_officer'] = $approving_officer->chief_employee_profile_id;

            $file = $request->file('attachment');
            $cleanData['attachment'] = $file->getClientOriginalName();
            $cleanData['attachment_size'] = $file->getSize();
            $cleanData['attachment_path'] = Helpers::checkSaveFile($file, 'leave_monetization');

            $currentYear = Carbon::now()->year;
            $existingApplications = MonetizationApplication::where('employee_profile_id', $employee_profile->id)
                ->whereYear('created_at', $currentYear)
                ->whereNotIn('status', ['declined'])
                ->exists();

            if ($existingApplications) {
                return response()->json(['message' =>"You have an existing application within the current year."], Response::HTTP_BAD_REQUEST);
            }

            $new_monetization = MonetizationApplication::create($cleanData);

            $previous_credit = $credit->total_leave_credits;

            $credit->update([
                'total_leave_credits' => $credit->total_leave_credits - $request->credit_value,
                'used_leave_credits' => $credit->used_leave_credits + $request->credit_value
            ]);

            $process_name = "Applied";
            // $this->storeMonetizationLog($new_monetization->id, $process_name, $employee_profile->id);
            // $this->storeMonetizationLog($new_monetization->id, $process_name, $employee_profile->id);

            $mone_application_log = new MoneApplicationLog();
            $mone_application_log->monetization_application_id = $new_monetization->id;
            $mone_application_log->action_by_id = $employee_profile->id;
            $mone_application_log->action = $process_name;
            $mone_application_log->save();

            EmployeeLeaveCreditLogs::create([
                'employee_leave_credit_id' => $credit->id,
                'previous_credit' => $previous_credit,
                'leave_credits' => $request->credit_value,
                'reason' => 'apply',
                'action' => 'deduct'
            ]);

            $employeeCredit =  EmployeeLeaveCredit::where('employee_profile_id', $employee_profile->id)
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

            
            $employeeProfile = EmployeeProfile::find($employee_profile->id);
            $title = "New Monetization request";
            $description = $employeeProfile->personalInformation->name()." filed a new monetization request";
            
            
            $notification = Notifications::create([
                "title" => $title,
                "description" => $description,
                "module_path" => '/manage-leave-monetization',
            ]);

            $user_notification = UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => $recommending_officer->chief_employee_profile_id,
            ]);

            Helpers::sendNotification([
                "id" => Helpers::getEmployeeID($recommending_officer->chief_employee_profile_id),
                "data" => new NotificationResource($user_notification)
            ]);

            return response()->json([
                'data' => new MonetizationApplicationResource($new_monetization),
                'credits' => $result,
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
            $declined_by = null;
            $cleanData['pin'] = strip_tags($request->pin);

            if ($employee_profile['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }

            $mone_application = MonetizationApplication::find($id);

            if (!$mone_application) {
                return response()->json(['message' => "No monetization application found."], Response::HTTP_NOT_FOUND);
            }
            $mone_application_hrmo = $mone_application->hrmo_officer;
            $mone_application_recommending = $mone_application->recommending_officer;
            $mone_application_approving = $mone_application->approving_officer;

            switch ($mone_application->status) {
                case 'applied':
                    if($employee_profile->id === $mone_application_hrmo){
                        $status = 'declined by hrmo officer';
                        $declined_by = "HR";

                       
                    }else{
                        return response()->json([
                            'message' => 'You have no access to  decline this request.',
                        ], Response::HTTP_FORBIDDEN);
                    }
                    break;
                case 'for recommending approval':
                    if($employee_profile->id === $mone_application_recommending){
                        $status = 'declined by recommending officer';
                        $declined_by = "Recommending officer";
                    // Helpers::pendingLeaveNotfication($mone_application->approving_officer, $mone_application->leaveType->name);
                    // Helpers::notifications(
                    //     $mone_application->employee_profile_id,
                    //     $mone_application->recommendingOfficer->personalInformation->name() . " has approved your " . $mone_application->leaveType->name . " request.",
                    //     $mone_application->leaveType->name
                    // );
                    }else{
                        return response()->json([
                            'message' => 'You have no access to  decline this request.',
                        ], Response::HTTP_FORBIDDEN);
                    }
                    break;
                case 'for approving approval':
                    if($employee_profile->id === $mone_application_approving){
                        $status = 'declined by approving officer';
                        $declined_by = "Approving officer";
                        // Helpers::notifications($mone_application->employee_profile_id, $message, $mone_application->leaveType->name);
                    }else{
                        return response()->json([
                            'message' => 'You have no access to  decline this request.',
                        ], Response::HTTP_FORBIDDEN);
                    }
                    break;
            }


            $mone_application->update([
                'status' => $status,
                'remarks' => strip_tags($request->remarks),
            ]);

            $employee_credit = EmployeeLeaveCredit::where('employee_profile_id', $mone_application->employee_profile_id)
            ->where('leave_type_id', $mone_application->leave_type_id)->first();

            $current_leave_credit = $employee_credit->total_leave_credits;
            $current_used_leave_credit = $employee_credit->used_leave_credits;

            $employee_credit->update([
                'total_leave_credits' => $current_leave_credit + $mone_application->credit_value,
                'used_leave_credits' => $current_used_leave_credit - $mone_application->credit_value,
            ]);

            EmployeeLeaveCreditLogs::create([
                'employee_leave_credit_id' => $employee_credit->id,
                'previous_credit' => $current_leave_credit,
                'leave_credits' => $mone_application->credit_value,
                'reason' => "declined",
                'action' => 'add'
            ]);

            MoneApplicationLog::create([
                'monetization_application_id' => $mone_application->id,
                'action_by_id' => $employee_profile->id,
                'action' => 'Declined'
            ]);


            $employeeCredit =  EmployeeLeaveCredit::where('employee_profile_id', $mone_application->employee_profile_id)
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

             //EMPLOYEE notification
             $title = "Monetization request declined";
             $description = "Your monetization request has been declined by your " . $declined_by . ".";
             
             
             $notification = Notifications::create([
                 "title" => $title,
                 "description" => $description,
                 "module_path" => '/monetize-leave',
             ]);
 
             $user_notification = UserNotifications::create([
                 'notification_id' => $notification->id,
                 'employee_profile_id' => $mone_application->employee_profile_id,
             ]);
 
             Helpers::sendNotification([
                 "id" => Helpers::getEmployeeID($mone_application->employee_profile_id),
                 "data" => new NotificationResource($user_notification)
             ]);

            return response()->json([
                'data' => new MonetizationApplicationResource($mone_application),
                'credits'=>$result,
                'message' => 'Declined leave application successfully.'
            ], Response::HTTP_OK);
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
            $mone_application_log->monetization_application_id = $mone_application_id;
            $mone_application_log->action_by_id = $user_id;
            $mone_application_log->action = $process_name;
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
            $cleanData['pin'] = strip_tags($request->pin);

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
            $cleanData['pin'] = strip_tags($request->pin);

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

    public function printLeaveForm($id)
    {
        try {
            $data = MonetizationApplication::with(['employeeProfile', 'leaveType', 'hrmoOfficer', 'recommendingOfficer', 'approvingOfficer'])->where('id', $id)->first();
            $vl_employee_credit = EmployeeLeaveCredit::where('leave_type_id', LeaveType::where('code', 'VL')->first()->id)
                                    ->where('employee_profile_id', $data->employee_profile_id)
                                    ->first();
            $sl_employee_credit = EmployeeLeaveCredit::where('leave_type_id', LeaveType::where('code', 'SL')->first()->id)
                                    ->where('employee_profile_id', $data->employee_profile_id)
                                    ->first();

            // return $data;
            $leave_type = MonetizationApplicationResource::collection(LeaveType::all());
            $my_leave_type = new LeaveTypeResource(LeaveType::find($data->leave_type_id));
            $hrmo_officer = Section::with(['supervisor'])->where('area_id', 'HOPPS-HRMO-DE-001')->first();

            // $employeeLeaveCredit = EmployeeLeaveCredit::with('employeeLeaveCreditLogs')
            //     ->where('employee_profile_id', $data->employee_profile_id)
            //     ->where('leave_type_id', $data->leave_type_id)
            //     ->first();

            // if ($employeeLeaveCredit) {
            //     $creditLogs = $employeeLeaveCredit->employeeLeaveCreditLogs;
            //     // Now you can work with $creditLogs
            // } else {
            //     // Handle the case when no matching record is found
            //     $creditLogs = null; // Or any other appropriate action
            // }
             //FETCH DOCUMENT DETAILS


             $document_details = [];

             $isMCC = Division::where('area_id', 'OMCC-DI-001')->where('chief_employee_profile_id', $data->employee_profile_id)->first();

             if (!$isMCC) {
                 //GET DIV ID FIRST
                 if ($data->country === 'Philippines') {
                     $div_id = Division::where('chief_employee_profile_id', $data->approvingOfficer->id)->first();
                     $document_details = DocumentNumber::where('division_id', $div_id->id)->where('is_abroad', 0)->first();
                 } else {
                     $document_details = DocumentNumber::where('division_id', 1)->where('is_abroad', 1)->first();
                 }
             } else {
                 $document_details = DocumentNumber::where('id', 6)->first();
             }

            // return view('leave_from.leave_application_form', compact('data', 'leave_type', 'hrmo_officer'));
            $is_monetization=true;
            $options = new Options();
            $options->set('isPhpEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->getOptions()->setChroot([base_path() . '/public/storage']);
            $html = view('leave_from.leave_application_form', compact('data', 'leave_type', 'my_leave_type', 'vl_employee_credit', 'sl_employee_credit', 'is_monetization', 'document_details'))->render();
            $dompdf->loadHtml($html);

            $dompdf->setPaper('Legal', 'portrait');
            $dompdf->render();
            $filename = 'LEAVE REPORT - (' . $data->employeeProfile->personalInformation->name() . ').pdf';

            // Use 'I' instead of 'D' to open in the browser
                $dompdf->stream($filename, array('Attachment' => false));
            // $dompdf->stream($filename);


            // if ($dompdf->loadHtml($html)) {
            // $dompdf->setPaper('Legal', 'portrait');
            // $dompdf->render();
            // $filename = 'Leave Application('. $data->employeeProfile->personalInformation->name() .').pdf';
            // $dompdf->stream($filename);
            // } else {
            //     return response()->json(['message' => 'Error loading HTML content', 'error' => true]);
            // }

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => true]);
        }
    }
}
