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
use App\Models\Department;
use App\Models\EmployeeOvertimeCredit;
use App\Models\EmployeeOvertimeCreditLog;
use App\Models\EmployeeProfile;
use App\Models\OvtApplicationLog;
use App\Models\Section;
use App\Models\Unit;
use DateTime;

class OvertimeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private $CONTROLLER_NAME = 'OvertimeController';

    public function index(Request $request)
    {
        try {

            $employee_profile = $request->user;
            $employee_area = $employee_profile->assignedArea->findDetails();
            $recommending = ["for recommending approval", "for approving approval", "approved", "declined by recommending officer"];
            $approving = ["for approving approval", "approved", "declined by approving officer"];
            $position = $employee_profile->position();
            $employeeId = $employee_profile->id;

            /** FOR NORMAL EMPLOYEE */
            if ($employee_profile->position() === null) {
                $overtime_application = OvertimeApplication::where('employee_profile_id', $employee_profile->id)->get();

                return response()->json([
                    'data' => OvertimeResource::collection($overtime_application),
                    'message' => 'Retrieved all overtime application'
                ], Response::HTTP_OK);
            }

            if ($employee_profile->id === Helpers::getHrmoOfficer()) {
                return response()->json([
                    'data' => OvertimeApplication::collection(OvertimeApplication::where('status', 'approved')->get()),
                    'message' => 'Retrieved all offical business application'
                ], Response::HTTP_OK);
            }

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

            return response()->json([
                'data' => OvertimeResource::collection($overtime_application),
                'message' => 'Retrieved all official business application'
            ], Response::HTTP_OK);
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
            $overtime_applications = OvertimeApplication::where('status', 'approved')->where('employee_profile_id', $employee_profile->id)->get();

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
            $cleanData['pin'] = strip_tags($request->password);

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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $employee_profile = $request->user;
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
            $recommending_and_approving = Helpers::getRecommendingAndApprovingOfficer($employee_profile->assignedArea->findDetails(), $employee_profile->id);

            if ($recommending_and_approving === null || $recommending_and_approving['recommending_officer'] === null || $recommending_and_approving['approving_officer'] === null) {
                return response()->json(['message' => 'No recommending officer and/or supervising officer assigned.'], Response::HTTP_FORBIDDEN);
            }
            foreach ($validatedData['employees'] as $index => $employeeList) {
                foreach ($employeeList as $dateIndex => $employeeIdList) {
                    foreach ($employeeIdList as $employeeId) {
                        // Retrieve employee's profile using the employee ID
                        $employeeProfile = EmployeeProfile::with('overtimeCredit')->find($employeeId);
                        // Get the current year and the next year
                        $currentYear = date('Y');
                        $nextYear = $currentYear + 1;

                        // Calculate the valid until date (next year's December 31st)
                        $validUntil = $nextYear . '-12-31';
                        // Ensure the relationship is defined in the EmployeeProfile model

                        // Calculate the total overtime hours requested by the employee
                        $totalOvertimeHours = 0;
                        foreach ($validatedData['dates'][$index][$dateIndex] as $date) {
                            $totalOvertimeHours += $this->calculateOvertimeHours($validatedData['time_from'][$index][$dateIndex], $validatedData['time_to'][$index][$dateIndex]);
                        }

                        // Calculate the total earned credit accumulated including the current overtime application
                        $totalEarnedCredit = $employeeProfile->earned_credit_by_hour + $totalOvertimeHours;

                        // Compare with max_credit_monthly and max_credit_annual including valid_until
                        if ($totalOvertimeHours > $employeeProfile->overtimeCredit->max_credit_monthly && $validUntil == $employeeProfile->overtimeCredit->valid_until) {
                            // Handle exceeding max_credit_monthly
                            return response()->json(['message' => 'Employee ' . $employeeId . ' has exceeded the monthly overtime credit.'], Response::HTTP_BAD_REQUEST);
                        }

                        if ($totalEarnedCredit > $employeeProfile->overtimeCredit->max_credit_annual && $validUntil == $employeeProfile->overtimeCredit->valid_until) {
                            // Handle exceeding max_credit_annual
                            return response()->json(['message' => 'Employee ' . $employeeId . ' has exceeded the annual overtime credit.'], Response::HTTP_BAD_REQUEST);
                        }
                    }
                }
            }

            $status = 'for recommending approval';
            $overtime_application = OvertimeApplication::create([
                'employee_profile_id' => $user->id,
                'status' => $status,
                'purpose' => $request->purpose,
                'overtime_letter_of_request' =>  $fileName,
                'overtime_letter_of_request_path' =>  $file_name_encrypted,
                'overtime_letter_of_request_size' =>  $size,
                'recommending_officer' => $recommending_and_approving['recommending_officer'],
                'approving_officer' => $recommending_and_approving['approving_officer'],
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
                'action_by' => $employee_profile->id,
                'action' => 'Applied'
            ]);
            return response()->json([
                'message' => 'Overtime Application has been sucessfully saved',
                'data' => OvertimeResource::collection($overtime_application),

            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function storePast(Request $request)
    {
        try {

            $user = $request->user;
            $employee_profile = $request->user;
            $employeeId = $employee_profile->id;
            $validatedData = $request->validate([
                'dates.*' => 'required|date_format:Y-m-d',
                'time_from.*' => 'required|date_format:H:i',
                'time_to.*' => [
                    'required',
                    'date_format:H:i',
                    function ($attribute, $value, $fail) use ($request) {
                        $index = explode('.', $attribute)[1];
                        $timeFrom = $request->input('time_from.' . $index);
                        if ($value < $timeFrom) {
                            $fail("The time to must be greater than time from.");
                        }
                    },
                ],
                'remarks.*' => 'required|string|max:512',
                'employees' => 'required|array',
                'employees.*' => 'required|integer|exists:employee_profiles,id',
            ]);
            $recommending_and_approving = Helpers::getRecommendingAndApprovingOfficer($employee_profile->assignedArea->findDetails(), $employee_profile->id);

            if ($recommending_and_approving === null || $recommending_and_approving['recommending_officer'] === null || $recommending_and_approving['approving_officer'] === null) {
                return response()->json(['message' => 'No recommending officer and/or supervising officer assigned.'], Response::HTTP_FORBIDDEN);
            }
            $status = 'for recommending approval';
            $overtime_application = OvertimeApplication::create([
                'employee_profile_id' => $user->id,
                'status' => $status,
                'purpose' => $request->purpose,
                'recommending_officer' => $recommending_and_approving['recommending_officer'],
                'approving_officer' => $recommending_and_approving['approving_officer'],
            ]);
            $ovt_id = $overtime_application->id;

            foreach ($validatedData['dates'] as $index => $date) {
                $date_application = OvtApplicationDatetime::create([
                    'overtime_application_id' => $ovt_id,
                    'time_from' =>  $validatedData['time_from'][$index],
                    'time_to' =>  $validatedData['time_to'][$index],
                    'date' =>  $date,
                ]);
            }
            $date_id = $date_application->id;
            foreach ($validatedData['employees'] as $index => $employees) {
                OvtApplicationEmployee::create([
                    'ovt_application_datetime_id' => $date_id,
                    'employee_profile_id' =>  $validatedData['employees'][$index],
                    'remarks' =>  $validatedData['remarks'][$index],
                ]);
            }
            OvtApplicationLog::create([
                'overtime_application_id' => $ovt_id,
                'action_by' => $employee_profile->id,
                'action' => 'Applied'
            ]);

            return response()->json([
                'message' => 'Overtime Application has been sucessfully saved',
                'data' => OvertimeResource::collection($overtime_application),

            ], Response::HTTP_OK);
        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME, 'storePast', $th->getMessage());
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

            $cleanData['pin'] = strip_tags($request->password);


            if ($employee_profile['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            if ($request->status === 'approved') {
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
            } else if ($request->status === 'declined') {

                $overtime_application_recommending = $data->recommending_officer;
                $overtime_application_approving = $data->approving_officer;


                if ($employee_profile->id === $overtime_application_recommending) {
                    $status = 'declined by recommending officer';
                } else if ($employee_profile->id === $overtime_application_approving) {
                    $status = 'declined by approving officer';
                }
                $log_action = 'Request Declined';
            }

            $data->update(['status' => $status]);
            OvtApplicationLog::create([
                'overtime_application_id' => $data->id,
                'action_by' => $employee_profile->id,
                'action' => 'Applied'
            ]);

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

    /**
     * Display the specified resource.
     */
    public function show($id, Request $request)
    {
        try {
            $overtime_application = Overtime::find($id);

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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Overtime $overtime)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Overtime $overtime)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Overtime $overtime)
    {
        //
    }
}
