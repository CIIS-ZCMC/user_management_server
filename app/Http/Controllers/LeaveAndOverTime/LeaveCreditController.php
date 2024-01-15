<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Models\LeaveCredit;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DTR\DTRcontroller;
use App\Http\Resources\EmployeeLeaveCredit;
use App\Http\Resources\EmployeeProfile;
use App\Http\Resources\LeaveApplication;
use App\Http\Resources\LeaveCredit as ResourcesLeaveCredit;
use App\Models\DailyTimeRecord;
use App\Models\EmployeeLeaveCredit as ModelsEmployeeLeaveCredit;
use App\Models\EmployeeProfile as ModelsEmployeeProfile;
use App\Models\EmploymentType;
use App\Models\LeaveApplication as ModelsLeaveApplication;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;
class LeaveCreditController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            $leave_credits=[];

           $leave_credits =LeaveCredit::all();
           $leave_credit_resource=ResourcesLeaveCredit::collection($leave_credits);

             return response()->json(['data' => $leave_credit_resource], Response::HTTP_OK);
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function addMonthlyLeaveCredit(Request $request)
    {
        $employees=[];
        $employees = ModelsEmployeeProfile::whereHas('employmentType', function ($query) {
            $query->where('name', 'Regular Full-Time')
                  ->orWhere('name', 'Regular Part-Time');
        })->get();
        if($employees)
        {
            foreach ($employees as $employee) {
                    $leaveTypes = LeaveType::where('is_special', '=', '0')->get();
                        foreach ($leaveTypes as $leaveType) {
                            if($leaveType->is_special == 0)
                            {
                                 $month_credit_value = $leaveType->leave_credit_year/12;
                                 $employeeCredit = new ModelsEmployeeLeaveCredit();
                                 $employeeCredit->leave_type_id = $leaveType->id;
                                 $employeeCredit->employee_profile_id = $employee->id;
                                 $employeeCredit->operation = "add";
                                 $employeeCredit->reason = "Monthly Leave Credits";
                                //  $employeeCredit->working_hours_total = $total_working_hours;
                                 $employeeCredit->credit_value = $month_credit_value;
                                 $employeeCredit->date = date('Y-m-d');
                                 $employeeCredit->save();

                            }

                        }
            }
        }
        return response()->json(['data' => $employeeCredit], Response::HTTP_OK);

    }
    public function deductUndertimefirsthalf(Request $request)
    {
        $currentMonth = date('m');
        $currentYear = date('Y');
        $currentDate = date('Y-m-d');
        $pastMonth = date('m', strtotime('-1 month'));
        $lastMonthDate = date('Y-m-d', strtotime('-1 month', strtotime($currentDate)));
        $employees=[];

        $employees = ModelsEmployeeProfile::whereHas('employmentType', function ($query) {
            $query->where('name', 'Regular Full-Time')
                  ->orWhere('name', 'Regular Part-Time');
        })->get();
        if($employees)
        {
            foreach ($employees as $employee) {
                    $total_undertime="0";
                    $vl_leave=[];
                    $vl_leave = LeaveType::where('id', '=', '1')->first();
                    $employee_leave_credits= ModelsEmployeeLeaveCredit::where('employee_profile_id',$employee->id)->get();
                    $biometric_id=$employee->biometric_id;
                    $undertimeController = new DTRcontroller();
                    $undertime_request = new Request(['biometric_id' => $biometric_id, 'monthof' => $currentMonth, 'yearof' => $currentYear, 'is15thdays' => '1','firsthalf' => '1', 'secondhalf' => '0']);
                    $undertime_total = $undertimeController->dtrUTOTReport($undertime_request);
                    $employee_undertime=$undertime_total[''];

                        $totalLeaveCredits = $employee_leave_credits->mapToGroups(function ($credit) {
                            return [$credit->operation => $credit->credit_value];
                        })->map(function ($operationCredits, $operation) {
                            return $operation === 'add' ? $operationCredits->sum() : -$operationCredits->sum();
                        })->sum();

                        if($vl_leave)
                        {
                            $undertime_credit_value = $total_undertime / 480;
                        }

                        if($totalLeaveCredits != 0 )
                        {
                            if($undertime_credit_value !=0 && $undertime_credit_value < $totalLeaveCredits)
                            {
                                $employeeCredit = new ModelsEmployeeLeaveCredit();
                                $employeeCredit->leave_type_id = $vl_leave->id;
                                $employeeCredit->employee_profile_id = $employee->id;
                                $employeeCredit->operation = "deduct";
                                $employeeCredit->reason = "Undertime";
                                $employeeCredit->undertime_total = $total_undertime;
                                $employeeCredit->credit_value = $undertime_credit_value;
                                $employeeCredit->date = date('Y-m-d');
                                $employeeCredit->time =  date('H:i:s');
                                $employeeCredit->save();
                            }
                            else if($undertime_credit_value > $totalLeaveCredits)
                            {
                                $employeeCredit = new ModelsEmployeeLeaveCredit();
                                $employeeCredit->leave_type_id = $vl_leave->id;
                                $employeeCredit->employee_profile_id = $employee->id;
                                $employeeCredit->operation = "deduct";
                                $employeeCredit->reason = "Undertime";
                                $employeeCredit->undertime_total = $total_undertime;
                                $employeeCredit->credit_value = $totalLeaveCredits;
                                $employeeCredit->true_credit_value = $undertime_credit_value;
                                $employeeCredit->date = date('Y-m-d');
                                $employeeCredit->save();
                            }

                        }

            }
        }
        return response()->json(['data' => $employee_leave_credits], Response::HTTP_OK);

    }
    public function deductUndertimesecondhalf(Request $request)
    {
        $currentMonth = date('m');
        $currentDate = date('Y-m-d');
        $pastMonth = date('m', strtotime('-1 month'));
       // Subtract one month to get the last month
        $lastMonthDate = date('Y-m-d', strtotime('-1 month', strtotime($currentDate)));
        // Get the first day of the last month
        $firstDayOfLastMonth = date('Y-m-01', strtotime($lastMonthDate));
        // Get the last day of the last month
        $lastDayOfLastMonth = date('Y-m-t', strtotime($lastMonthDate));
        $currentDate = date('Y-m-d');
        // Subtract one month to get the last month
        $lastMonthDate = date('Y-m-d', strtotime('-1 month', strtotime($currentDate)));
         // Get the first day of the last month
        $firstDayOfLastMonth = date('Y-m-01', strtotime($lastMonthDate));
         // Get the last day of the last month
        $lastDayOfLastMonth = date('Y-m-t', strtotime($lastMonthDate));
        $employees=[];
        $employees = ModelsEmployeeProfile::whereHas('employmentType', function ($query) {
            $query->where('name', 'Regular Full-Time')
                  ->orWhere('name', 'Regular Part-Time');
        })->get();
        if($employees)
        {
            foreach ($employees as $employee) {
                $month = $currentMonth;
                    $total_absences="0";
                    $total_undertime="0";
                    $total_working_hours="150";
                    $leaveTypes=[];
                    $vl_leave=[];
                    $leaveTypes = LeaveType::where('is_special', '=', '0')->get();
                    $vl_leave = LeaveType::where('id', '=', '1')->first();
                    $employee_leave_credits= ModelsEmployeeLeaveCredit::where('employee_profile_id',$employee->id)->get();

                        $totalLeaveCredits = $employee_leave_credits->mapToGroups(function ($credit) {
                            return [$credit->operation => $credit->credit_value];
                        })->map(function ($operationCredits, $operation) {
                            return $operation === 'add' ? $operationCredits->sum() : -$operationCredits->sum();
                        })->sum();

                        if($vl_leave)
                        {
                            $undertime_credit_value = $total_undertime / 480;
                        }

                        if($totalLeaveCredits != 0 )
                        {
                            if($undertime_credit_value !=0 && $undertime_credit_value < $totalLeaveCredits)
                            {
                                $employeeCredit = new ModelsEmployeeLeaveCredit();
                                $employeeCredit->leave_type_id = $vl_leave->id;
                                $employeeCredit->employee_profile_id = $employee->id;
                                $employeeCredit->operation = "deduct";
                                $employeeCredit->reason = "Undertime";
                                $employeeCredit->undertime_total = $total_undertime;
                                $employeeCredit->credit_value = $undertime_credit_value;
                                $employeeCredit->date = date('Y-m-d');
                                $employeeCredit->time =  date('H:i:s');
                                $employeeCredit->save();
                            }
                            else if($undertime_credit_value > $totalLeaveCredits)
                            {
                                $employeeCredit = new ModelsEmployeeLeaveCredit();
                                $employeeCredit->leave_type_id = $vl_leave->id;
                                $employeeCredit->employee_profile_id = $employee->id;
                                $employeeCredit->operation = "deduct";
                                $employeeCredit->reason = "Undertime";
                                $employeeCredit->undertime_total = $total_undertime;
                                $employeeCredit->credit_value = $totalLeaveCredits;
                                $employeeCredit->true_credit_value = $undertime_credit_value;
                                $employeeCredit->date = date('Y-m-d');
                                $employeeCredit->save();
                            }

                        }

            }
        }
        return response()->json(['data' => $employee_leave_credits], Response::HTTP_OK);

    }

    Public function addYearlyLeaveCredit(Request $request)
    {

        $employees = ModelsEmployeeProfile::whereHas('employmentType', function ($query) {
            $query->where('name', 'Regular Full-Time')
                  ->orWhere('name', 'Regular Part-Time');
        })->get();
        if($employees)
        {
            foreach ($employees as $employee) {
                $vl_leave = LeaveType::where('name', '=', 'Vacation Leave')->orwhere('code', '=', 'vl')->first();
                $employee_leave_credits= ModelsEmployeeLeaveCredit::where('employee_profile_id', '=','1')
                ->where('leave_type_id', '=',$vl_leave->id)->get();

                $totalLeaveCredits = $employee_leave_credits->mapToGroups(function ($credit) {
                    return [$credit->operation => $credit->credit_value];
                })->map(function ($operationCredits, $operation) {
                    return $operation === 'add' ? $operationCredits->sum() : -$operationCredits->sum();
                })->sum();

                if($totalLeaveCredits >= 10)
                {
                    $fl_leave = LeaveType::where('name', '=', 'Forced Leave')->orwhere('code', '=', 'FL')->first();
                    $employeeCredit = new ModelsEmployeeLeaveCredit();
                    $employeeCredit->leave_type_id = $fl_leave->id;
                    $employeeCredit->employee_profile_id = $employee->id;
                    $employeeCredit->operation = "add";
                    $employeeCredit->reason = "Yearly FL Credits";
                    $employeeCredit->credit_value = '5';
                    $employeeCredit->date = date('Y-m-d');
                    $employeeCredit->save();

                }
            }
        }

        return response()->json(['data' => $employee_leave_credits], Response::HTTP_OK);

    }

    Public function addSpLeaveCredit(Request $request)
    {
        $employees = ModelsEmployeeProfile::with('biometric.dtr')
        ->get();

        if($employees)
        {
            foreach ($employees as $employee) {
                    $spl_leave = LeaveType::where('name', '=', 'Special Leave Privilege')->orwhere('code', '=', 'SPL')->first();
                    $employeeCredit = new ModelsEmployeeLeaveCredit();
                    $employeeCredit->leave_type_id = $spl_leave->id;
                    $employeeCredit->employee_profile_id = $employee->id;
                    $employeeCredit->operation = "add";
                    $employeeCredit->reason = "Biannual SPL Credits";
                    $employeeCredit->credit_value = '3';
                    $employeeCredit->date = date('Y-m-d');
                    $employeeCredit->save();
                }
            return response()->json(['data' => $employeeCredit], Response::HTTP_OK);
        }

    }

    Public function resetYearlyLeaveCredit(Request $request)
    {
        $employees = ModelsEmployeeProfile::get();
        if($employees)
        {
            foreach ($employees as $employee) {
                $spl_leave = LeaveType::where('name', '=', 'Special Leave Privilege')->orwhere('code', '=', 'SPL')->first();
                $fl_leave = LeaveType::where('name', '=', 'Forced Leave')->orwhere('code', '=', 'FL')->first();
                if($spl_leave)
                {
                    $employee_leave_credits= ModelsEmployeeLeaveCredit::where('employee_profile_id', '=',$employee->id)
                    ->where('leave_type_id', '=',$spl_leave->id)->get();

                    $totalLeaveCredits = $employee_leave_credits->mapToGroups(function ($credit) {
                        return [$credit->operation => $credit->credit_value];
                    })->map(function ($operationCredits, $operation) {
                        return $operation === 'add' ? $operationCredits->sum() : -$operationCredits->sum();
                    })->sum();

                    $employeeCredit = new ModelsEmployeeLeaveCredit();
                    $employeeCredit->leave_type_id = $spl_leave->id;
                    $employeeCredit->employee_profile_id = $employee->id;
                    $employeeCredit->operation = "deduct";
                    $employeeCredit->reason = "Reset SPL Credits";
                    $employeeCredit->credit_value = $totalLeaveCredits;
                    $employeeCredit->date = date('Y-m-d');
                    $employeeCredit->save();

                }


                if($fl_leave)
                {
                    $employee_leave_credits= ModelsEmployeeLeaveCredit::where('employee_profile_id', '=',$employee->id)
                    ->where('leave_type_id', '=',$fl_leave->id)->get();

                    $totalLeaveCredits = $employee_leave_credits->mapToGroups(function ($credit) {
                        return [$credit->operation => $credit->credit_value];
                    })->map(function ($operationCredits, $operation) {
                        return $operation === 'add' ? $operationCredits->sum() : -$operationCredits->sum();
                    })->sum();

                    $employeeCredit = new ModelsEmployeeLeaveCredit();
                    $employeeCredit->leave_type_id = $fl_leave->id;
                    $employeeCredit->employee_profile_id = $employee->id;
                    $employeeCredit->operation = "deduct";
                    $employeeCredit->reason ='Reset FL Credit';
                    $employeeCredit->credit_value = $totalLeaveCredits;
                    $employeeCredit->date = date('Y-m-d');
                    $employeeCredit->save();
                }

            }
        }
        return response()->json(['data' => $employee_leave_credits], Response::HTTP_OK);

    }

    public function DTR_UTOT_Report(Request $request)
    {

        $date=['2023-10-11','2023-10-12'];
        $absent=[1];
        return
        [
            'dates' => $date,
            'absent' => $absent,
        ];
    }

    public function store(Request $request)
    {
        try{
            $leave_credit = new LeaveCredit();
            $leave_credit->day_value = $request->day_value;
            $leave_credit->month_value = $request->month_value;
            $leave_credit->save();

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){

            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function update($id,Request $request)
    {
        try{
            $leave_credit = LeaveCredit::findOrFail($id);
            $leave_credit->day_value = $request->day_value;
            $leave_credit->month_value = $request->month_value;
            $leave_credit->update();


            return response() -> json(['data' => "Success"], 200);
        }catch(\Throwable $th){

            return response() -> json(['message' => $th -> getMessage()], 500);
        }
    }




}

