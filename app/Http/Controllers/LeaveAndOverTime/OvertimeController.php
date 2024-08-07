<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Http\Controllers\Controller;
use App\Models\Overtime;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Helpers\Helpers;
use App\Http\Resources\OvertimeResource;
use App\Models\OvertimeApplication;
use App\Models\OvtApplicationActivity;
use App\Models\OvtApplicationDatetime;
use App\Models\OvtApplicationEmployee;
use Illuminate\Http\Response;
use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Resources\MyApprovedLeaveApplicationResource;
use App\Http\Resources\NotificationResource;
use App\Jobs\SendEmailJob;
use App\Models\Department;
use App\Models\EmployeeOvertimeCredit;
use App\Models\EmployeeOvertimeCreditLog;
use App\Models\EmployeeProfile;
use App\Models\Notifications;
use App\Models\OvtApplicationLog;
use App\Models\Section;
use App\Models\Unit;
use App\Models\UserNotifications;
use DateTime;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Validator;

class OvertimeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private $CONTROLLER_NAME = 'OvertimeController';

    public function printOvertimeForm($id)
    {

        $overtime_application = OvertimeApplication::where('id', $id)->get();

        $data = json_decode(json_encode(OvertimeResource::collection($overtime_application)))[0];

        $activities = $data->activities;
        $uniqueEmployees = [];

        foreach ($activities as $activity) {
            foreach ($activity->dates as $date) {
                foreach ($date->employees as $employee) {
                    $employeeId = $employee->employee_profile->employee_id;
                    // Check if employee already exists in the uniqueEmployees array
                    if (!isset($uniqueEmployees[$employeeId])) {
                        // If not, add the employee to the uniqueEmployees array
                        $uniqueEmployees[$employeeId] = $employee->employee_profile;
                    }
                }
            }
        }

        $listofEmployees = array_values($uniqueEmployees);
        $purposeofovertime = $data->purpose;
        $remarks = $data->remarks;
        $recommendingofficer = $data->recommending_officer;
        $approvingOfficer = $data->approving_officer;
        $requestedBy =  $data->employee_profile;

        $created = date("F j, Y", strtotime($data->created_at));

        $options = new Options();
        $options->set('isPhpEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->getOptions()->setChroot([base_path() . '\public\storage']);
        $dompdf->loadHtml(view(
            "overtimeAuthority",
            compact(
                'listofEmployees',
                'activities',
                'purposeofovertime',
                'recommendingofficer',
                'requestedBy',
                'approvingOfficer',
                'created'
            )
        ));

        $dompdf->setPaper('Letter', 'portrait');
        $dompdf->render();

        $filename = "OvertimeAuthority_{$purposeofovertime}.pdf";
        $dompdf->stream($filename);
    }

    public function printPastOvertimeForm($id)
    {



        $overtime_application = OvertimeApplication::where('id', $id)->get();

        $data = json_decode(json_encode(OvertimeResource::collection($overtime_application)))[0];



        $dates = $data->dates[0];
        $preparedBy = $data->employee_profile;
        $recommendingOfficer = $data->recommending_officer;
        $approvingOfficer = $data->approving_officer;
        $created_at = $data->created_at;
        $updated_at = $data->updated_at;
        // $employees = $dates->employees;


        $time_from = $dates->time_from;
        $time_to = $dates->time_to;
        $date = $dates->date;
        $employees = $dates->employees[0];
        $requested = $dates->created_at;



        // return view("overtimePast",compact(
        // 'employees',
        // 'time_from',
        // 'time_to',
        // 'preparedBy',
        // 'recommendingOfficer',
        // 'approvingOfficer'));


        $options = new Options();
        $options->set('isPhpEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->getOptions()->setChroot([base_path() . '\public\storage']);
        $dompdf->loadHtml(view("overtimePast", compact(
            'employees',
            'time_from',
            'time_to',
            'preparedBy',
            'recommendingOfficer',
            'approvingOfficer'
        )));

        $dompdf->setPaper('Letter', 'landscape');
        $dompdf->render();

        $filename = "OvertimeAuthority_{$employees->employee_profile->name}.pdf";
        $dompdf->stream($filename);
    }

    public function index(Request $request)
    {
        try {

            $employee_profile = $request->user;
            $employee_area = $employee_profile->assignedArea->findDetails();
            $recommending = ["for recommending approval", "for approving approval", "approved", "declined by recommending officer"];
            $approving = ["for approving approval", "approved", "declined by approving officer"];
            $position = $employee_profile->position();
            $employeeId = $employee_profile->id;

            // /** FOR NORMAL EMPLOYEE */
            // if ($employee_profile->position() === null || $employee_profile->position()['position'] === 'Supervisor') {

            //     $overtime_application = OvertimeApplication::with('dates')->where('employee_profile_id', $employee_profile->id)->get();

            //     return response()->json([
            //         'data' => OvertimeResource::collection($overtime_application),
            //         'message' => 'Retrieved all overtime application'
            //     ], Response::HTTP_OK);
            // }

            //    return response()->json(['message' => $employee_profile->position()['position']], Response::HTTP_INTERNAL_SERVER_ERROR);
            // if ($employee_profile->id === Helpers::getHrmoOfficer()) {
            //     return response()->json([
            //         'data' => OvertimeApplication::collection(OvertimeApplication::where('status', 'approved')->get()),
            //         'message' => 'Retrieved all overtime  application'

            //     ], Response::HTTP_OK);
            // }
            if ($employeeId == 1) {
                $overtime_application = OvertimeApplication::select('overtime_applications.*')
                    ->groupBy(
                        'id',
                        'employee_profile_id',
                        'status',
                        'purpose',
                        'recommending_officer',
                        'approving_officer',
                        'overtime_letter_of_request',
                        'overtime_letter_of_request_path',
                        'overtime_letter_of_request_size',
                        'remarks',
                        'decline_reason',
                        'created_at',
                        'updated_at',
                    )
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
            else
            {
                $overtime_application = OvertimeApplication::select('overtime_applications.*')
                ->where(function ($query) use ($recommending, $approving, $employeeId) {
                    $query->whereIn('overtime_applications.status', $recommending)
                        ->where('overtime_applications.recommending_officer', $employeeId);
                })
                ->orWhere(function ($query) use ($recommending, $approving, $employeeId) {
                    $query->whereIn('overtime_applications.status', $approving)
                        ->where('overtime_applications.approving_officer', $employeeId);
                })
                ->groupBy(
                    'id',
                    'employee_profile_id',
                    'status',
                    'purpose',
                    'recommending_officer',
                    'approving_officer',
                    'overtime_letter_of_request',
                    'overtime_letter_of_request_path',
                    'overtime_letter_of_request_size',
                    'remarks',
                    'decline_reason',
                    'created_at',
                    'updated_at',
                )
                ->orderBy('created_at', 'desc')
                ->get();

            }

            return response()->json([
                'user_id' => $employeeId,
                'data' => OvertimeResource::collection($overtime_application),
                'message' => 'Retrieved all official business application'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function getSupervisor(Request $request)
    {
        try {

            $employee_profile = $request->user;
            $employeeId = $employee_profile->id;

            /** FOR NORMAL EMPLOYEE */
            if ($employee_profile->position() === null || $employee_profile->position()['position'] === 'Supervisor') {

                $overtime_application = OvertimeApplication::with('dates')->where('employee_profile_id', $employee_profile->id)->orderBy('created_at', 'desc')->get();
                return response()->json([
                    'user_id' => $employeeId,
                    'data' => OvertimeResource::collection($overtime_application),
                    'message' => 'Retrieved all overtime application'
                ], Response::HTTP_OK);
            }

            //    return response()->json(['message' => $employee_profile->position()['position']], Response::HTTP_INTERNAL_SERVER_ERROR);
            if ($employee_profile->id === Helpers::getHrmoOfficer()) {
                return response()->json([
                    'data' => OvertimeApplication::collection(OvertimeApplication::where('status', 'approved')->get()),
                    'message' => 'Retrieved all overtime  application'

                ], Response::HTTP_OK);
            }
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function myApprovedOvertimeApplication(Request $request)
    {
        try {
            $employee_profile = $request->user;
            $overtime_applications = OvertimeApplication::where('status', 'approved')
                ->where('employee_profile_id', $employee_profile->id)->get();

            return response()->json([
                'data' => OvertimeResource::collection($overtime_applications),
                'message' => 'Retrieve list.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function myOvertimeApplication(Request $request)
    {
        try {
            $employee_profile = $request->user;

            $overtime_applications = OvertimeApplication::where('employee_profile_id', $employee_profile->id)->get();

            return response()->json([
                'data' => OvertimeResource::collection($overtime_applications),
                'message' => 'Retrieve list.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeeApprovedOvertimeApplication($id, Request $request)
    {
        try {
            $overtime_applications = OvertimeApplication::where('status', 'approved')->where('employee_profile_id', $id)->get();

            return response()->json([
                'data' => Overtime::collection($overtime_applications),
                'message' => 'Retrieve list.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approvedOvertimeRequest(Request $request)
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

            $units_overtime_applications = OvertimeApplication::select("overtime_applications.*")
                ->join('employee_profiles as ep', 'overtime_applications.employee_profile_id', 'ep.id')
                ->join('assigned_areas as aa', 'ep.id', 'aa.employee_profile_id')
                ->join('units as u', 'aa.unit_id', 'u.id')
                ->join('sections as s', 'u.section_id', 's.id')
                ->join('departments as d', 's.department_id', 'd.id')
                ->join('divisions as dv', 'd.division_id', 'dv.id')
                ->select('overtime_applications.*')
                ->where('dv.id', $division_id)
                ->where('overtime_applications.status', 'approved')
                ->get();

            $sections_overtime_applications = OvertimeApplication::select("overtime_applications.*")
                ->join('employee_profiles as ep', 'overtime_applications.employee_profile_id', 'ep.id')
                ->join('assigned_areas as aa', 'ep.id', 'aa.employee_profile_id')
                ->join('sections as s', 'aa.section_id', 's.id')
                ->join('departments as d', 's.department_id', 'd.id')
                ->join('divisions as dv', 'd.division_id', 'dv.id')
                ->select('overtime_applications.*')
                ->where('dv.id', $division_id)
                ->where('overtime_applications.status', 'approved')
                ->get();

            $departments_overtime_applications = OvertimeApplication::select("overtime_applications.*")
                ->join('employee_profiles as ep', 'overtime_applications.employee_profile_id', 'ep.id')
                ->join('assigned_areas as aa', 'ep.id', 'aa.employee_profile_id')
                ->join('departments as d', 'aa.department_id', 'd.id')
                ->join('divisions as dv', 'd.division_id', 'dv.id')
                ->select('overtime_applications.*')
                ->where('dv.id', $division_id)
                ->where('overtime_applications.status', 'approved')
                ->get();

            $divisions_overtime_applications = OvertimeApplication::select("overtime_applications.*")
                ->join('employee_profiles as ep', 'overtime_applications.employee_profile_id', 'ep.id')
                ->join('assigned_areas as aa', 'ep.id', 'aa.employee_profile_id')
                ->join('divisions as dv', 'aa.division_id', 'dv.id')
                ->select('overtime_applications.*')
                ->where('dv.id', $division_id)
                ->where('overtime_applications.status', 'approved')
                ->get();

            $overtime_applications = [
                ...$units_overtime_applications,
                ...$sections_overtime_applications,
                ...$departments_overtime_applications,
                ...$divisions_overtime_applications
            ];

            return response()->json([
                'data' => OvertimeResource::collection($overtime_applications),
                'message' => 'Retrieve list.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approvedOvertimeApplication()
    {
        try {
            $overtime_applications = OvertimeApplication::where('status', 'approved')->get();
            return response()->json([
                'data' => OvertimeResource::collection($overtime_applications),
                'message' => 'Retrieve list.'
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
            $status = "";
            $cleanData['pin'] = strip_tags($request->pin);

            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Invalid authorization pin."], Response::HTTP_FORBIDDEN);
            }

            $overtime_application = OvertimeApplication::find($id);
            $overtime_type = $overtime_application->overtimeType;
            $overtime_application_hrmo = $overtime_application->hrmo_officer;
            $overtime_application_recommending = $overtime_application->recommending_officer;
            $overtime_application_approving = $overtime_application->approving_officer;

            if ($employee_profile->id === $overtime_application_recommending) {
                $status = 'declined by recommending officer';
                $declined_by = "Recommending officer";
            } else if ($employee_profile->id === $overtime_application_approving) {
                $status = 'declined by approving officer';
                $declined_by = "Approving officer";
            }

            $overtime_application->update([
                'status' => $status,
                'remarks' => strip_tags($request->remarks),
            ]);

            return response()->json([
                'data' => new OvertimeResource($overtime_application),
                'message' => 'Declined overtime application successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request, AuthPinApprovalRequest $pin)
    {
        try {
            $employee_profile = $request->user;

            $cleanData['pin'] = strip_tags($pin->pin);
            if ($employee_profile['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }
            $employeeId = $employee_profile->id;

            $validatedData = $request->validate([
                'dates.*' => 'required',
                'activities.*' => 'required',
                'time_from.*' => 'required',
                'time_to.*' => [
                    'required',
                    function ($attribute, $value, $fail) use ($request) {
                        $index = explode('.', $attribute)[1];
                        $timeFrom = $request->input('time_from.*' . $index);
                        if ($value < $timeFrom) {
                            $fail("The time to must be greater than time from.");
                        }
                    },
                ],
                //'letter_of_request' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
                'purpose.*' => 'required',
                'remarks.*' => 'required',
                'quantities.*' => 'required',
                'employees.*' => 'required',
            ]);



            $user = $request->user;
            $path = "";
            $fileName = "";
            $file_name_encrypted = "";
            $size = "";


            $assigned_area = $employee_profile->assignedArea->findDetails();
            if (Helpers::getDivHead($assigned_area) === null || Helpers::getChiefOfficer() === null) {
                return response()->json(['message' => 'No recommending officer and/or supervising officer assigned.'], Response::HTTP_FORBIDDEN);
            }


            foreach ($validatedData['employees'] as $index => $employeeList) {

                foreach ($employeeList as $dateIndex => $employeeIdList) {

                    foreach ($employeeIdList as $employeeId) {

                        $employeeProfile = EmployeeProfile::with('overtimeCredits')->find($employeeId);
                        // Check for overlapping CTO records
                        $date = $validatedData['dates'][$index][$dateIndex];

                        if (Helpers::hasOverlappingCTO($date, $employeeId)) {
                            return response()->json(['message' => 'Overlapping application records found for employee ' . $employeeProfile->name() . ' on date ' . $date . '.'], Response::HTTP_BAD_REQUEST);
                        }
                        // Retrieve employee's profile using the employee ID


                        // Get the current year and the next year
                        $currentYear = date('Y');
                        $nextYear = $currentYear + 1;

                        // Calculate the valid until date (next year's December 31st)
                        $validUntil = $nextYear . '-12-31';
                        // Ensure the relationship is defined in the EmployeeProfile model

                        // Calculate the total overtime hours requested by the employee
                        $totalOvertimeHours = 0;

                        $timeFrom = $validatedData['time_from'][$index][$dateIndex];
                        $timeTo = $validatedData['time_to'][$index][$dateIndex];
                        $totalOvertimeHours += $this->calculateOvertimeHours($timeFrom, $timeTo);

                        // Calculate the total earned credit accumulated including the current overtime application
                        $totalEarnedCredit = $employeeProfile->earned_credit_by_hour + $totalOvertimeHours;

                        // Compare with max_credit_monthly and max_credit_annual including valid_until
                        if ($totalOvertimeHours > $employeeProfile->overtimeCredits[0]->max_credit_monthly && $validUntil == $employeeProfile->overtimeCredits[0]->valid_until) {
                            // Handle exceeding max_credit_monthly
                            return response()->json(['message' => 'Employee ' . $employeeId . ' has exceeded the monthly overtime credit.'], Response::HTTP_BAD_REQUEST);
                        }

                        if ($totalEarnedCredit > $employeeProfile->overtimeCredits[0]->max_credit_annual && $validUntil == $employeeProfile->overtimeCredits[0]->valid_until) {
                            // Handle exceeding max_credit_annual
                            return response()->json(['message' => 'Employee ' . $employeeId . ' has exceeded the annual overtime credit.'], Response::HTTP_BAD_REQUEST);
                        }
                    }
                }
            }

            $assigned_area = $employee_profile->assignedArea->findDetails();
            $status = 'for recommending approval';
            $overtime_application = OvertimeApplication::create([
                'employee_profile_id' => $user->id,
                'status' => $status,
                'purpose' => $request->purpose,
                'overtime_letter_of_request' =>  $fileName,
                'overtime_letter_of_request_path' =>  $file_name_encrypted,
                'overtime_letter_of_request_size' =>  $size,
                'recommending_officer' => Helpers::getDivHead($assigned_area),
                'approving_officer' => Helpers::getChiefOfficer(),
            ]);

            $ovt_id = $overtime_application->id;
            foreach ($validatedData['activities'] as $index => $activities) {
                $activity_application = OvtApplicationActivity::create([
                    'overtime_application_id' => $ovt_id,
                    'name' => $activities,
                    'quantity' => $validatedData['quantities'][$index],
                ]);

                foreach ($validatedData['dates'][$index] as $dateIndex => $date) {
                    $date_application = OvtApplicationDatetime::create([
                        'ovt_application_activity_id' => $activity_application->id,
                        'time_from' => $validatedData['time_from'][$index][$dateIndex],
                        'time_to' => $validatedData['time_to'][$index][$dateIndex],
                        'date' => $date,
                    ]);



                    foreach ($validatedData['employees'][$index][$dateIndex] as $employee) {
                        OvtApplicationEmployee::create([
                            'ovt_application_datetime_id' => $date_application->id,
                            'employee_profile_id' => $employee,
                            // 'remarks' => $validatedData['remarks'][$index][$dateIndex][$employeeIndex],
                        ]);
                    }
                }
            }
            OvtApplicationLog::create([
                'overtime_application_id' => $ovt_id,
                'action_by_id' => $employee_profile->id,
                'action' => 'Applied'
            ]);

            $recommending = EmployeeProfile::where('id', Helpers::getDivHead($assigned_area))->first();
            $email = $recommending->personalinformation->contact->email_address;
            $name = $recommending->personalInformation->name();

            $data = [
                'name' =>  'Division Head',
                'employeeName' =>  $employee_profile->personalInformation->name(),
                'employeeID' => $employee_profile->employee_id,
                "Link" => config('app.client_domain')
            ];

            SendEmailJob::dispatch('overtime_request', $email, $name, $data);

            $title = "New Overtime Request";
            $description = $user->personalInformation->name() . " filed a new overtime request";

            $notification = Notifications::create([
                "title" => $title,
                "description" => $description,
                "module_path" => '/head-overtime-requests',
            ]);

            $user_notification = UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => $recommending->id,
            ]);

            Helpers::sendNotification([
                "id" => Helpers::getEmployeeID($recommending->id),
                "data" => new NotificationResource($user_notification)
            ]);
            return response()->json([
                'message' => 'Overtime Application has been sucessfully saved',
                //  'data' => OvertimeResource::collection($overtime_application),

            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function storePast(Request $request, AuthPinApprovalRequest $pin)
    {
        try {

            $user = $request->user;
            $employee_profile = $request->user;
            return  $employeeId = $employee_profile->id;
            $validatedData = $request->validate([
                'dates.*' => 'required',
                'time_from.*' => 'required',
                'time_to.*' => [
                    'required',
                    function ($attribute, $value, $fail) use ($request) {
                        $index = explode('.', $attribute)[1];
                        $timeFrom = $request->input('time_from.' . $index);
                        if ($value < $timeFrom) {
                            $fail("The time to must be greater than time from.");
                        }
                    },
                ],
                'remarks.*' => 'required',
                'employees.*' => 'required',

            ]);

            $cleanData['pin'] = strip_tags($pin->pin);
            if ($employee_profile['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }
            $assigned_area = $employee_profile->assignedArea->findDetails();
            if (Helpers::getDivHead($assigned_area) === null || Helpers::getChiefOfficer() === null) {
                return response()->json(['message' => 'No recommending officer and/or supervising officer assigned.'], Response::HTTP_FORBIDDEN);
            }


            foreach ($validatedData['dates'] as $index => $date) {
                $employeeId = $validatedData['employees'][$index];
                // Check for overlapping CTO records
                $date = $validatedData['dates'][$index];
                // Retrieve employee's profile using the employee ID
                $employeeProfile = EmployeeProfile::with('overtimeCredits')->find($employeeId);
                if (Helpers::hasOverlappingCTO($date, $employeeId)) {
                    return response()->json(['message' => 'Overlapping application records found for employee ' . $employeeProfile->name() . ' on date ' . $date . '.'], Response::HTTP_BAD_REQUEST);
                }


                // Get the current year and the next year
                $currentYear = date('Y');
                $nextYear = $currentYear + 1;

                // Calculate the valid until date (next year's December 31st)
                $validUntil = $nextYear . '-12-31';

                // Calculate the total overtime hours requested by the employee
                $totalOvertimeHours = $this->calculateOvertimeHours(
                    $validatedData['time_from'][$index],
                    $validatedData['time_to'][$index]
                );

                // Calculate the total earned credit accumulated including the current overtime application
                $totalEarnedCredit = $employeeProfile->earned_credit_by_hour + $totalOvertimeHours;

                // Compare with max_credit_monthly and max_credit_annual including valid_until
                if ($totalOvertimeHours > $employeeProfile->overtimeCredits[0]->max_credit_monthly && $validUntil == $employeeProfile->overtimeCredits[0]->valid_until) {
                    // Handle exceeding max_credit_monthly
                    return response()->json(['message' => 'Employee ' . $employeeId . ' has exceeded the monthly overtime credit.'], Response::HTTP_BAD_REQUEST);
                }

                if ($totalEarnedCredit > $employeeProfile->overtimeCredits[0]->max_credit_annual && $validUntil == $employeeProfile->overtimeCredits[0]->valid_until) {
                    // Handle exceeding max_credit_annual
                    return response()->json(['message' => 'Employee ' . $employeeId . ' has exceeded the annual overtime credit.'], Response::HTTP_BAD_REQUEST);
                }
            }

            $status = 'for recommending approval';
            $overtime_application = OvertimeApplication::create([
                'employee_profile_id' => $user->id,
                'status' => $status,
                'purpose' => $request->purpose,
                'recommending_officer' => Helpers::getDivHead($assigned_area),
                'approving_officer' => Helpers::getChiefOfficer(),
            ]);
            $ovt_id = $overtime_application->id;

            foreach ($validatedData['dates'] as $index => $date) {
                $date_application = OvtApplicationDatetime::create([
                    'overtime_application_id' => $ovt_id,
                    'time_from' =>  $validatedData['time_from'][$index],
                    'time_to' =>  $validatedData['time_to'][$index],
                    'date' =>  $date,
                ]);
                $date_id = $date_application->id;
                foreach ($validatedData['employees'] as $index => $employees) {
                    OvtApplicationEmployee::create([
                        'ovt_application_datetime_id' => $date_id,
                        'employee_profile_id' =>  $validatedData['employees'][$index],
                        'remarks' =>  $validatedData['remarks'][$index],
                    ]);
                }
            }

            OvtApplicationLog::create([
                'overtime_application_id' => $ovt_id,
                'action_by_id' => $employee_profile->id,
                'action' => 'Applied'
            ]);


            $recommending = EmployeeProfile::where('id', Helpers::getDivHead($assigned_area))->first();
            $email = $recommending->personalinformation->contact->email_address;
            $name = $recommending->personalInformation->name();

            $data = [
                'name' =>  'Division Head',
                'employeeName' =>  $employee_profile->personalInformation->name(),
                'employeeID' => $employee_profile->employee_id,
                "Link" => config('app.client_domain')
            ];

            SendEmailJob::dispatch('overtime_request', $email, $name, $data);

            $title = "New Overtime Request";
            $description = $user->personalInformation->name() . " filed a new overtime request";

            $notification = Notifications::create([
                "title" => $title,
                "description" => $description,
                "module_path" => '/head-overtime-requests',
            ]);

            $user_notification = UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => $recommending->id,
            ]);

            Helpers::sendNotification([
                "id" => Helpers::getEmployeeID($recommending->id),
                "data" => new NotificationResource($user_notification)
            ]);

            return response()->json([
                'message' => 'Overtime Application has been sucessfully saved',
                //'data' => OvertimeResource::collection($overtime_application),

            ], Response::HTTP_OK);
        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'storePast', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function storeBulk(Request $request, AuthPinApprovalRequest $pin)
    {
        try {
            $employee_profile = $request->user;

            $cleanData['pin'] = strip_tags($request->pin);
            if ($employee_profile['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }
            $employeeId = $employee_profile->id;
            $validator = Validator::make($request->all(), [
                'otID' => 'required|integer',
                'purposeofovertime' => 'required|string',
                'listdate' => 'required|array',
                'listdate.*.day' => 'required|integer',
                'listdate.*.month' => 'required|integer',
                'listdate.*.year' => 'required|integer',
                'employees' => 'required|array',
                'employees.*.otID' => 'required|integer',
                'employees.*.empName' => 'required|string',
                'employees.*.id' => 'required|integer',
                'activitiestobeaccomplished' => 'required|string',
                'estqty' => 'required|integer',
                'fromtime' => 'required|string',
                'totime' => 'required|string',
            ]);

            $user = $request->user;
            $fileName = "";
            $file_name_encrypted = "";
            $size = "";

            $cleanData['pin'] = strip_tags($pin->pin);
            if ($employee_profile['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }
            $assigned_area = $employee_profile->assignedArea->findDetails();
            if (Helpers::getDivHead($assigned_area) === null || Helpers::getChiefOfficer() === null) {
                return response()->json(['message' => 'No recommending officer and/or supervising officer assigned.'], Response::HTTP_FORBIDDEN);
            }
            $jsonData = $request->input('data');
            $formData = json_decode($jsonData, true);
            foreach ($formData as $data) {
                foreach ($data['employees'] as $employee) {
                    $employeeId = $employee['id'];
                    $employeeProfile = EmployeeProfile::with('overtimeCredits')->find($employeeId);

                    foreach ($data['listdate'] as $date) {
                        $combinedDate = Carbon::createFromDate(
                            $date['year'],
                            $date['month'],
                            $date['day']
                        )->toDateString();
                        // Construct the date from year, month, and day
                        $date = $combinedDate;

                        if (Helpers::hasOverlappingCTO($date, $employeeId)) {
                            return response()->json(['message' => 'Overlapping application records found for employee ' . $employeeProfile->name() . ' on date ' . $date . '.'], Response::HTTP_BAD_REQUEST);
                        }

                        // Calculate the total overtime hours requested by the employee
                        $totalOvertimeHours = $this->calculateOvertimeHours($data['fromtime'],  $data['totime'],);
                        $totalEarnedCredit = $employeeProfile->earned_credit_by_hour + $totalOvertimeHours;

                        $currentYear = date('Y');
                        $nextYear = $currentYear + 1;
                        $validUntil = $nextYear . '-12-31';

                        if ($totalOvertimeHours > $employeeProfile->overtimeCredits[0]->max_credit_monthly && $validUntil == $employeeProfile->overtimeCredits[0]->valid_until) {
                            return response()->json(['message' => 'Employee ' . $employeeId . ' has exceeded the monthly overtime credit.'], Response::HTTP_BAD_REQUEST);
                        }

                        if ($totalEarnedCredit > $employeeProfile->overtimeCredits[0]->max_credit_annual && $validUntil == $employeeProfile->overtimeCredits[0]->valid_until) {
                            return response()->json(['message' => 'Employee ' . $employeeId . ' has exceeded the annual overtime credit.'], Response::HTTP_BAD_REQUEST);
                        }
                    }
                }
            }

            $assigned_area = $employee_profile->assignedArea->findDetails();
            $status = 'for recommending approval';
            $overtime_application = OvertimeApplication::create([
                'employee_profile_id' => $user->id,
                'status' => $status,
                'purpose' => $request->purpose,
                'overtime_letter_of_request' =>  $fileName,
                'overtime_letter_of_request_path' =>  $file_name_encrypted,
                'overtime_letter_of_request_size' =>  $size,
                'recommending_officer' => Helpers::getDivHead($assigned_area),
                'approving_officer' => Helpers::getChiefOfficer(),
            ]);

            $ovt_id = $overtime_application->id;
            $jsonData = $request->input('data');
            $formData = json_decode($jsonData, true);
            foreach ($formData as $data) {

                $activity_application = OvtApplicationActivity::create([
                    'overtime_application_id' => $ovt_id,
                    'name' => $data['activitiestobeaccomplished'],
                    'quantity' => $data['estqty'],
                ]);
                foreach ($data['listdate'] as $date) {
                    $combinedDate = Carbon::createFromDate(
                        $date['year'],
                        $date['month'],
                        $date['day']
                    )->toDateString();
                    $date_application = OvtApplicationDatetime::create([
                        'ovt_application_activity_id' => $activity_application->id,
                        'time_from' => $data['fromtime'],
                        'time_to' => $data['totime'],
                        'date' => $combinedDate,
                    ]);
                    foreach ($data['employees'] as $employee) {
                        OvtApplicationEmployee::create([
                            'ovt_application_datetime_id' => $date_application->id,
                            'employee_profile_id' => $employee['id'],
                        ]);
                    }
                }
            }
            OvtApplicationLog::create([
                'overtime_application_id' => $ovt_id,
                'action_by_id' => $employee_profile->id,
                'action' => 'Applied'
            ]);

            $recommending = EmployeeProfile::where('id', Helpers::getDivHead($assigned_area))->first();
            $email = $recommending->personalinformation->contact->email_address;
            $name = $recommending->personalInformation->name();

            $data = [
                'name' =>  'Division Head',
                'employeeName' =>  $employee_profile->personalInformation->name(),
                'employeeID' => $employee_profile->employee_id,
                "Link" => config('app.client_domain')
            ];

            SendEmailJob::dispatch('overtime_request', $email, $name, $data);

            $title = "New Overtime Request";
            $description = $user->personalInformation->name() . " filed a new overtime request";

            $notification = Notifications::create([
                "title" => $title,
                "description" => $description,
                "module_path" => '/head-overtime-requests',
            ]);

            $user_notification = UserNotifications::create([
                'notification_id' => $notification->id,
                'employee_profile_id' => $recommending->id,
            ]);

            Helpers::sendNotification([
                "id" => Helpers::getEmployeeID($recommending->id),
                "data" => new NotificationResource($user_notification)
            ]);
            return response()->json([
                'message' => 'Overtime Application has been sucessfully saved',
                //  'data' => OvertimeResource::collection($overtime_application),

            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function calculateOvertimeHours($startTime, $endTime)
    {
        $start = new DateTime($startTime);
        $end = new DateTime($endTime);
        $interval = $start->diff($end);
        $hours = $interval->h;
        $minutes = $interval->i;
        $totalHours = $hours + ($minutes / 60);
        return $totalHours;
    }

    public function approved($id, AuthPinApprovalRequest $request)
    {
        try {
            $data = OvertimeApplication::findOrFail($id);

            if (!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $status = null;
            $log_action = null;
            $employee_profile = $request->user;

            $cleanData['pin'] = strip_tags($request->pin);


            if ($employee_profile['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }
            switch ($data->status) {
                case 'for recommending approval':
                    $status = 'for approving approval';
                    $log_action = 'Approved by Recommending Officer';
                    break;

                case 'for approving approval':
                    $status = 'approved';
                    $log_action = 'Approved by Approving Officer';
                    break;
            }
            $data->update(['status' => $status]);
            OvtApplicationLog::create([
                'overtime_application_id' => $data->id,
                'action_by_id' => $employee_profile->id,
                'action' => $log_action
            ]);

            $approving = EmployeeProfile::where('id', $data->approving_officer)->first();
            $email = $approving->personalinformation->contact->email_address;
            $name = $approving->personalInformation->name();

            $data = [
                'name' =>  'MCC Chief',
                'employeeName' =>  $employee_profile->personalInformation->name(),
                'employeeID' => $employee_profile->employee_id,
                "Link" => config('app.client_domain')
            ];

            SendEmailJob::dispatch('overtime_request', $email, $name, $data);

            return response()->json([
                'data' => new OvertimeResource($data),
                'message' => $log_action,
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'update', $th->getMessage());
            return response()->json(['msg' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function calculateTotal($date)
    {

        $carbonDate = Carbon::parse($date);
        if ($carbonDate->isWeekend()) {
            return 1.5;
        } else {
            return 1;
        }
    }

    public function getUserOvertime(Request $request)
    {
        try {
            $employee_profile = $request->user;
            $employeeId = $employee_profile->id;
            $overtime_applications = OvertimeApplication::with(['dates.employees', 'activities.dates.employees'])

                ->where(function ($query) use ($employeeId) {
                    // Scenario 1: Direct connection to Dates
                    $query->whereHas('dates.employees', function ($query) use ($employeeId) {
                        $query->where('employee_profile_id', $employeeId);
                    })
                        // Scenario 2: Indirect connection through Activities and Dates
                        ->orWhereHas('activities.dates.employees', function ($query) use ($employeeId) {
                            $query->where('employee_profile_id', $employeeId);
                        });
                })
                ->orderBy('created_at', 'desc')
                ->get();
            return response()->json([
                'data' => OvertimeResource::collection($overtime_applications),
                'message' => 'Retrieved all overtime application'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id, Request $request)
    {
        try {
            $overtime_application = OvertimeApplication::find($id);

            if (!$overtime_application) {
                return response()->json(['message' => "No overtime application record."], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => new OvertimeResource($overtime_application),
                'message' => 'Retrieve overtime application record.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    // public function printOvertimeForm($id)
    // {
    //     try {
    //        return $data = OvertimeApplication::with([
    //             'employeeProfile',
    //             'recommendingOfficer',
    //             'approvingOfficer',
    //             'activities',
    //             'activities.dates',
    //             'activities.dates.employees'
    //         ])->where('id', $id)->first();


    //         $is_monetization = false;
    //         $options = new Options();
    //         $options->set('isPhpEnabled', true);
    //         $options->set('isHtml5ParserEnabled', true);
    //         $options->set('isRemoteEnabled', true);
    //         $dompdf = new Dompdf($options);
    //         $dompdf->getOptions()->setChroot([base_path() . '/public/storage']);
    //         $html = view('leave_from.leave_application_form', compact('data', 'leave_type', 'hrmo_officer', 'my_leave_type', 'vl_employee_credit', 'sl_employee_credit', 'is_monetization'))->render();
    //         $dompdf->loadHtml($html);

    //         $dompdf->setPaper('Legal', 'portrait');
    //         $dompdf->render();
    //         $filename = 'OVERTIME AUTHORITY FORM - (' . $data->employeeProfile->personalInformation->name() . ').pdf';
    //         $dompdf->stream($filename, array('Attachment' => false));


    //     } catch (\Exception $e) {
    //         return response()->json(['message' => $e->getMessage(), 'error' => true]);
    //     }
    // }
}
