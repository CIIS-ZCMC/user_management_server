<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\Http\Requests\PasswordApprovalRequest;

use App\Models\MonitizationPosting;
use App\Models\EmployeeLeaveCredit;
use App\Models\LeaveType;


class MonitizationPostingController extends Controller
{
    public function index(Request $request)
    {
        try{
            $latest_posting=[];

            $latest_posting = MonitizationPosting::latest('created_at')->first();
            if ($latest_posting) {
                $end_posting_date = strtotime($latest_posting->end_filing_date);
                $start_posting_date = strtotime($latest_posting->effective_filing_date);
                $current_date = strtotime(date('Y-m-d'));

                $start = ($current_date >= $start_posting_date && $current_date <= $end_posting_date);
                $end = $current_date > $end_posting_date;

                // Populate the latest posting array with necessary data
                $latest_posting = [
                    'id' => $latest_posting->id,
                    'created_at' => $latest_posting->created_at,
                    'end_filing_date' => $latest_posting->end_filing_date,
                    'effective_filing_date' => $latest_posting->effective_filing_date,
                    'start' => $start,
                    'end' => $end
                ];

                return response()->json([
                    'data' => $latest_posting,
                    'message' => 'Retrieve posting records.'
                ], Response::HTTP_OK);
            } else {
                $latest_posting = [
                    'id' => null,
                    'created_at' => null,
                    'end_filing_date' => null,
                    'effective_filing_date' => null,
                    'start' => null,
                    'end' => null
                ];
                return response()->json([
                    'data' => $latest_posting,
                    'message' => 'No posting records found.'
                ],  Response::HTTP_OK);
            }
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function checkForSLMonitization($id, Request $request)
    {
        try{
            $employee_profile = $request->user;

            $employee_leave_credits = EmployeeLeaveCredit::where('leave_type_id', LeaveType::where('code', 'SL')->first()->id)
                    ->where('employee_profile_id', $employee_profile->id)->first();

            return response()->json([
                'data' => [
                    'employee_leave_credits' => $employee_leave_credits,
                    'is_allowed_for_sick_leave_monitization' => $employee_leave_credits->total_leave_credits >= 15
                ],
                'message' => 'Retrieve check for sl monitization.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function candidates(Request $request)
    {
        try{
            $employee_leave_credits = EmployeeLeaveCredit::select('employee_leave_credits.*')
<<<<<<< HEAD
            ->join('employee_profiles as ep', 'ep.id', 'employee_leave_credits.employee_profile_id')
=======
            ->join('employee_profiles as ep', 'ep.id', '=', 'employee_leave_credits.employee_profile_id')
>>>>>>> 28b483e436bb28aa50a5620f1f4f9709bb86e42c
            ->join('assigned_areas', 'assigned_areas.employee_profile_id', '=', 'ep.id')
            ->join('designations', 'designations.id', '=', 'assigned_areas.designation_id')
            ->join('salary_grades as sg', 'sg.id', '=', 'designations.salary_grade_id')
            ->where('sg.salary_grade_number', '<=', 19)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('employee_leave_credits.leave_type_id', LeaveType::where('code', 'VL')->first()->id)
                        ->where('employee_leave_credits.total_leave_credits', '>=', 15);
                })
                ->orWhere(function ($query) {
                    $query->where('employee_leave_credits.leave_type_id', LeaveType::where('code', 'SL')->first()->id)
                        ->where('employee_leave_credits.total_leave_credits', '>=', 15);
                });
            })
            ->get();

        $candidates = [];

        foreach ($employee_leave_credits as $employee_leave_credit) {
            $employeeProfile = $employee_leave_credit->employeeProfile;
            $personalInformation = $employeeProfile->personalInformation;
            $area = $employeeProfile->assignedArea->findDetails();

            $employee_id = $employeeProfile->id;

            // Get additional required information
            $employee_name = $personalInformation->first_name . ' ' . $personalInformation->last_name;
            $employeeNo = $employeeProfile->employee_id;
            $designation = $employeeProfile->assignedArea->designation->name;
            $area = $area['details']->name;
            $vacation_leave_balance = 0;
            $sick_leave_balance = 0;

            // Check if VL balance should be added
            if ($employee_leave_credit->leave_type_id === LeaveType::where('code', 'VL')->first()->id) {
                $vacation_leave_balance = $employee_leave_credit->total_leave_credits;
            }

            // Check if SL balance should be added
            if ($employee_leave_credit->leave_type_id === LeaveType::where('code', 'SL')->first()->id) {
                $sick_leave_balance = $employee_leave_credit->total_leave_credits;
            }

            // Add data to candidates array
            if (!isset($candidates[$employee_id])) {
                $candidates[$employee_id] = [
                    'id' => $employee_id,
                    'employee_name' => $employee_name,
                    'employee_no' => $employeeNo,
                    'profile_url' =>  env('SERVER_DOMAIN') . "/photo/profiles/" . $employeeProfile->profile_url,
                    'designation' => $designation,
                    'area' => $area,
                    'vacation_leave_balance' => $vacation_leave_balance,
                    'sick_leave_balance' => $sick_leave_balance
                ];
            } else {
                // If employee already exists in array, update the leave balances
                $candidates[$employee_id]['vacation_leave_balance'] += $vacation_leave_balance;
                $candidates[$employee_id]['sick_leave_balance'] += $sick_leave_balance;
            }
        }

        // Convert associative array to simple array
        $candidates = array_values($candidates);


            return response()->json([
                'data' => $candidates,
                'message' => "Employees for monetization."
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try{
            $cleanData = [];
            $employee_profile = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($employee_profile['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            foreach($request->all() as $key => $value)
            {
                if($key === 'user') continue;
                if($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }
            $cleanData['created_by'] = $employee_profile->id;
            $monitization =  MonitizationPosting::create($cleanData);

            return response()->json([
                'data' => $monitization->toArray(),
                'message' => "Monitization Post has been created."
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id, Request $request)
    {
        try{
            $cleanData = [];
            $monitization = MonitizationPosting::find($id);

            if($monitization){
                return response()->json(['message' => 'No monitization found.'], Response::HTTP_NOT_FOUND);
            }

            foreach($request->all() as $key => $value)
            {
                if($key === 'user') continue;
                if($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $monitization->update($cleanData);

            return response()->json([
                'data' => $monitization,
                'message' => "Monitization Post has been updated."
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id, PasswordApprovalRequest $request)
    {
        try{
            $employee_profile = $request->user;

            $pin = strip_tags($request->password);

            if($employee_profile->authorization_pin === $pin)
            {
                return response()->json(['message' => "Invalid pin."], Response::HTTP_FORBIDDEN);
            }

            $monitization = MonitizationPosting::find($id);

            if($monitization){
                return response()->json(['message' => 'No monitization found.'], Response::HTTP_NOT_FOUND);
            }

            $monitization->delete();

            return response()->json([
                'message' => 'Monitization has successfully deleted.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

