<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Http\Controllers\UmisAndEmployeeManagement\EmployeeProfileController;
use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Resources\LeaveTypeResource;
use App\Http\Resources\MyApprovedLeaveApplicationResource;
use App\Models\Department;
use App\Models\Division;
use App\Models\EmployeeCreditLog;
use App\Models\EmployeeOvertimeCredit;
use App\Models\LeaveType;
use App\Models\Section;
use App\Models\Unit;
use Carbon\Carbon;
use App\Helpers\Helpers;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\LeaveApplication;
use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveApplicationRequest;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Resources\LeaveApplicationResource;
use App\Models\EmployeeLeaveCredit;
use App\Models\EmployeeLeaveCreditLogs;
use App\Models\EmployeeProfile;
use App\Models\LeaveApplicationLog;
use App\Models\LeaveApplicationRequirement;
use App\Models\OfficialBusiness;
use App\Models\OfficialTime;
use Illuminate\Support\Str;

class LeaveApplicationController extends Controller
{
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
                $hrmo = ["applied", "for recommending approval", "approved", "declined by hrmo officer"];
                $recommending = ["for recommending approval", "for approving approval", "approved", "declined by recommending officer"];

                $leave_applications = LeaveApplication::select('leave_applications.*')
                    ->where(function ($query) use ($hrmo, $employeeId) {
                        $query->whereIn('leave_applications.status', $hrmo)
                            ->where('leave_applications.hrmo_officer', $employeeId);
                    })
                    ->orWhere(function ($query) use ($recommending, $employeeId) {
                        $query->whereIn('leave_applications.status', $recommending)
                            ->where('leave_applications.recommending_officer', $employeeId);
                    })
                    ->groupBy(
                        'id',
                        'employee_profile_id',
                        'leave_type_id',
                        'date_from',
                        'date_to',
                        'country',
                        'city',
                        'is_outpatient',
                        'illness',
                        'is_masters',
                        'is_board',
                        'is_commutation',
                        'applied_credits',
                        'status',
                        'remarks',
                        'without_pay',
                        'reason',
                        'hrmo_officer',
                        'recommending_officer',
                        'approving_officer',
                        'employee_oic_id',
                        'created_at',
                        'updated_at'
                    )
                    ->orderBy('created_at', 'desc')
                    ->get();

                $leave_applications = collect($leave_applications)->filter(function ($leave_application) use ($employeeId) {
                    // Keep the leave application if the status is "applied" or if the employee profile ID is not equal to $employeeId
                    return $leave_application->status === "applied" || $leave_application->employee_profile_id !== $employeeId;
                })->all();

                return response()->json([
                    'data' => LeaveApplicationResource::collection($leave_applications),
                    'message' => 'Retrieve all leave application records.'
                ], Response::HTTP_OK);
            }

            $employeeId = $employee_profile->id;
            $recommending = ["for recommending approval", "for approving approval", "approved", "declined by recommending officer"];
            $approving = ["for approving approval", "approved", "declined by approving officer"];

            /**
             * Supervisor = for recommending, for approving, approved, de
             */
            $leave_applications = LeaveApplication::select('leave_applications.*')
                ->where(function ($query) use ($recommending, $approving, $employeeId) {
                    $query->whereIn('leave_applications.status', $recommending)
                        ->where('leave_applications.recommending_officer', $employeeId);
                })
                ->orWhere(function ($query) use ($recommending, $approving, $employeeId) {
                    $query->whereIn('leave_applications.status', $approving)
                        ->where('leave_applications.approving_officer', $employeeId);
                })
                ->groupBy(
                    'id',
                    'employee_profile_id',
                    'leave_type_id',
                    'date_from',
                    'date_to',
                    'country',
                    'city',
                    'is_outpatient',
                    'illness',
                    'is_masters',
                    'is_board',
                    'is_commutation',
                    'applied_credits',
                    'status',
                    'remarks',
                    'without_pay',
                    'reason',
                    'hrmo_officer',
                    'recommending_officer',
                    'approving_officer',
                    'employee_oic_id',
                    'created_at',
                    'updated_at'
                )
                ->orderBy('created_at', 'desc')
                ->get();


            // if (Helpers::getChiefOfficer() === $employee_profile->id) {
            //     $leave_applications = [];
            //     $divisions = Division::all();

            //     foreach ($divisions as $division) {
            //         if ($division->code === 'OMCC') {
            //             $leave_application_under_omcc = LeaveApplication::join('employee_profile as emp', 'emp.', 'employee_profile_id')
            //                 ->join('assign_areas as aa', 'aa.employee_profile_id', 'emp.id')->where('aa.division_id', $division->id)
            //                 ->where('recommending_officer', $division->chief_employee_profile_id)->get();

            //             $leave_applications = [...$leave_applications, ...$leave_application_under_omcc];
            //             continue;
            //         }
            //         $leave_application_per_division_head = LeaveApplication::where('for approving_officer')->where('approving_officer', $division->chief_employee_profile_id)->get();
            //         $leave_applications = [...$leave_applications, ...$leave_application_per_division_head];
            //     }

            //     return response()->json([
            //         'data' => LeaveApplicationResource::collection($leave_applications),
            //         'message' => 'Retrieve all leave application records.'
            //     ], Response::HTTP_OK);
            // }

            // /**
            //  * For employee that has position
            //  * Only for approving application status
            //  */

            // $position = $employee_profile->position();
            // if ($position !== null && $position['position'] !== 'Unit Head' && !str_contains($position['position'], 'OIC')) {
            //     $leave_applications = LeaveApplication::where('recommending_officer', $employee_profile->id)->get();
            //     $approving_applications = LeaveApplication::where('approving_officer', $employee_profile->id)->get();
            //     $leave_applications = [...$leave_applications, ...$approving_applications];

            //     return response()->json([
            //         'data' => LeaveApplicationResource::collection($leave_applications),
            //         'message' => 'Retrieve all leave application records.'
            //     ], Response::HTTP_OK);
            // }

            // $leave_applications = LeaveApplication::where('employee_profile_id', $employee_profile->id)->get();

            return response()->json([
                'data' => LeaveApplicationResource::collection($leave_applications),
                'message' => 'Retrieve all leave application records.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approvedLeaveRequest(Request $request)
    {
        try{
            $employee_profile = $request->user;
            $assigned_area = $employee_profile->assignedArea->findDetails();
            $division_id = null;

            switch($assigned_area['sector'])
            {
                case "Division":
                    $division_id = $assigned_area['details']->id;
                    break;
                case "Department":
                    $division_id = Department::find($assigned_area['details']->id)->division_id;
                    break;
                case "Section":
                    $section = Section::find($assigned_area['details']->id);

                    if($section->department_id === null){
                        $division_id = $section->division_id;
                        break;
                    }

                    $division_id = $section->department->division_id;
                    break;
                case "Unit":
                    $section = Unit::find($assigned_area['details']->id)->section;
                    $division_id = $section->department->division_id;
                    break;
            }

            if($division_id === null)
            {
                return response()->json(['message' => "GG"], Response::HTTP_OK);
            }

            $leave_applications = [];

            $units_leave_applications = LeaveApplication::select("leave_applications.*")
                ->join('employee_profiles as ep', 'leave_applications.employee_profile_id', 'ep.id')
                ->join('assigned_areas as aa', 'ep.id', 'aa.employee_profile_id')
                ->join('units as u', 'aa.unit_id', 'u.id')
                ->join('sections as s', 'u.section_id', 's.id')
                ->join('departments as d', 's.department_id', 'd.id')
                ->join('divisions as dv', 'd.division_id', 'dv.id')
                ->select('leave_applications.*')
                ->where('dv.id', $division_id)
                ->get();

            $sections_leave_applications = LeaveApplication::select("leave_applications.*")
                ->join('employee_profiles as ep', 'leave_applications.employee_profile_id', 'ep.id')
                ->join('assigned_areas as aa', 'ep.id', 'aa.employee_profile_id')
                ->join('sections as s', 'aa.section_id', 's.id')
                ->join('departments as d', 's.department_id', 'd.id')
                ->join('divisions as dv', 'd.division_id', 'dv.id')
                ->select('leave_applications.*')
                ->where('dv.id', $division_id)
                ->get();

            $departments_leave_applications = LeaveApplication::select("leave_applications.*")
                ->join('employee_profiles as ep', 'leave_applications.employee_profile_id', 'ep.id')
                ->join('assigned_areas as aa', 'ep.id', 'aa.employee_profile_id')
                ->join('departments as d', 'aa.department_id', 'd.id')
                ->join('divisions as dv', 'd.division_id', 'dv.id')
                ->select('leave_applications.*')
                ->where('dv.id', $division_id)
                ->get();

            $divisions_leave_applications = LeaveApplication::select("leave_applications.*")
                ->join('employee_profiles as ep', 'leave_applications.employee_profile_id', 'ep.id')
                ->join('assigned_areas as aa', 'ep.id', 'aa.employee_profile_id')
                ->join('divisions as dv', 'aa.division_id', 'dv.id')
                ->select('leave_applications.*')
                ->where('dv.id', $division_id)
                ->get();

            $leave_applications = [
                ...$units_leave_applications,
                ...$sections_leave_applications,
                ...$departments_leave_applications,
                ...$divisions_leave_applications
            ];

            return response()->json([
                'data' => LeaveApplicationResource::collection($leave_applications),
                'message' => 'Retrieve list.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function myApprovedLeaveApplication(Request $request)
    {
        try{
            $employee_profile = $request->user;
            $leave_applications = LeaveApplication::where('status', 'approved')
                ->where('employee_profile_id', $employee_profile->id)->get();

            return response()->json([
                'data' => MyApprovedLeaveApplicationResource::collection($leave_applications),
                'message' => 'Retrieve list.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getEmployees()
    {
        try {

            $leaveCredits = EmployeeLeaveCredit::with(['employeeProfile.personalInformation', 'leaveType'])->get()->groupBy('employee_profile_id');
            $response = [];
            foreach ($leaveCredits as $employeeProfileId => $leaveCreditGroup) {
                $employeeDetails = $leaveCreditGroup->first()->employeeProfile->personalInformation->name();
                $leaveCreditData = [];
                foreach ($leaveCreditGroup as $leaveCredit) {

                    $leaveCreditData[$leaveCredit->leaveType->name] = $leaveCredit->total_leave_credits;
                }

                // Fetch 'CTO' credit from EmployeeOvertimeCredit
                $ctoCredit = EmployeeOvertimeCredit::where('employee_profile_id', $employeeProfileId)->value('earned_credit_by_hour');

                // Add 'CTO' credit to the leaveCreditData
                $leaveCreditData['CTO'] = $ctoCredit;

                $employeeResponse = [
                    'id' => $employeeProfileId,
                    'name' => $employeeDetails,
                    'employee_id' =>  $leaveCreditGroup->first()->employeeProfile->employee_id,
                ];
                $employeeResponse = array_merge($employeeResponse, $leaveCreditData);
                $response[] = $employeeResponse;
            }

            return ['data' => $response];
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAllEmployees()
    {
        try {
            $employee_profiles = EmployeeProfile::all();
            $data = [];
            foreach ($employee_profiles as $employee) {
                $data[] = [
                    'id' => $employee->id,
                    'name' => $employee->name(),
                ];
            }

            return response()->json([
                'data' => $data,
                'message' => 'List of employees retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getLeaveTypes()
    {
        try {
            $LeaveTypes = LeaveType::where('is_special', '0')->get();

            return response()->json([
                'data' => LeaveTypeResource::collection($LeaveTypes),
                'message' => 'list of special leave type retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateCredit(AuthPinApprovalRequest $request)
    {
        try {
            $employee_profile = $request->user;
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            foreach ($request->credits as $credit) {
                $employeeId = $request->employee_id;
                $leaveTypeId = $credit['leave_id'];

                $leaveCredit = EmployeeLeaveCredit::where('employee_profile_id', $employeeId)
                    ->where('leave_type_id', $leaveTypeId)
                    ->firstOrFail();

                $leaveCredit->total_leave_credits = $credit['credit_value'];
                $leaveCredit->save();
            }

            $overtimeCredit = EmployeeOvertimeCredit::where('employee_profile_id', $request->employee_id)->first();
            $overtimeCredit->earned_credit_by_hour = $request->cto;
            $overtimeCredit->save();

            EmployeeCreditLog::create([
                'employee_profile_id' => $employeeId,
                'action_by' => $employee_profile->id,
                'action' => 'add'
            ]);

            $updatedLeaveCredits = EmployeeLeaveCredit::with(['employeeProfile.personalInformation', 'leaveType'])
                ->where('employee_profile_id', $request->employee_id)
                ->get()
                ->groupBy('employee_profile_id');



            $response = [];

            foreach ($updatedLeaveCredits as $employeeProfileId => $leaveCreditGroup) {
                $employeeDetails = $leaveCreditGroup->first()->employeeProfile->personalInformation->name();
                $leaveCreditData = [];

                foreach ($leaveCreditGroup as $leaveCredit) {
                    $leaveCreditData[$leaveCredit->leaveType->name] = $leaveCredit->total_leave_credits;
                }
                // Fetch 'CTO' credit from EmployeeOvertimeCredit
                $ctoCredit = EmployeeOvertimeCredit::where('employee_profile_id', $employeeProfileId)->value('earned_credit_by_hour');

                // Add 'CTO' credit to the leaveCreditData
                $leaveCreditData['CTO'] = $ctoCredit;
                $employeeResponse = [
                    'id' => $employeeProfileId,
                    'name' => $employeeDetails,
                ];

                $employeeResponse = array_merge($employeeResponse, $leaveCreditData);
                $response[] = $employeeResponse;
            }

            return response()->json(['message' => 'Leave credits updated successfully', 'data' => $response,], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function addCredit(AuthPinApprovalRequest $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            foreach ($request->credits as $credit) {
                $newLeaveCredit = new EmployeeLeaveCredit([
                    'employee_profile_id' => $request->employee_id,
                    'leave_type_id' => $credit['leave_id'], // Adjust the key if needed
                    'total_leave_credits' => (float)$credit['credit_value'],
                    // 'created_at' => now(), // Adjust as needed
                    // 'updated_at' => now(), // Adjust as needed
                ]);

                $newLeaveCredit->save();

                // // Assuming you have a 'logs' attribute in your request
                // EmployeeLeaveCreditLogs::create([
                //     'employee_leave_credit_id' => $newLeaveCredit->id,
                //     'previous_credit' => 0.0, // Assuming initial value is 0
                //     'leave_credits' => (float)$credit['credit_value'],
                // ]);
            }
            /// Fetch updated leave credits only for the specific employee
            $updatedLeaveCredits = EmployeeLeaveCredit::with(['employeeProfile.personalInformation', 'leaveType'])
                ->where('employee_profile_id', $request->employee_id)
                ->get()
                ->groupBy('employee_profile_id');

            $response = [];

            foreach ($updatedLeaveCredits as $employeeProfileId => $leaveCreditGroup) {
                $employeeDetails = $leaveCreditGroup->first()->employeeProfile->personalInformation->name();
                $leaveCreditData = [];

                foreach ($leaveCreditGroup as $leaveCredit) {
                    $leaveCreditData[$leaveCredit->leaveType->name] = $leaveCredit->total_leave_credits;
                }

                $employeeResponse = [
                    'id' => $employeeProfileId,
                    'name' => $employeeDetails,
                ];

                $employeeResponse = array_merge($employeeResponse, $leaveCreditData);
                $response[] = $employeeResponse;
            }


            return response()->json(['message' => 'Leave credits added successfully', 'data' => $response,], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approved($id, AuthPinApprovalRequest $request)
    {
        try {
            $user = $request->user;
            $employee_profile = $user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $leave_application = LeaveApplication::find($id);

            if (!$leave_application) {
                return response()->json(["message" => "No leave application with id " . $id], Response::HTTP_NOT_FOUND);
            }

            $position = $employee_profile->position();
            $status = '';
            $log_status = '';

            switch ($leave_application->status) {
                case 'applied':

                    if (Helpers::getHrmoOfficer() !== $employee_profile->id) {
                        return response()->json(['message' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
                    }
                    $status = 'for recommending approval';
                    $log_status = 'Approved by HRMO'; 
                    $leave_application->update(['status' => $status]);
                    Helpers::pendingLeaveNotfication($leave_application->recommending_officer, $leave_application->leaveType->name);
                    Helpers::notifications(
                        $leave_application->employee_profile_id, 
                        "HR has approved your ".$leave_application->leaveType->name." request.", 
                        $leave_application->leaveType->name);
                    break;
                case 'for recommending approval':
                    if ($position === null || str_contains($position['position'], 'Unit')) {
                        return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
                    }
                    $status = 'for approving approval';
                    $log_status = 'Approved by Recommending Officer';
                    $leave_application->update(['status' => $status]);
                    Helpers::pendingLeaveNotfication($leave_application->approving_officer, $leave_application->leaveType->name);
                    Helpers::notifications(
                        $leave_application->employee_profile_id, 
                        $leave_application->recommendingOfficer->personalInformation->name()." has approved your ".$leave_application->leaveType->name." request.", 
                        $leave_application->leaveType->name);
                    break;
                case 'for approving approval':
                    $approving =  Helpers::getRecommendingAndApprovingOfficer($employee_profile->assignedArea->findDetails(), $leave_application->employee_profile_id)['approving_officer'];
                    if ($approving !== $employee_profile->id) {
                        return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
                    }
                    $status = 'approved';
                    $log_status = 'Approved by Approving Officer';
                    $leave_application->update(['status' => $status]);
                    $from = Carbon::parse($leave_application->date_from)->format('F d, Y');
                    $to = Carbon::parse($leave_application->date_to)->format('F d, Y');
                    $message = "Your ".$leave_application->leaveType->name." request with date from ".$from." to ".$to." has been approved.";
                    Helpers::notifications($leave_application->employee_profile_id, $message, $leave_application->leaveType->name);
                    break;
            }

            $employee_profile = $leave_application->employeeProfile;

            LeaveApplicationLog::create([
                'action_by' => $employee_profile->id,
                'leave_application_id' => $leave_application->id,
                'action' => $log_status
            ]);

            return response()->json([
                'data' =>  new LeaveApplicationResource($leave_application),
                'message' => 'Successfully approved application.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function userLeaveApplication(Request $request)
    {
        try {
            $employee_profile = $request->user;

            $leave_applications = LeaveApplication::where('employee_profile_id', $employee_profile->id)->get();
            $employeeCredit = EmployeeLeaveCredit::where('employee_profile_id', $employee_profile->id)->get();
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
                'data' => LeaveApplicationResource::collection($leave_applications),
                'credits' => $result,
                'message' => 'Retrieve all leave application records.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(LeaveApplicationRequest $request)
    {
        try {
            $employee_profile = $request->user;
            $recommending_and_approving = Helpers::getRecommendingAndApprovingOfficer($employee_profile->assignedArea->findDetails(), $employee_profile->id);
            $hrmo_officer = Helpers::getHrmoOfficer();

            if($hrmo_officer === null || $recommending_and_approving === null || $recommending_and_approving['recommending_officer'] === null || $recommending_and_approving['approving_officer'] === null){
                return response()->json(['message' => 'No recommending officer and/or supervising officer assigned.'], Response::HTTP_FORBIDDEN);
            }

            $cleanData = [];
            $result = [];

            $start = Carbon::parse($request->date_from);
            $end = Carbon::parse($request->date_to);

            $currentDate = Carbon::now();
            $twoMonthsAhead = $currentDate->copy()->addMonths(2);
            
            if ($start->greaterThan($twoMonthsAhead)) {
                return response()->json(['message' => "Filing 2 months ahead is not allowed."], Response::HTTP_FORBIDDEN);
            }

            $daysDiff = $start->diffInDays($end) + 1;

            $leave_type = LeaveType::find($request->leave_type_id);

            if($leave_type->code === 'SL' && $leave_type->file_after !== null){
                $daysDiff = Carbon::now()->diffInDays($end);

                if ($daysDiff > $leave_type->file_after) {
                    return response()->json(['message' => "Filing of application must be ".$leave_type->file_after." days after the return of employee."], Response::HTTP_FORBIDDEN);
                }
            }

            $employeeId = $employee_profile->id;

            $overlapExists = Helpers::hasOverlappingRecords($start, $end, $employeeId);

            if ($overlapExists) {
                return response()->json(['message' => 'You already have an application for the same dates.'], Response::HTTP_FORBIDDEN);
            } else {
                if ($leave_type->is_special) {
                    if ($leave_type->period < $daysDiff) {
                        return response()->json(['message' => 'Exceeds days entitled for ' . $leave_type->name], Response::HTTP_FORBIDDEN);
                    }

                    $cleanData['applied_credits'] = $daysDiff;
                    $cleanData['employee_profile_id'] = $employee_profile->id;
                    $cleanData['hrmo_officer'] = $hrmo_officer;

                    // if($request->employee_oic_id !== "null" && $request->employee_oic_id !== null ){
                    //     $cleanData['employee_oic_id'] = (int) strip_tags($request->employee_oic_id);
                    // }

                    $isMCC = Division::where('code', 'OMCC')->where('chief_employee_profile_id', $employee_profile->id)->first();

                    if (!$isMCC) {
                        $cleanData['recommending_officer'] = $recommending_and_approving['recommending_officer'];
                        $cleanData['approving_officer'] = $recommending_and_approving['approving_officer'];
                    }

                    $cleanData['status'] = 'applied';

                    foreach ($request->all() as $key => $leave) {

                        if (is_bool($leave)) {
                            $cleanData[$key] = $leave === 0 ? false : true;
                        }
                        if (is_array($leave)) {
                            $cleanData[$key] = $leave;
                            continue;
                        }
                        if ($key === 'user' || $key === 'requirements')
                            continue;
                        if ($leave === 'null') {
                            $cleanData[$key] = $leave;
                            continue;
                        }
                        $cleanData[$key] = strip_tags($leave);
                    }

                    $leave_application = LeaveApplication::create($cleanData);
                    Helpers::pendingLeaveNotfication($cleanData['hrmo_officer'], $leave_type->name);
                   
                    if ($request->requirements) {
                        $index = 0;
                        $requirements_name = $request->requirements_name;

                        foreach ($request->file('requirements') as $key => $file) {
                            $fileName = $file->getClientOriginalName();
                            $size = filesize($file);
                            $file_name_encrypted = Helpers::checkSaveFile($file, '/requirements');

                            LeaveApplicationRequirement::create([
                                'leave_application_id' => $leave_application->id,
                                'file_name' => $fileName,
                                'name' => $requirements_name[$index],
                                'path' => $file_name_encrypted,
                                'size' => $size,
                            ]);
                            $index++;
                        }
                    }

                    LeaveApplicationLog::create([
                        'action_by' => $employee_profile->id,
                        'leave_application_id' => $leave_application->id,
                        'action' => 'Applied'
                    ]);
                } else {
                    $employee_credit = EmployeeLeaveCredit::where('employee_profile_id', $employee_profile->id)
                        ->where('leave_type_id', $request->leave_type_id)->first();


                    //  return response()->json(['message' => $request->without_pay == 0 && $employee_credit->total_leave_credits < $daysDiff], 401);
                    if ($request->without_pay == 0 && $employee_credit->total_leave_credits < $daysDiff) {
                        return response()->json(['message' => 'Insufficient leave credits.'], Response::HTTP_BAD_REQUEST);
                    } else {

                        $cleanData['applied_credits'] = $daysDiff;
                        $cleanData['employee_profile_id'] = $employee_profile->id;
                        $cleanData['hrmo_officer'] = $hrmo_officer;

                        // if($request->employee_oic_id !== "null" && $request->employee_oic_id !== null ){
                        //     $cleanData['employee_oic_id'] = (int) strip_tags($request->employee_oic_id);
                        // }

                        $isMCC = Division::where('code', 'OMCC')->where('chief_employee_profile_id', $employee_profile->id)->first();

                        if (!$isMCC) {
                            $cleanData['recommending_officer'] = $recommending_and_approving['recommending_officer'];
                            $cleanData['approving_officer'] = $recommending_and_approving['approving_officer'];
                        }

                        $cleanData['status'] = 'applied';

                        foreach ($request->all() as $key => $leave) {
                            if (is_bool($leave)) {
                                $cleanData[$key] = $leave === 0 ? false : true;
                            }
                            if (is_array($leave)) {
                                $cleanData[$key] = $leave;
                                continue;
                            }
                            if ($key === 'user' || $key === 'requirements')
                                continue;
                            if ($leave === 'null') {
                                $cleanData[$key] = $leave;
                                continue;
                            }
                            $cleanData[$key] = strip_tags($leave);
                        }

                        $leave_application = LeaveApplication::create($cleanData);
                        
                        Helpers::pendingLeaveNotfication($cleanData['hrmo_officer'], $leave_type->name);
    
                        if ($request->without_pay == 0) {
                            $previous_credit = $employee_credit->total_leave_credits;

                            $employee_credit->update([
                                'total_leave_credits' => $employee_credit->total_leave_credits - $daysDiff,
                                'used_leave_credits' => $employee_credit->used_leave_credits + $daysDiff
                            ]);


                            if(LeaveType::find($request->leave_type_id)->code === 'FL' ){
                                $vlLeaveTypeId = LeaveType::where('code', 'VL')->first()->id;

                                $employee_credit_vl = EmployeeLeaveCredit::where('employee_profile_id', $employee_profile->id)
                                    ->where('leave_type_id', $vlLeaveTypeId)->first();

                                $previous_credit_vl = $employee_credit_vl->total_leave_credits;

                                $employee_credit_vl->update([
                                    'total_leave_credits' => $employee_credit_vl->total_leave_credits - $daysDiff,
                                    'used_leave_credits' => $employee_credit_vl->used_leave_credits + $daysDiff
                                ]);

                                EmployeeLeaveCreditLogs::create([
                                    'employee_leave_credit_id' => $employee_credit->id,
                                    'previous_credit' => $previous_credit_vl,
                                    'leave_credits' => $daysDiff,
                                    'reason'=>'apply'
                                ]);
                            }

                            EmployeeLeaveCreditLogs::create([
                                'employee_leave_credit_id' => $employee_credit->id,
                                'previous_credit' => $previous_credit,
                                'leave_credits' => $daysDiff,
                                'reason' => 'apply'
                            ]);
                        }

                        if ($request->requirements) {
                            $index = 0;
                            $requirements_name = $request->requirements_name;

                            foreach ($request->file('requirements') as $key => $file) {
                                $fileName = $file->getClientOriginalName();
                                $size = filesize($file);
                                $file_name_encrypted = Helpers::checkSaveFile($file, '/requirements');

                                LeaveApplicationRequirement::create([
                                    'leave_application_id' => $leave_application->id,
                                    'file_name' => $fileName,
                                    'name' => $requirements_name[$index],
                                    'path' => $file_name_encrypted,
                                    'size' => $size,
                                ]);
                                $index++;
                            }
                        }

                        LeaveApplicationLog::create([
                            'action_by' => $employee_profile->id,
                            'leave_application_id' => $leave_application->id,
                            'action' => 'Applied'
                        ]);
                    }
                }

                $employeeCredit = EmployeeLeaveCredit::where('employee_profile_id', $employee_profile->id)->get();

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
                    'data' =>  new LeaveApplicationResource($leave_application),
                    'credits' => $result ? $result : [],
                    'message' => 'Successfully applied for ' . $leave_type->name
                ], Response::HTTP_OK);
            }
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id, Request $request)
    {
        try {
            $leave_application = LeaveApplication::find($id);

            if (!$leave_application) {
                return response()->json(['message' => "No leave application record."], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => new LeaveApplicationResource($leave_application),
                'message' => 'Retrieve leave application record.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function declined($id, AuthPinApprovalRequest $request)
    {
        try {
            $user = $request->user;
            $employee_profile = $user;
            $declined_by = null;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $leave_application = LeaveApplication::find($id);
            $leave_type = $leave_application->leaveType;
            $leave_application_hrmo = $leave_application->hrmo_officer;
            $leave_application_recommending = $leave_application->recommending_officer;
            $leave_application_approving = $leave_application->approving_officer;

            if ($employee_profile->id === $leave_application_hrmo) {
                $status='declined by hrmo officer';
                $declined_by = "HR";
            }
            else if($employee_profile->id === $leave_application_recommending)
            {
                $status='declined by recommending officer';
                $declined_by = "Recommending officer";
            }
            else if($employee_profile->id === $leave_application_approving)
            {
                $status='declined by approving officer';
                $declined_by = "Approving officer";
            }

            $leave_application->update([
                'status' => $status,
                'remarks' => strip_tags($request->remarks),
            ]);
            
            $from = Carbon::parse($leave_application->date_from)->format('F d, Y');
            $to = Carbon::parse($leave_application->date_to)->format('F d, Y');
            $message = "Your ".$leave_application->leave_type->name." request with date from ".$from." to ".$to." has been declined by ".$declined_by." .";
            Helpers::notifications($leave_application->employee_profile_id, $message, $leave_application->leaveType->name);

            if (!$leave_type->is_special) {
                $employee_credit = EmployeeLeaveCredit::where('employee_profile_id', $leave_application->employee_profile_id)
                    ->where('leave_type_id', $leave_application->leave_type_id)->first();

                $current_leave_credit = $employee_credit->total_leave_credits;
                $current_used_leave_credit = $employee_credit->used_leave_credits;

                $employee_credit->update([
                    'total_leave_credits' => $current_leave_credit + $leave_application->applied_credits,
                    'used_leave_credits' => $current_used_leave_credit - $leave_application->applied_credits
                ]);

                EmployeeLeaveCreditLogs::create([
                    'employee_leave_credit_id' => $employee_credit->id,
                    'previous_credit' => $current_leave_credit,
                    'leave_credits' => $leave_application->applied_credits,
                    'reason' => "declined"
                ]);
            }

            return response()->json([
                'data' => new LeaveApplicationResource($leave_application),
                'message' => 'Declined leave application successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function printLeaveForm($id)
    {
        try {
            $data = LeaveApplication::with(['employeeProfile', 'leaveType', 'recommendingOfficer', 'approvingOfficer'])->where('id', $id)->first();
            $vl_employee_credit = EmployeeLeaveCredit::where('leave_type_id', LeaveType::where('code', 'VL')->first()->id)->first();
            $sl_employee_credit = EmployeeLeaveCredit::where('leave_type_id', LeaveType::where('code', 'SL')->first()->id)->first();

            // return $data;
            $leave_type = LeaveTypeResource::collection(LeaveType::all());
            $my_leave_type = new LeaveTypeResource(LeaveType::find($data->leave_type_id));
            $hrmo_officer = Section::with(['supervisor'])->where('code', 'HRMO')->first();

            $employeeLeaveCredit = EmployeeLeaveCredit::with('employeeLeaveCreditLogs')
                ->where('employee_profile_id', $data->employee_profile_id)
                ->where('leave_type_id', $data->leave_type_id)
                ->first();

            if ($employeeLeaveCredit) {
                $creditLogs = $employeeLeaveCredit->employeeLeaveCreditLogs;
                // Now you can work with $creditLogs
            } else {
                // Handle the case when no matching record is found
                $creditLogs = null; // Or any other appropriate action
            }

            // return view('leave_from.leave_application_form', compact('data', 'leave_type', 'hrmo_officer'));

            $options = new Options();
            $options->set('isPhpEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->getOptions()->setChroot([base_path() . '/public/storage']);
            $html = view('leave_from.leave_application_form', compact('data', 'leave_type', 'hrmo_officer', 'my_leave_type', 'vl_employee_credit', 'sl_employee_credit'))->render();
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

    public static function checkOverlap($start, $end, $employeeId)
    {
        // Check for overlapping dates in LeaveApplication
        $overlappingLeave = LeaveApplication::where(function ($query) use ($start, $end, $employeeId) {
            $query->where('employee_profile_id', $employeeId)
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('date_from', [$start, $end])
                        ->orWhereBetween('date_to', [$start, $end])
                        ->orWhere(function ($query) use ($start, $end) {
                            $query->where('date_from', '<=', $start)
                                ->where('date_to', '>=', $end);
                        });
                });
        })->exists();

        // Check for overlapping dates in OfficialBusiness
        $overlappingOb = OfficialBusiness::where(function ($query) use ($start, $end, $employeeId) {
            $query->where('employee_profile_id', $employeeId)
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('date_from', [$start, $end])
                        ->orWhereBetween('date_to', [$start, $end])
                        ->orWhere(function ($query) use ($start, $end) {
                            $query->where('date_from', '<=', $start)
                                ->where('date_to', '>=', $end);
                        });
                });
        })->exists();

        $overlappingOT = OfficialTime::where(function ($query) use ($start, $end, $employeeId) {
            $query->where('employee_profile_id', $employeeId)
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('date_from', [$start, $end])
                        ->orWhereBetween('date_to', [$start, $end])
                        ->orWhere(function ($query) use ($start, $end) {
                            $query->where('date_from', '<=', $start)
                                ->where('date_to', '>=', $end);
                        });
                });
        })->exists();

        // Return true if any overlap is found, otherwise false
        return $overlappingLeave || $overlappingOb || $overlappingOT;
    }
}
