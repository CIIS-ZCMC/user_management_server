<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Http\Controllers\UmisAndEmployeeManagement\EmployeeProfileController;
use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Resources\LeaveTypeResource;
use App\Http\Resources\MyApprovedLeaveApplicationResource;
use App\Http\Resources\NotificationResource;
use App\Models\Department;
use App\Models\Division;
use App\Models\DocumentNumber;
use App\Models\EmployeeCreditLog;
use App\Models\EmployeeOvertimeCredit;
use App\Models\LeaveType;
use App\Models\Notifications;
use App\Models\Section;
use App\Models\TaskSchedules;
use App\Models\Unit;
use App\Models\UserNotifications;
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
use App\Http\Resources\EmployeeLeaveCredit as ResourcesEmployeeLeaveCredit;
use App\Http\Resources\LeaveApplicationResource;
use App\Imports\CtoApplicationImport;
use App\Jobs\SendEmailJob;
use App\Models\Country;
use App\Models\CtoApplication;
use App\Models\EmployeeLeaveCredit;
use App\Models\EmployeeLeaveCreditLogs;
use App\Models\EmployeeProfile;
use App\Models\Holiday;
use App\Models\LeaveApplicationLog;
use App\Models\LeaveApplicationRequirement;
use App\Models\OfficialBusiness;
use App\Models\OfficialTime;
use DateTime;
use App\Imports\LeaveApplicationsImport;
use App\Imports\OfficialBusinessImport;
use App\Imports\OfficialTimeImport;
use Maatwebsite\Excel\Facades\Excel;

class LeaveApplicationController extends Controller
{

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt',
        ]);

        Excel::import(new CtoApplicationImport(), $request->file('file'));

        return response()->json([
            'message' => 'Import Succesfull.'
        ], Response::HTTP_OK);
    }

    public function index(Request $request)
    {
        try {

            $employee_profile = $request->user;

            $employeeId = $employee_profile->id;
            $recommending = ["for recommending approval", "for approving approval", "approved",  "received", "declined by recommending officer"];
            $approving = ["for approving approval", "approved", "received", "declined by approving officer"];

            /**
             * Supervisor = for recommending, for approving, approved, de
             */

            if ($employeeId == 1) {
                $leave_applications = LeaveApplication::select('leave_applications.*')
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
                        'is_printed',
                        'print_datetime',
                        'hrmo_officer',
                        'recommending_officer',
                        'approving_officer',
                        'employee_oic_id',
                        'is_effective',
                        'received_at',
                        'cancelled_at',
                        'created_at',
                        'updated_at',
                        'applied_by'
                    )
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else {
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
                        'is_printed',
                        'print_datetime',
                        'hrmo_officer',
                        'recommending_officer',
                        'approving_officer',
                        'employee_oic_id',
                        'is_effective',
                        'received_at',
                        'cancelled_at',
                        'created_at',
                        'updated_at',
                        'applied_by'
                    )
                    ->orderBy('created_at', 'desc')
                    ->get();
            }



            return response()->json([
                'data' => LeaveApplicationResource::collection($leave_applications),
                'message' => 'Retrieve all leave application records.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function exportCsv()
    {
        $leave_applications = LeaveApplication::with('employeeProfile', 'leaveType')->get();
        // ->where('status', 'received')

        // ->where('status', 'approved')


        $response = [];

        foreach ($leave_applications as $leave_application) {
            $employeeName = $leave_application->employeeProfile->name();
            $employeeid = $leave_application->employeeProfile->employee_id;
            $leaveType = $leave_application->leaveType->name;
            $dateFrom = $leave_application->date_from;
            $dateTo = $leave_application->date_to;
            $city = $leave_application->city;
            $country = $leave_application->country;
            $illness = $leave_application->illness;
            $is_board = $leave_application->is_board;
            $is_masters = $leave_application->is_masters;
            $is_outpatient = $leave_application->is_outpatient;
            $date_filed = $leave_application->created_at;
            $credits = $leave_application->applied_credits;
            $status = $leave_application->status;
            $response[] = [
                'Employee Id' => $employeeid,
                'Employee Name' => $employeeName,
                'Leave Type' => $leaveType,
                'Date From' => $dateFrom,
                'Date To' => $dateTo,
                'Country' => $country,
                'City' => $city,
                'Illness' => $illness,
                'Board Exam' => $is_board,
                'Masters' => $is_masters,
                'Outpatient' => $is_outpatient,
                'Date Filed' => $date_filed,
                'Total Credits' => $credits,
                'Total Days' => $credits,
                'Status' => $status,

            ];
        }
        return ['data' => $response];
    }

    public function hrmoApproval(Request $request)
    {
        try {

            $employee_profile = $request->user;
            /**
             * HR division
             * Only newly applied leave application
             */
            $employeeId = $employee_profile->id;
            if (Helpers::getHrmoOfficer() === $employee_profile->id) {
                $employeeId = $employee_profile->id;
                $hrmo = ["applied", "for recommending approval", "for approving approval", "approved", "declined by hrmo officer", "cancelled", "received", "cancelled by user", "cancelled by hrmo", "cancelled by mcc"];

                $leave_applications = LeaveApplication::select('leave_applications.*')
                    ->where(function ($query) use ($hrmo, $employeeId) {
                        $query->whereIn('leave_applications.status', $hrmo)
                            ->where('leave_applications.hrmo_officer', $employeeId);
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
                        'is_printed',
                        'print_datetime',
                        'hrmo_officer',
                        'recommending_officer',
                        'approving_officer',
                        'employee_oic_id',
                        'is_effective',
                        'received_at',
                        'cancelled_at',
                        'created_at',
                        'updated_at',
                        'applied_by'
                    )
                    ->orderBy('created_at', 'desc')
                    ->get();

                // $leave_applications = collect($leave_applications)->filter(function ($leave_application) use ($employeeId) {
                //     // Keep the leave application if the status is "applied" or if the employee profile ID is not equal to $employeeId
                //     return $leave_application->status === "applied" || $leave_application->status === "cancelled by user" || $leave_application->status === "cancelled by hrmo" || $leave_application->employee_profile_id !== $employeeId;
                // })->all();

                return response()->json([
                    'data' => LeaveApplicationResource::collection($leave_applications),
                    'message' => 'Retrieve all leave application records.'
                ], Response::HTTP_OK);
            } else if ($employeeId == 1) {
                $employeeId = $employee_profile->id;
                $hrmo = ["applied", "for recommending approval", "for approving approval", "approved", "declined by hrmo officer", "cancelled", "received", "cancelled by user", "cancelled by hrmo", "cancelled by mcc"];

                $leave_applications = LeaveApplication::select('leave_applications.*')
                    ->where(function ($query) use ($hrmo, $employeeId) {
                        $query->whereIn('leave_applications.status', $hrmo);
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
                        'is_printed',
                        'print_datetime',
                        'hrmo_officer',
                        'recommending_officer',
                        'approving_officer',
                        'employee_oic_id',
                        'is_effective',
                        'received_at',
                        'cancelled_at',
                        'created_at',
                        'updated_at',
                        'applied_by'
                    )
                    ->orderBy('created_at', 'desc')
                    ->get();

                // $leave_applications = collect($leave_applications)->filter(function ($leave_application) use ($employeeId) {
                //     // Keep the leave application if the status is "applied" or if the employee profile ID is not equal to $employeeId
                //     return $leave_application->status === "applied" || $leave_application->status === "cancelled by user" || $leave_application->status === "cancelled by hrmo" || $leave_application->employee_profile_id !== $employeeId;
                // })->all();

                return response()->json([
                    'data' => LeaveApplicationResource::collection($leave_applications),
                    'message' => 'Retrieve all leave application records.'
                ], Response::HTTP_OK);
            }
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approvedLeaveRequest(Request $request)
    {
        try {
            $employee_profile = $request->user;
            $assigned_area = $employee_profile->assignedArea->findDetails();
            $division_id = null;

            switch ($assigned_area['sector']) {
                case "Division":
                    $division_id = $assigned_area['details']->id;
                    break;
                case "Department":
                    $division_id = Department::find($assigned_area['details']->id)->division_id;
                    break;
                case "Section":
                    $section = Section::find($assigned_area['details']->id);

                    if ($section->department_id === null) {
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

            if ($division_id === null) {
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
                ->where('leave_applications.status', 'approved')
                ->get();

            $sections_leave_applications = LeaveApplication::select("leave_applications.*")
                ->join('employee_profiles as ep', 'leave_applications.employee_profile_id', 'ep.id')
                ->join('assigned_areas as aa', 'ep.id', 'aa.employee_profile_id')
                ->join('sections as s', 'aa.section_id', 's.id')
                ->join('departments as d', 's.department_id', 'd.id')
                ->join('divisions as dv', 'd.division_id', 'dv.id')
                ->select('leave_applications.*')
                ->where('dv.id', $division_id)
                ->where('leave_applications.status', 'approved')
                ->get();

            $departments_leave_applications = LeaveApplication::select("leave_applications.*")
                ->join('employee_profiles as ep', 'leave_applications.employee_profile_id', 'ep.id')
                ->join('assigned_areas as aa', 'ep.id', 'aa.employee_profile_id')
                ->join('departments as d', 'aa.department_id', 'd.id')
                ->join('divisions as dv', 'd.division_id', 'dv.id')
                ->select('leave_applications.*')
                ->where('dv.id', $division_id)
                ->where('leave_applications.status', 'approved')
                ->get();

            $divisions_leave_applications = LeaveApplication::select("leave_applications.*")
                ->join('employee_profiles as ep', 'leave_applications.employee_profile_id', 'ep.id')
                ->join('assigned_areas as aa', 'ep.id', 'aa.employee_profile_id')
                ->join('divisions as dv', 'aa.division_id', 'dv.id')
                ->select('leave_applications.*')
                ->where('dv.id', $division_id)
                ->where('leave_applications.status', 'approved')
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
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function showCountries()
    {
        try {
            $countries = Country::get();
            return response()->json([
                'data' => $countries,
                'message' => 'Retrieve countries.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approvedLeaveApplication()
    {
        try {

            $leave_applications = LeaveApplication::where('status', 'approved')->orWhere('status', 'received')->orWhere('status', 'cancelled by hrmo')->get();
            return response()->json([
                'data' => LeaveApplicationResource::collection($leave_applications),
                'message' => 'Retrieve list.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function countapprovedleaveApplication(Request $request)
    {
        try {
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function flLeaveApplication()
    {
        try {
            $leave_applications = LeaveApplication::whereHas('leaveType', function ($query) {
                $query->where('code', 'FL');
            })->get();
            return response()->json([
                'data' => LeaveApplicationResource::collection($leave_applications),
                'message' => 'Retrieve list.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function cancelFL($id, AuthPinApprovalRequest $request)
    {
        try {
            $user = $request->user;
            $employee_profile = $user;

            $cleanData['pin'] = strip_tags($request->pin);
            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }

            $leave_application = LeaveApplication::find($id);


            if ($leave_application->status === 'cancelled by mcc') {
                return response()->json(['message' => "You already cancelled this application."], Response::HTTP_FORBIDDEN);
            }

            $leave_type = $leave_application->leaveType;

            $leave_application->update([
                'status' => 'cancelled by mcc',
                'cancelled_at' => Carbon::now(),
                'remarks' => $request->remarks,
            ]);


            if (!$leave_type->is_special) {
                $employee_credit = EmployeeLeaveCredit::where('employee_profile_id', $leave_application->employee_profile_id)
                    ->where('leave_type_id', $leave_application->leave_type_id)->first();

                $current_leave_credit = $employee_credit->total_leave_credits;
                $current_used_leave_credit = $employee_credit->used_leave_credits;

                $employee_credit->update([
                    'total_leave_credits' => $current_leave_credit + $leave_application->applied_credits,
                    'used_leave_credits' => $current_used_leave_credit - $leave_application->applied_credits
                ]);
            }

            $result = [];

            $employeeCredit = EmployeeLeaveCredit::where('employee_profile_id',  $leave_application->employee_profile_id)->get();

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

            LeaveApplicationLog::create([
                'action_by' => $employee_profile->id,
                'leave_application_id' => $leave_application->id,
                'action' => 'Cancelled by MCC'
            ]);

            //NOTIFICATIONS
            $title = $leave_type->name . " request cancelled";
            $description = "Your leave request of " . $leave_type->name . " has been cancelled by Medical Center Chief.";


            $notification = Notifications::create([
                "title" => $title,
                "description" => $description,
                "module_path" => '/leave-applications',
            ]);

            $user_notification = UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => $leave_application->employee_profile_id,
            ]);

            Helpers::sendNotification([
                "id" => Helpers::getEmployeeID($leave_application->employee_profile_id),
                "data" => new NotificationResource($user_notification)
            ]);

            return response()->json([
                'data' => new LeaveApplicationResource($leave_application),
                'credits' => $result ? $result : [],
                'message' => 'Cancelled leave application successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function myApprovedLeaveApplication(Request $request)
    {
        try {
            $employee_profile = $request->user;
            $leave_applications = LeaveApplication::where('status', 'approved')
                ->where('employee_profile_id', $employee_profile->id)->get();

            return response()->json([
                'data' => MyApprovedLeaveApplicationResource::collection($leave_applications),
                'message' => 'Retrieve list.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function myLeaveApplication(Request $request)
    {
        try {
            $employee_profile = $request->user;
            $leave_applications = LeaveApplication::where('status', 'approved')->where('employee_profile_id', $employee_profile->id)->get();

            return response()->json([
                'data' => MyApprovedLeaveApplicationResource::collection($leave_applications),
                'message' => 'Retrieve list.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeeApprovedLeaveApplication($id, Request $request)
    {
        try {
            $leave_applications = LeaveApplication::where('status', 'approved')->where('employee_profile_id', $id)->get();

            return response()->json([
                'data' => MyApprovedLeaveApplicationResource::collection($leave_applications),
                'message' => 'Retrieve list.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeeCreditLog($id)
    {
        try {
            $employeeCredits = EmployeeLeaveCredit::with(['logs', 'employeeProfile', 'leaveType'])->where('employee_profile_id', $id)->get();
            $allLogs = [];
            $employeeName = null;
            $employeePosition = null;
            $totalCreditsEarnedThisMonth = null;
            $totalCreditsEarnedThisYear = 0;
            foreach ($employeeCredits as $employeeCredit) {

                if (!$employeeName) {

                    $employeeName = $employeeCredit->employeeProfile->name();
                    $employeeJobPosition = $employeeCredit->employeeProfile->findDesignation()->code;
                    $employeePosition = $employeeCredit->employeeProfile->employmentType->name;
                    $employee_assign_area = $employeeCredit->employeeProfile->assignedArea->findDetails();
                }
                $logs = $employeeCredit->logs;

                foreach ($logs as $log) {

                    if ($log->action === 'add') {

                        if (Carbon::parse($log->created_at)->format('Y-m') === Carbon::now()->format('Y-m')) {
                            $leaveType = $employeeCredit->leaveType->code;
                            if ($leaveType === "VL" || $leaveType === "SL") {
                                $totalCreditsEarnedThisMonth[$leaveType] = isset($totalCreditsEarnedThisMonth[$leaveType]) ? $totalCreditsEarnedThisMonth[$leaveType]($log->leave_credits ?? 0) : ($log->leave_credits ?? 0);
                            }
                        }

                        if (Carbon::parse($log->created_at)->format('Y') === Carbon::now()->format('Y')) {
                            $totalCreditsEarnedThisYear += $log->leave_credits;
                        }

                        $remaining = $log->previous_credit + $log->leave_credits;
                    } else {
                        $remaining = $log->previous_credit - $log->leave_credits;
                    }
                    $allLogs[] = [
                        'leave_type' => $employeeCredit->leaveType->name,
                        'reason' => $log->reason,
                        'action' => $log->action,
                        'previous_credit' => $log->previous_credit,
                        'leave_credit' => $log->leave_credits,
                        'remaining' =>  $remaining,
                        'created_at' =>  $log->created_at,
                    ];
                }
            }

            $response = [
                'employee_name' => $employeeName,
                'employee_job' => $employeeJobPosition,
                'employee_position' => $employeePosition,
                'employee_area' => $employee_assign_area,
                'total_credits_earned_this_month' => $totalCreditsEarnedThisMonth,
                'total_credits_earned_this_year' => $totalCreditsEarnedThisYear,
                'logs' => $allLogs,
            ];

            // $response =array_merge($employeeDetails,$allLogs);
            return ['data' => $response];
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getEmployees()
    {
        try {

            $leaveCredits = EmployeeLeaveCredit::with(['employeeProfile.personalInformation', 'leaveType'])
                ->whereHas('employeeProfile', function ($query) {
                    $query->whereNotNull('employee_id');
                })
                ->whereHas('employeeProfile', function ($query) {
                    $query->where('employment_type_id', '!=', 5);
                })
                ->get()
                ->groupBy('employee_profile_id');
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
                    'employee_id' => $leaveCreditGroup->first()->employeeProfile->employee_id,
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
            $cleanData['pin'] = strip_tags($request->pin);

            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }

            foreach ($request->credits as $credit) {
                $employeeId = $request->employee_id;
                $leaveTypeId = $credit['leave_id'];

                $leaveCredit = EmployeeLeaveCredit::where('employee_profile_id', $employeeId)
                    ->where('leave_type_id', $leaveTypeId)
                    ->firstOrFail();

                // Capture the previous credit before updating
                $previousCredit = $leaveCredit->total_leave_credits;

                // Update the total leave credits
                $leaveCredit->total_leave_credits = $credit['credit_value'];
                $leaveCredit->save();

                // Log the update
                EmployeeLeaveCreditLogs::create([
                    'employee_leave_credit_id' => $leaveCredit->id,
                    'previous_credit' => $previousCredit,
                    'leave_credits' => $credit['credit_value'],
                    'reason' => "Update Credits",
                    'action' => "add"
                ]);
            }


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
                    'employee_id' => $leaveCreditGroup->first()->employeeProfile->employee_id,
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
            $cleanData['pin'] = strip_tags($request->pin);

            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }

            foreach ($request->credits as $credit) {
                $newLeaveCredit = new EmployeeLeaveCredit([
                    'employee_profile_id' => $request->employee_id,
                    'leave_type_id' => $credit['leave_id'], // Adjust the key if needed
                    'total_leave_credits' => (float) $credit['credit_value'],
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
            $cleanData['pin'] = strip_tags($request->pin);

            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }

            $leave_application = LeaveApplication::find($id);

            if (!$leave_application) {
                return response()->json(["message" => "No leave application with id " . $id], Response::HTTP_NOT_FOUND);
            }
            if ($leave_application->status === 'cancelled by user') {
                return response()->json(["message" => "Application has been cancelled by employee. "], Response::HTTP_FORBIDDEN);
            }

            $position = $employee_profile->position();
            $status = '';
            $log_status = '';
            $the_same_approver_id = '';
            $hrmo_flag = false;
            $approving_flag = false;
            //FOR NOTIFICATION
            $next_approving = null;
            $emp_id = $leave_application->employee_profile_id;
            $message = '';
            $leave_type = LeaveType::find($leave_application->leave_type_id);
            $employeeProfile = EmployeeProfile::find($leave_application->employee_profile_id);

            switch ($leave_application->status) {
                case 'applied':
                    if ($employee_profile->id === $leave_application->hrmo_officer) {
                        if ($leave_application->hrmo_officer === $leave_application->recommending_officer) {
                            $status = 'for approving approval';
                            $log_status = 'Approved by HRMO';
                            $the_same_approver_id = 'Approved by Recommending Officer';
                            $leave_application->update(['status' => $status]);

                            //FOR NOTIFICATION
                            $next_approving = $leave_application->approving_officer;
                            $message = 'HRMO';
                            $hrmo_flag = true;
                        } else {
                            $status = 'for recommending approval';
                            $log_status = 'Approved by HRMO';
                            $leave_application->update(['status' => $status]);

                            //FOR NOTIFICATION
                            $next_approving = $leave_application->recommending_officer;
                            $message = 'HRMO';
                        }
                    } else {
                        return response()->json([
                            'message' => 'You have no access to approve this request.',
                        ], Response::HTTP_FORBIDDEN);
                    }
                    break;
                case 'for recommending approval':
                    if ($employee_profile->id === $leave_application->recommending_officer) {
                        if ($leave_application->recommending_officer === $leave_application->approving_officer) {
                            $status = 'approved';
                            $log_status = 'Approved by Recommending Officer';
                            $the_same_approver_id = 'Approved by Approving Officer';
                            $leave_application->update(['status' => $status]);

                            //FOR NOTIFICATION
                            // $next_approving=$leave_application->recommending_officer;
                            $message = 'Approving Officer';
                            $approving_flag = true;
                        } else {
                            $status = 'for approving approval';
                            $log_status = 'Approved by Recommending Officer';
                            $leave_application->update(['status' => $status]);

                            //FOR NOTIFICATION
                            $next_approving = $leave_application->approving_officer;
                            $message = 'Recommending Officer';
                        }
                    } else {
                        return response()->json([
                            'message' => 'You have no access to approve this request.',
                        ], Response::HTTP_FORBIDDEN);
                    }
                    break;
                case 'for approving approval':
                    if ($employee_profile->id === $leave_application->approving_officer) {
                        $status = 'approved';
                        $log_status = 'Approved by Approving Officer';
                        $leave_application->update(['status' => $status]);

                        //FOR NOTIFICATION
                        // $next_approving=$leave_application->recommending_officer;
                        $message = 'Approving Officer';
                    } else {
                        return response()->json([
                            'message' => 'You have no access to approve this request.',
                        ], Response::HTTP_FORBIDDEN);
                    }
                    break;
            }

            if ($hrmo_flag) {
                LeaveApplicationLog::create([
                    'action_by' => $employee_profile->id,
                    'leave_application_id' => $leave_application->id,
                    'action' => $log_status
                ]);
                LeaveApplicationLog::create([
                    'action_by' => $employee_profile->id,
                    'leave_application_id' => $leave_application->id,
                    'action' => $the_same_approver_id
                ]);
            } elseif ($approving_flag) {
                LeaveApplicationLog::create([
                    'action_by' => $employee_profile->id,
                    'leave_application_id' => $leave_application->id,
                    'action' => $log_status
                ]);
                LeaveApplicationLog::create([
                    'action_by' => $employee_profile->id,
                    'leave_application_id' => $leave_application->id,
                    'action' => $the_same_approver_id
                ]);
            } else {
                LeaveApplicationLog::create([
                    'action_by' => $employee_profile->id,
                    'leave_application_id' => $leave_application->id,
                    'action' => $log_status
                ]);
            }


            if ($leave_application->status === 'approved') {
                //EMPLOYEE
                $title = $leave_type->name . " request approved";
                $description = "Your leave request of " . $leave_type->name . " has been approved by your " . $message . ".";


                $notification = Notifications::create([
                    "title" => $title,
                    "description" => $description,
                    "module_path" => '/leave-applications',
                ]);

                $user_notification = UserNotifications::create([
                    'notification_id' => $notification->id,
                    'employee_profile_id' => $leave_application->employee_profile_id,
                ]);

                Helpers::sendNotification([
                    "id" => Helpers::getEmployeeID($leave_application->employee_profile_id),
                    "data" => new NotificationResource($user_notification)
                ]);
            } else {
                //NEXT APPROVING
                $notification = Notifications::create([
                    "title" =>  "New " . $leave_type->name . " request",
                    "description" => $employeeProfile->personalInformation->name() . " filed a new " . $leave_type->name . " request",
                    "module_path" => '/leave-requests',
                ]);

                $user_notification = UserNotifications::create([
                    'notification_id' => $notification->id,
                    'employee_profile_id' => $next_approving,
                ]);

                Helpers::sendNotification([
                    "id" => Helpers::getEmployeeID($next_approving),
                    "data" => new NotificationResource($user_notification)
                ]);

                $officer = EmployeeProfile::where('id', $next_approving)->first();
                $employee = EmployeeProfile::where('id', $leave_application->employee_profile_id)->first();
                $email = $officer->personalinformation->contact->email_address;
                $name = $officer->personalInformation->name();

                $data = [
                    'name' =>  $message,
                    'employeeName' =>  $employee->personalInformation->name(),
                    'employeeID' => $employee->employee_id,
                    'leaveType' =>  $leave_type->name,
                    'dateFrom' =>  $leave_application->date_from,
                    'dateTo' =>  $leave_application->date_to,
                    "Link" => config('app.client_domain')
                ];

                SendEmailJob::dispatch('leave_request', $email, $name, $data);

                //EMPLOYEE
                $notification = Notifications::create([
                    "title" => $leave_type->name . " request approved",
                    "description" => "Your leave request of " . $leave_type->name . " has been approved by your " . $message . ".",
                    "module_path" => '/leave-applications',
                ]);

                $user_notification = UserNotifications::create([
                    'notification_id' => $notification->id,
                    'employee_profile_id' => $leave_application->employee_profile_id,
                ]);

                Helpers::sendNotification([
                    "id" => Helpers::getEmployeeID($leave_application->employee_profile_id),
                    "data" => new NotificationResource($user_notification)
                ]);
            }

            return response()->json([
                'data' => new LeaveApplicationResource($leave_application),
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
                $totalCredits = max(0, $leaveCredit->total_leave_credits); // Ensure total credits are not negative
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
            $employeeId = $employee_profile->id;

            $cleanData['pin'] = strip_tags($request->pin);
            if ($employee_profile['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }

            $employeeProfile = EmployeeProfile::find($employeeId);

            if ($employeeProfile->isUnderProbation()) {
                return response()->json(['message' => 'You are under probation.'], Response::HTTP_FORBIDDEN);
            }

            $recommending_and_approving = Helpers::getRecommendingAndApprovingOfficer($employee_profile->assignedArea->findDetails(), $employee_profile->id);

            $hrmo_officer = Helpers::getHrmoOfficer();


            if ($hrmo_officer === null || $recommending_and_approving === null || $recommending_and_approving['recommending_officer'] === null || $recommending_and_approving['approving_officer'] === null) {
                return response()->json(['message' => 'No recommending officer and/or supervising officer assigned.'], Response::HTTP_FORBIDDEN);
            }

            $cleanData = [];
            $result = [];

            $start = Carbon::parse($request->date_from);
            $end =  Carbon::parse($request->date_to);

            $currentDate = Carbon::now();
            $twoMonthsAhead = $currentDate->copy()->addMonths(2);

            // if ($start->greaterThan($twoMonthsAhead)) {
            //     return response()->json(['message' => "Filing 2 months ahead is not allowed."], Response::HTTP_FORBIDDEN);
            // }

            $daysDiff = $start->diffInDays($end) + 1;
            $leave_type = LeaveType::find($request->leave_type_id);

            $checkSchedule = Helpers::hasSchedule($start, $end, $employeeId);

            if (!$checkSchedule['status']) {
                return response()->json(['message' => "You don't have a schedule within the specified date range."], Response::HTTP_FORBIDDEN);
            }

            $dateObject = new DateTime($start);
            $dateFormatted = $dateObject->format('m-d');
            $isHolidayByMonthDay = Holiday::where('month_day', $dateFormatted)->get();
            $isHolidayExists = false;
            foreach ($isHolidayByMonthDay as $holiday) {
                if ($holiday->isspecial == 1) {
                    $isHolidayExists = Holiday::where('effectiveDate', $currentDate)->exists();
                } else {
                    $isHolidayExists = Holiday::where('month_day', $dateFormatted)->exists();
                }

                if ($isHolidayExists) {
                    break;
                }
            }

            if ($isHolidayExists) {
                return response()->json(['message' => "The selected starting date, " . $dateObject->format('Y-m-d') . ", is a holiday."], Response::HTTP_FORBIDDEN);
            }


            //IF SICK LEAVE
            if ($leave_type->code === 'SL' && $leave_type->file_after !== null) {

                // Initialize the variable to store the final date of the consecutive schedule
                $finalConsecutiveScheduleDate = null;
                $foundConsecutiveDays = 0;
                $currentDate = Carbon::now();
                $checkDate = $currentDate->copy();
                $Date = $end->copy();
                // Loop through each day starting from the end date

                while ($foundConsecutiveDays  <= 3) {
                    if (Helpers::hasSchedule($Date->toDateString(), $Date->toDateString(), $employeeId)) {
                        // If a schedule is found, increment the counter
                        $foundConsecutiveDays++;

                        // Store the date of the current consecutive schedule
                    }
                    // Move to the next day
                    $Date->addDay();
                }

                if ($Date->lt($checkDate)) {
                    return response()->json(['message' => "You missed the filing deadline."], Response::HTTP_FORBIDDEN);
                }
            }


            //IF VL OR FL
            if (($leave_type->code === 'VL' && $request->country === 'Philippines') || $leave_type->code === 'FL') {
                // Get the current date
                $currentDate = Carbon::now();
                // Get the HRMO schedule for the next 5 days
                $checkDate = $currentDate->copy();

                $foundConsecutiveDays = 0;
                $selected_date = $start->copy();
                if (Helpers::hasSchedule($checkDate->toDateString(),  $checkDate->toDateString(), $hrmo_officer)) {
                    $vldateDate = $currentDate->copy();
                    $foundConsecutiveDays = 0;

                    while ($foundConsecutiveDays === 4) {

                        if (Helpers::hasSchedule($vldateDate->toDateString(), $vldateDate->toDateString(), $hrmo_officer)) {
                            // If a schedule is found, increment the counter
                            $foundConsecutiveDays++;
                        }
                        $vldateDate->addDay();
                    }
                    $message = $selected_date->toDateString();
                    if ($selected_date->lt($vldateDate)) {

                        return response()->json(['message' => "You cannot file for leave on $message. Please select a date 5 days or more from today."], Response::HTTP_FORBIDDEN);
                    }
                } else {
                    return response()->json(['message' => "No schedule defined for HRMO"], Response::HTTP_FORBIDDEN);
                }
            }
            //IF OUTSIDE COUNTRY
            if ($leave_type->code === 'VL' && $request->country !== 'Philippines') {
                // Get the current date
                $currentDate = Carbon::now();

                // Check if there is an HRMO schedule starting from today
                if (!Helpers::hasSchedule($currentDate->toDateString(), $currentDate->toDateString(), $hrmo_officer)) {
                    return response()->json(['message' => "No schedule defined for HRMO"], Response::HTTP_FORBIDDEN);
                }

                $consecutiveDaysNeeded = 19;
                $foundConsecutiveDays = 0;
                $checkDate = $currentDate->copy();

                // Loop to find 19 consecutive days with HRMO schedule
                while ($foundConsecutiveDays < $consecutiveDaysNeeded) {
                    if (Helpers::hasSchedule($checkDate->toDateString(), $checkDate->toDateString(), $hrmo_officer)) {
                        $foundConsecutiveDays++;
                    }
                    $checkDate->addDay();
                }

                $earliestValidDate = $checkDate->copy(); // This is the date after 19 consecutive days

                if ($start->lt($earliestValidDate)) {
                    $message = $start->toDateString();
                    return response()->json(['message' => "You cannot file for leave on $message. Please select a date 20 days or more from today."], Response::HTTP_FORBIDDEN);
                }
            }

            $overlapExists = Helpers::hasOverlappingRecords($start, $end, $employeeId);

            if ($overlapExists) {
                return response()->json(['message' => 'You already have an application for the same dates.'], Response::HTTP_FORBIDDEN);
            } else {
                if ($leave_type->is_special) {
                    if ($leave_type->period < $checkSchedule['totalWithSchedules']) {
                        return response()->json(['message' => 'Exceeds days entitled for ' . $leave_type->name], Response::HTTP_FORBIDDEN);
                    }

                    $cleanData['applied_credits'] = $checkSchedule['totalWithSchedules'];
                    $cleanData['employee_profile_id'] = $employee_profile->id;
                    $cleanData['hrmo_officer'] = $hrmo_officer;

                    if ($request->employee_oic_id !== "null" && $request->employee_oic_id !== null) {
                        $cleanData['employee_oic_id'] = (int) strip_tags($request->employee_oic_id);
                    }

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
                    if ($request->without_pay == 0 && $employee_credit->total_leave_credits < $checkSchedule['totalWithSchedules']) {
                        return response()->json(['message' => 'Insufficient leave credits.'], Response::HTTP_BAD_REQUEST);
                    } else {
                        $totalHours = Helpers::getTotalHours($start, $end, $employeeId);
                        $totalDeductCredits = (int) ($totalHours / 8);
                        if ($employee_profile->employmentType === 'Permanent Part-time') {
                            $totalDeductCredits = $totalDeductCredits / 8;
                            $cleanData['applied_credits'] = $totalDeductCredits;
                        } else {
                            $cleanData['applied_credits'] = $checkSchedule['totalWithSchedules'];
                        }

                        $cleanData['employee_profile_id'] = $employee_profile->id;
                        $cleanData['hrmo_officer'] = $hrmo_officer;

                        if ($request->employee_oic_id !== "null" && $request->employee_oic_id !== null) {
                            $cleanData['employee_oic_id'] = (int) strip_tags($request->employee_oic_id);
                        }

                        $isMCC = Division::where('code', 'OMCC')->where('chief_employee_profile_id', $employee_profile->id)->first();

                        if (!$isMCC) {

                            if ($leave_type->code === 'VL' && $request->country !== 'Philippines') {
                                $cleanData['recommending_officer'] = Helpers::getDivHead($employee_profile->assignedArea->findDetails());
                                $cleanData['approving_officer'] = Helpers::getChiefOfficer();
                            } else {
                                $cleanData['recommending_officer'] = $recommending_and_approving['recommending_officer'];
                                $cleanData['approving_officer'] = $recommending_and_approving['approving_officer'];
                            }
                        }
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

                    if ($request->without_pay == 0) {
                        $previous_credit = $employee_credit->total_leave_credits;

                        $employee_credit->update([
                            'total_leave_credits' => $employee_credit->total_leave_credits - $checkSchedule['totalWithSchedules'],
                            'used_leave_credits' => $employee_credit->used_leave_credits + $checkSchedule['totalWithSchedules']
                        ]);


                        if (LeaveType::find($request->leave_type_id)->code === 'FL') {
                            $vlLeaveTypeId = LeaveType::where('code', 'VL')->first()->id;

                            $employee_credit_vl = EmployeeLeaveCredit::where('employee_profile_id', $employee_profile->id)
                                ->where('leave_type_id', $vlLeaveTypeId)->first();

                            $previous_credit_vl = $employee_credit_vl->total_leave_credits;

                            $employee_credit_vl->update([
                                'total_leave_credits' => $employee_credit_vl->total_leave_credits - $checkSchedule['totalWithSchedules'],
                                'used_leave_credits' => $employee_credit_vl->used_leave_credits + $checkSchedule['totalWithSchedules']
                            ]);
                        }
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

                    EmployeeLeaveCreditLogs::create([
                        'employee_leave_credit_id' => $employee_credit->id,
                        'previous_credit' => $previous_credit,
                        'leave_credits' => $checkSchedule['totalWithSchedules'],
                        'reason' => 'apply',
                        'action' => 'deduct'
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

            $title = "New " . $leave_type->name . " request";
            $description = $employeeProfile->personalInformation->name() . " filed a new " . $leave_type->name . " request";


            $notification = Notifications::create([
                "title" => $title,
                "description" => $description,
                "module_path" => '/hr-leave-requests',
            ]);

            $user_notification = UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => $hrmo_officer,
            ]);

            Helpers::sendNotification([
                "id" => Helpers::getEmployeeID($hrmo_officer),
                "data" => new NotificationResource($user_notification)
            ]);

            //OIC NOTIFS
            if ($request->employee_oic_id !== "null" && $request->employee_oic_id !== null) {
                $from = Carbon::parse($request->date_from)->format('F d, Y');
                $to = Carbon::parse($request->date_to)->format('F d, Y');
                $title = "Assigned as OIC";
                $description = 'You have been assigned as Officer-in-Charge from ' . $from . ' to ' . $to . ' by ' . $employee_profile->personalInformation->name() . '.';


                $notification = Notifications::create([
                    "title" => $title,
                    "description" => $description,
                    "module_path" => '/calendar',
                ]);

                $user_notification = UserNotifications::create([
                    'notification_id' => $notification->id,
                    'employee_profile_id' => $request->employee_oic_id,
                ]);

                Helpers::sendNotification([
                    "id" => Helpers::getEmployeeID($request->employee_oic_id),
                    "data" => new NotificationResource($user_notification)
                ]);
            }

            $hrmo = EmployeeProfile::where('id', $hrmo_officer)->first();
            $email = $hrmo->personalInformation->contact->email_address;
            $name = $hrmo->personalInformation->name();


            $data = [
                'name' =>  'HRMO',
                'employeeName' =>  $employee_profile->personalInformation->name(),
                'employeeID' => $employee_profile->employee_id,
                'leaveType' =>  $leave_type->name,
                'dateFrom' =>  $request->date_from,
                'dateTo' =>  $request->date_to,
                "Link" => config('app.client_domain')
            ];

            SendEmailJob::dispatch('leave_request', $email, $name, $data);

            // Helpers::registerSystemLogs($request, $data->id, true, 'Success in storing ' . $this->PLURAL_MODULE_NAME . '.'); //System Logs

            return response()->json([
                'data' => new LeaveApplicationResource($leave_application),
                'credits' => $result ? $result : [],
                'message' => 'Successfully applied for ' . $leave_type->name
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function storeHrmo(LeaveApplicationRequest $request)
    {
        try {

            $employee_id = $request->employee_id;
            $filed_date = $request->filed_date;
            $employee_profile = $request->user;
            $applied_by = $employee_profile->id;

            $cleanData['pin'] = strip_tags($request->pin);
            if ($employee_profile['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }

            $employeeProfile = EmployeeProfile::find($employee_id);

            if ($employeeProfile->isUnderProbation()) {
                return response()->json(['message' => 'Employee is under probation.'], Response::HTTP_FORBIDDEN);
            }

            $recommending_and_approving = Helpers::getRecommendingAndApprovingOfficer($employee_profile->assignedArea->findDetails(), $employee_id);

            $hrmo_officer = Helpers::getHrmoOfficer();


            if ($hrmo_officer === null || $recommending_and_approving === null || $recommending_and_approving['recommending_officer'] === null || $recommending_and_approving['approving_officer'] === null) {
                return response()->json(['message' => 'No recommending officer and/or supervising officer assigned.'], Response::HTTP_FORBIDDEN);
            }

            $cleanData = [];
            $result = [];

            $start = Carbon::parse($request->date_from);
            $end =  Carbon::parse($request->date_to);


            $daysDiff = $start->diffInDays($end) + 1;
            $leave_type = LeaveType::find($request->leave_type_id);

            $checkSchedule = Helpers::hasSchedule($start, $end, $employee_id);

            if (!$checkSchedule) {
                return response()->json(['message' => "Employee doesn't have a schedule within the specified date range."], Response::HTTP_FORBIDDEN);
            }


            $overlapExists = Helpers::hasOverlappingRecords($start, $end, $employee_id);

            if ($overlapExists) {
                return response()->json(['message' => 'Employee already have an application for the same dates.'], Response::HTTP_FORBIDDEN);
            } else {
                if ($leave_type->is_special) {
                    $cleanData['applied_credits'] = $checkSchedule['totalWithSchedules'];
                    $cleanData['employee_profile_id'] = $employee_id;
                    $cleanData['hrmo_officer'] = $hrmo_officer;

                    if ($request->employee_oic_id !== "null" && $request->employee_oic_id !== null) {
                        $cleanData['employee_oic_id'] = (int) strip_tags($request->employee_oic_id);
                    }

                    $isMCC = Division::where('code', 'OMCC')->where('chief_employee_profile_id', $employee_id)->first();

                    if (!$isMCC) {
                        $cleanData['recommending_officer'] = $recommending_and_approving['recommending_officer'];
                        $cleanData['approving_officer'] = $recommending_and_approving['approving_officer'];
                    }

                    $cleanData['status'] = 'applied';
                    $cleanData['created_at'] = $filed_date;
                    $cleanData['applied_by'] = $applied_by;

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


                    $employee_credit = EmployeeLeaveCredit::where('employee_profile_id', $employee_id)
                        ->where('leave_type_id', $request->leave_type_id)->first();

                    //  return response()->json(['message' => $request->without_pay == 0 && $employee_credit->total_leave_credits < $daysDiff], 401);
                    if ($request->without_pay == 0 && $employee_credit->total_leave_credits < $checkSchedule['totalWithSchedules']) {
                        return response()->json(['message' => 'Insufficient leave credits.'], Response::HTTP_BAD_REQUEST);
                    } else {
                        $totalHours = Helpers::getTotalHours($start, $end, $employee_id);
                        $totalDeductCredits = (int) ($totalHours / 8);
                        if ($employee_profile->employmentType === 'Permanent Part-time') {
                            $totalDeductCredits = $totalDeductCredits / 8;
                            $cleanData['applied_credits'] = $totalDeductCredits;
                        } else {
                            $cleanData['applied_credits'] = $checkSchedule['totalWithSchedules'];
                        }

                        $cleanData['employee_profile_id'] = $employee_id;
                        $cleanData['hrmo_officer'] = $hrmo_officer;

                        if ($request->employee_oic_id !== "null" && $request->employee_oic_id !== null) {
                            $cleanData['employee_oic_id'] = (int) strip_tags($request->employee_oic_id);
                        }

                        $isMCC = Division::where('code', 'OMCC')->where('chief_employee_profile_id', $employee_id)->first();

                        if (!$isMCC) {

                            if ($leave_type->code === 'VL' && $request->country !== 'Philippines') {
                                $cleanData['recommending_officer'] = Helpers::getDivHead($employee_profile->assignedArea->findDetails());
                                $cleanData['approving_officer'] = Helpers::getChiefOfficer();
                            } else {
                                $cleanData['recommending_officer'] = $recommending_and_approving['recommending_officer'];
                                $cleanData['approving_officer'] = $recommending_and_approving['approving_officer'];
                            }
                        }
                    }

                    $cleanData['status'] = 'applied';
                    $cleanData['created_at'] = $filed_date;
                    $cleanData['applied_by'] = $applied_by;


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

                    if ($request->without_pay == 0) {
                        $previous_credit = $employee_credit->total_leave_credits;

                        $employee_credit->update([
                            'total_leave_credits' => $employee_credit->total_leave_credits - $checkSchedule['totalWithSchedules'],
                            'used_leave_credits' => $employee_credit->used_leave_credits + $checkSchedule['totalWithSchedules']
                        ]);


                        if (LeaveType::find($request->leave_type_id)->code === 'FL') {
                            $vlLeaveTypeId = LeaveType::where('code', 'VL')->first()->id;

                            $employee_credit_vl = EmployeeLeaveCredit::where('employee_profile_id', $employee_id)
                                ->where('leave_type_id', $vlLeaveTypeId)->first();

                            $previous_credit_vl = $employee_credit_vl->total_leave_credits;

                            $employee_credit_vl->update([
                                'total_leave_credits' => $employee_credit_vl->total_leave_credits - $checkSchedule['totalWithSchedules'],
                                'used_leave_credits' => $employee_credit_vl->used_leave_credits + $checkSchedule['totalWithSchedules']
                            ]);
                        }
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

                    EmployeeLeaveCreditLogs::create([
                        'employee_leave_credit_id' => $employee_credit->id,
                        'previous_credit' => $previous_credit,
                        'leave_credits' => $checkSchedule['totalWithSchedules'],
                        'reason' => 'apply',
                        'action' => 'deduct'
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

            $title = "New " . $leave_type->name . " request";
            $description = "HRMO " . " filed a new " . $leave_type->name . " request for you";


            $notification = Notifications::create([
                "title" => $title,
                "description" => $description,
                "module_path" => '/hr-leave-requests',
            ]);

            $user_notification = UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => $employee_id,
            ]);

            Helpers::sendNotification([
                "id" => Helpers::getEmployeeID($employee_id),
                "data" => new NotificationResource($user_notification)
            ]);

            //OIC NOTIFS
            if ($request->employee_oic_id !== "null" && $request->employee_oic_id !== null) {
                $from = Carbon::parse($request->date_from)->format('F d, Y');
                $to = Carbon::parse($request->date_to)->format('F d, Y');
                $title = "Assigned as OIC";
                $description = 'You have been assigned as Officer-in-Charge from ' . $from . ' to ' . $to . ' by ' . $employee_profile->personalInformation->name() . '.';


                $notification = Notifications::create([
                    "title" => $title,
                    "description" => $description,
                    "module_path" => '/calendar',
                ]);

                $user_notification = UserNotifications::create([
                    'notification_id' => $notification->id,
                    'employee_profile_id' => $request->employee_oic_id,
                ]);

                Helpers::sendNotification([
                    "id" => Helpers::getEmployeeID($request->employee_oic_id),
                    "data" => new NotificationResource($user_notification)
                ]);
            }

            return response()->json([
                'data' => new LeaveApplicationResource($leave_application),
                'credits' => $result ? $result : [],
                'message' => 'Successfully applied for ' . $leave_type->name
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAppliedByHrmo(Request $request)
    {
        try {

            $leave_applications = LeaveApplication::select('leave_applications.*')
                ->whereNotNull('applied_by')
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
                    'is_printed',
                    'print_datetime',
                    'hrmo_officer',
                    'recommending_officer',
                    'approving_officer',
                    'employee_oic_id',
                    'is_effective',
                    'received_at',
                    'cancelled_at',
                    'created_at',
                    'updated_at',
                    'applied_by'
                )
                ->orderBy('created_at', 'desc')
                ->get();


            return response()->json([
                'data' => LeaveApplicationResource::collection($leave_applications),
                'message' => 'Retrieve all leave application records.'
            ], Response::HTTP_OK);
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
            $cleanData['pin'] = strip_tags($request->pin);

            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }

            $leave_application = LeaveApplication::find($id);
            $leave_type = $leave_application->leaveType;
            $leave_application_hrmo = $leave_application->hrmo_officer;
            $leave_application_recommending = $leave_application->recommending_officer;
            $leave_application_approving = $leave_application->approving_officer;


            if ($leave_application->status === 'cancelled by user') {
                return response()->json(["message" => "Application has been cancelled by employee. "], Response::HTTP_FORBIDDEN);
            }

            //FOR NOTIFICATION
            $leave_type = LeaveType::find($leave_application->leave_type_id);

            switch ($leave_application->status) {
                case 'applied':
                    if ($employee_profile->id === $leave_application_hrmo) {
                        $status = 'declined by hrmo officer';
                        $declined_by = "HRMO";
                    } else {
                        return response()->json([
                            'message' => 'You have no access to  decline this request.',
                        ], Response::HTTP_FORBIDDEN);
                    }
                    break;
                case 'for recommending approval':
                    if ($employee_profile->id === $leave_application_recommending) {
                        $status = 'declined by recommending officer';
                        $declined_by = "Recommending Officer";
                    } else {
                        return response()->json([
                            'message' => 'You have no access to  decline this request.',
                        ], Response::HTTP_FORBIDDEN);
                    }
                    break;
                case 'for approving approval':
                    if ($employee_profile->id === $leave_application_approving) {
                        $status = 'declined by approving officer';
                        $declined_by = "Approving Officer";
                    } else {
                        return response()->json([
                            'message' => 'You have no access to  decline this request.',
                        ], Response::HTTP_FORBIDDEN);
                    }
                    break;
            }


            $leave_application->update([
                'status' => $status,
                'remarks' => strip_tags($request->remarks),
            ]);

            // $from = Carbon::parse($leave_application->date_from)->format('F d, Y');
            // $to = Carbon::parse($leave_application->date_to)->format('F d, Y');

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
                    'reason' => "declined",
                    'action' => 'add'
                ]);
            }
            $result = [];

            $employeeCredit = EmployeeLeaveCredit::where('employee_profile_id',  $leave_application->employee_profile_id)->get();

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

            LeaveApplicationLog::create([
                'action_by' => $employee_profile->id,
                'leave_application_id' => $leave_application->id,
                'action' => 'Declined'
            ]);

            //EMPLOYEE notification
            $title = $leave_type->name . " request declined";
            $description = "Your leave request of " . $leave_type->name . " has been declined by your " . $declined_by . ".";

            $notification = Notifications::create([
                "title" => $title,
                "description" => $description,
                "module_path" => '/leave-applications',
            ]);

            $user_notification = UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => $leave_application->employee_profile_id,
            ]);

            Helpers::sendNotification([
                "id" => Helpers::getEmployeeID($leave_application->employee_profile_id),
                "data" => new NotificationResource($user_notification)
            ]);

            return response()->json([
                'data' => new LeaveApplicationResource($leave_application),
                'credits' => $result ? $result : [],
                'message' => 'Declined leave application successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function cancelled($id, AuthPinApprovalRequest $request)
    {

        try {
            $user = $request->user;
            $employee_profile = $user;

            $cleanData['pin'] = strip_tags($request->pin);
            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }

            $leave_application = LeaveApplication::find($id);

            if ($leave_application->status === 'cancelled by hrmo') {
                return response()->json(['message' => "You already cancelled this application."], Response::HTTP_FORBIDDEN);
            }

            $leave_type = $leave_application->leaveType;

            $leave_application->update([
                'status' => 'cancelled by hrmo',
                'cancelled_at' => Carbon::now(),
                'remarks' => $request->remarks,
            ]);

            if (!$leave_type->is_special) {
                $employee_credit = EmployeeLeaveCredit::where('employee_profile_id', $leave_application->employee_profile_id)
                    ->where('leave_type_id', $leave_application->leave_type_id)->first();

                $current_leave_credit = $employee_credit->total_leave_credits;
                $current_used_leave_credit = $employee_credit->used_leave_credits;

                $employee_credit->update([
                    'total_leave_credits' => $current_leave_credit + $leave_application->applied_credits,
                    'used_leave_credits' => $current_used_leave_credit - $leave_application->applied_credits
                ]);
            }

            $result = [];
            $employeeCredit = EmployeeLeaveCredit::where('employee_profile_id',  $leave_application->employee_profile_id)->get();

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

            LeaveApplicationLog::create([
                'action_by' => $employee_profile->id,
                'leave_application_id' => $leave_application->id,
                'action' => 'Cancelled by HRMO'
            ]);

            //NOTIFICATIONS
            $title = $leave_type->name . " request cancelled";
            $description = "Your leave request of " . $leave_type->name . " has been cancelled by HRMO.";


            $notification = Notifications::create([
                "title" => $title,
                "description" => $description,
                "module_path" => '/leave-applications',
            ]);

            $user_notification = UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => $leave_application->employee_profile_id,
            ]);

            Helpers::sendNotification([
                "id" => Helpers::getEmployeeID($leave_application->employee_profile_id),
                "data" => new NotificationResource($user_notification)
            ]);

            return response()->json([
                'data' => new LeaveApplicationResource($leave_application),
                'credits' => $result ? $result : [],
                'message' => 'Cancelled leave application successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function cancelUser($id, AuthPinApprovalRequest $request)
    {
        try {
            $user = $request->user;
            $employee_profile = $user;

            $cleanData['pin'] = strip_tags($request->pin);
            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }

            $leave_application = LeaveApplication::find($id);
            $leave_type = $leave_application->leaveType;

            $leave_application->update([
                'status' => 'cancelled by user',
                'cancelled_at' => Carbon::now(),
                'remarks' => $request->remarks,
            ]);

            if (!$leave_type->is_special) {
                $employee_credit = EmployeeLeaveCredit::where('employee_profile_id', $leave_application->employee_profile_id)
                    ->where('leave_type_id', $leave_application->leave_type_id)->first();

                $current_leave_credit = $employee_credit->total_leave_credits;
                $current_used_leave_credit = $employee_credit->used_leave_credits;

                $employee_credit->update([
                    'total_leave_credits' => $current_leave_credit + $leave_application->applied_credits,
                    'used_leave_credits' => $current_used_leave_credit - $leave_application->applied_credits
                ]);
            }
            $result = [];

            $employeeCredit = EmployeeLeaveCredit::where('employee_profile_id',  $leave_application->employee_profile_id)->get();

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

            LeaveApplicationLog::create([
                'action_by' => $employee_profile->id,
                'leave_application_id' => $leave_application->id,
                'action' => 'Cancelled by User'
            ]);



            return response()->json([
                'data' => new LeaveApplicationResource($leave_application),
                'credits' => $result ? $result : [],
                'message' => 'Cancelled leave application successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function received($id, AuthPinApprovalRequest $request)
    {
        try {

            $user = $request->user;
            $employee_profile = $user;
            $cleanData['pin'] = strip_tags($request->pin);

            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }

            $leave_application = LeaveApplication::find($id);
            $leave_type = $leave_application->leaveType;

            if ($leave_application->status === 'cancelled by hrmo') {
                return response()->json(['message' => "You cannot receive a cancelled application."], Response::HTTP_FORBIDDEN);
            }

            if ($leave_application->status === 'received') {
                return response()->json(['message' => "You already received this application."], Response::HTTP_FORBIDDEN);
            }

            $leave_application->update([
                'status' => 'received',
                'received_at' => Carbon::now(),
            ]);

            LeaveApplicationLog::create([
                'action_by' => $employee_profile->id,
                'leave_application_id' => $leave_application->id,
                'action' => 'Received'
            ]);

            //NOTIFICATIONS
            $title = $leave_type->name . " request hard copy received.";
            $description = "Your printed copy leave application form of " . $leave_type->name . " has been received by HRMO.";


            $notification = Notifications::create([
                "title" => $title,
                "description" => $description,
                "module_path" => '/leave-applications',
            ]);

            $user_notification = UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => $leave_application->employee_profile_id,
            ]);

            Helpers::sendNotification([
                "id" => Helpers::getEmployeeID($leave_application->employee_profile_id),
                "data" => new NotificationResource($user_notification)
            ]);

            return response()->json([
                'data' => new LeaveApplicationResource($leave_application),
                'message' => 'Received leave application successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updatePrint($id, Request $request)
    {
        try {
            $user = $request->user;
            $employee_profile = $user;
            $employee_leave_application = $id;
            $employee_print = LeaveApplication::where('id', $employee_leave_application)->first();
            $employee_print->update([
                'is_printed' => 1,
                'print_datetime' => Carbon::now()
            ]);

            LeaveApplicationLog::create([
                'action_by' => $employee_profile->id,
                'leave_application_id' => $employee_print->id,
                'action' => 'Printed'
            ]);
            return response()->json(['data' => new LeaveApplicationResource($employee_print), 'message' => 'Successfully printed'], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function printLeaveForm($id)
    {
        try {
            $doc_title = '';
            $rev_no = '';
            $effective_date = '';
            $is_monetization = false;


            $data = LeaveApplication::with(['employeeProfile', 'leaveType', 'hrmoOfficer', 'recommendingOfficer', 'approvingOfficer'])->where('id', $id)->first();
            $vl_employee_credit = EmployeeLeaveCredit::where('leave_type_id', LeaveType::where('code', 'VL')->first()->id)
                ->where('employee_profile_id', $data->employee_profile_id)
                ->first();
            $sl_employee_credit = EmployeeLeaveCredit::where('leave_type_id', LeaveType::where('code', 'SL')->first()->id)
                ->where('employee_profile_id', $data->employee_profile_id)
                ->first();
            $fl_employee_credit = EmployeeLeaveCredit::where('leave_type_id', LeaveType::where('code', 'FL')->first()->id)
                ->where('employee_profile_id', $data->employee_profile_id)
                ->first();

            // return $data;
            $leave_type = LeaveTypeResource::collection(LeaveType::all());
            $my_leave_type = new LeaveTypeResource(LeaveType::find($data->leave_type_id));
            $hrmo_officer = Section::with(['supervisor'])->where('code', 'HRMO')->first();

            //FETCH DOCUMENT DETAILS
            $document_details = [];

            $isMCC = Division::where('code', 'OMCC')->where('chief_employee_profile_id', $data->employee_profile_id)->first();

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

            $options = new Options();
            $options->set('isPhpEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->getOptions()->setChroot([base_path() . '/public/storage']);
            $html = view('leave_from.leave_application_form', compact('data', 'leave_type', 'hrmo_officer', 'my_leave_type', 'vl_employee_credit', 'sl_employee_credit', 'fl_employee_credit', 'is_monetization', 'document_details'))->render();
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
            $employee_leave_application = $id;
            $employee_print = LeaveApplication::where('id', $employee_leave_application)->first();
            $employee_print->update([
                'is_printed' => 1,
                'print_datetime' => Carbon::now()
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => true]);
        }
    }

    public function reschedule($id, AuthPinApprovalRequest $request)
    {
        try {
            $user = $request->user;
            $employee_profile = $user;

            $cleanData['pin'] = strip_tags($request->pin);
            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }
            $hrmo_officer = Helpers::getHrmoOfficer();
            $start = Carbon::parse($request->date_from);
            $end =  Carbon::parse($request->date_to);
            $checkSchedule = Helpers::hasSchedule($start, $end, $hrmo_officer);
            if (!$checkSchedule) {
                return response()->json(['message' => "You don't have a schedule within the specified date range."], Response::HTTP_FORBIDDEN);
            }

            $overlapExists = Helpers::hasOverlappingRecords($start, $end, $user);

            if ($overlapExists) {
                return response()->json(['message' => 'You already have an application for the same dates.'], Response::HTTP_FORBIDDEN);
            }

            $leave_application = LeaveApplication::find($id);
            $date_from = Carbon::parse($request->date_from);
            $date_to = Carbon::parse($request->date_to);
            $start_date = Carbon::parse($leave_application->date_from);

            if ($start_date->isPast()) {
                return response()->json(['message' => 'Cannot be rescheduled.'], Response::HTTP_FORBIDDEN);
            }
            // Check if the dates are in the past relative to the current date
            if ($date_from->isPast() || $date_to->isPast()) {
                return response()->json(['message' => 'Date cannot be in the past.'], Response::HTTP_FORBIDDEN);
            } else {
                $leave_application->update([
                    'status' => "applied",
                    'reason' => $request->reason,
                    'date_from' => $request->date_from,
                    'date_to' => $request->date_to,
                    'created_at' => Carbon::now(),
                ]);
            }
            $result = [];

            $employeeCredit = EmployeeLeaveCredit::where('employee_profile_id',  $leave_application->employee_profile_id)->get();

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

            LeaveApplicationLog::where('leave_application_id', $leave_application->id)->delete();

            LeaveApplicationLog::create([
                'action_by' => $employee_profile->id,
                'leave_application_id' => $leave_application->id,
                'action' => 'Applied'
            ]);

            return response()->json([
                'data' => new LeaveApplicationResource($leave_application),
                'credits' => $result ? $result : [],
                'message' => 'Rescheduled leave application successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function changeDate($id, AuthPinApprovalRequest $request)
    {
        try {

            $user = $request->user;
            $employee_profile = $user;
            $start = Carbon::parse($request->date_from);
            $end =  Carbon::parse($request->date_to);

            $cleanData['pin'] = strip_tags($request->pin);
            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }

            $leave_application = LeaveApplication::find($id);
            $leave_type = $leave_application->leaveType;
            $updateData = [];


            $overlapExists = Helpers::hasOverlappingRecords($start, $end, $leave_application->employee_profile_id);

            if ($overlapExists) {
                return response()->json(['message' => 'The employee already has an application for the same dates.'], Response::HTTP_FORBIDDEN);
            }

            $checkSchedule = Helpers::hasSchedule($start, $end, $leave_application->employee_profile_id);
            if (!$checkSchedule) {
                return response()->json(['message' => "The employee doesn't have a schedule within the specified date range."], Response::HTTP_FORBIDDEN);
            }

            if (!is_null($request->date_from)) {
                $updateData['date_from'] = $request->date_from;
            }

            if (!is_null($request->date_to)) {
                $updateData['date_to'] = $request->date_to;
            }
            $leave_application->update($updateData);

            $result = [];
            LeaveApplicationLog::create([
                'action_by' => $employee_profile->id,
                'leave_application_id' => $leave_application->id,
                'action' => 'Change Leave Application Date'
            ]);

            //NOTIFICATIONS
            $title = $leave_type->name . " request date change";
            $description = "Your leave request for " . $leave_type->name . " from " . $leave_application->date_from . " to " . $leave_application->date_to . " has been successfully changed.";

            $notification = Notifications::create([
                "title" => $title,
                "description" => $description,
                "module_path" => '/leave-applications',
            ]);

            $user_notification = UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => $leave_application->employee_profile_id,
            ]);

            Helpers::sendNotification([
                "id" => Helpers::getEmployeeID($leave_application->employee_profile_id),
                "data" => new NotificationResource($user_notification)
            ]);

            return response()->json([
                'data' => new LeaveApplicationResource($leave_application),
                'credits' => $result ? $result : [],
                'message' => 'The date on leave application has been successfully changed.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
