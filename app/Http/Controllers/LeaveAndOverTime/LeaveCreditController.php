<?php

namespace App\Http\Controllers\LeaveAndOverTime;

use App\Models\LeaveCredit;
use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeLeaveCredit;
use App\Http\Resources\EmployeeProfile;
use App\Http\Resources\LeaveApplication;
use App\Http\Resources\LeaveCredit as ResourcesLeaveCredit;
use App\Models\DailyTimeRecord;
use App\Models\EmployeeLeaveCredit as ModelsEmployeeLeaveCredit;
use App\Models\EmployeeProfile as ModelsEmployeeProfile;
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
    $employees = ModelsEmployeeProfile::with('biometric.dtr')
    ->get();

     if($employees)
     {
        foreach ($employees as $employee) {

            $month = $currentMonth; 
            $biometric_id = $employee->biometric_id; 
            $request->merge(['monthof' => $month]);
            $request->merge(['biometric_id' => $biometric_id]);
            // $firstController = new FirstController();
            // $employee_records = $firstController->DTR_UTOT_Report($request);;
            $employee_records = $this->DTR_UTOT_Report($request);
            // $dates = $data['dates'];
            // $absences = $data['absences']
            ;
                 $total_absences="1";
                 $total_undertime="5";
                 $leaveTypes=[];
                 $vl_leave=[];
                 $leaveTypes = LeaveType::where('is_special', '=', '1')->get();
                 $vl_leave = LeaveType::where('name', '=', 'Sick Leave')->first();
                    $employee_leave_credits= ModelsEmployeeLeaveCredit::where('employee_profile_id', '=','1')
                    ->where('leave_type_id', '=','1')->get();
              
                    $totalLeaveCredits = $employee_leave_credits->mapToGroups(function ($credit) {
                        return [$credit->operation => $credit->credit_value];
                    })->map(function ($operationCredits, $operation) {
                        return $operation === 'add' ? $operationCredits->sum() : -$operationCredits->sum();
                    })->sum();
    
                    if($vl_leave)
                    {
                        $absent_credit_value = $vl_leave->leave_credit_year / 360 * 24 * $total_absences;
                        $undertime_credit_value = $total_undertime / 480;
                    }
                   
                    if($totalLeaveCredits != 0 )
                    {
                        $datesToCompare = ['2023-11-09', '2023-11-10', '2023-11-15'];
                        $approvedLeaveApplications=[];
                        $approvedLeaveApplications = ModelsLeaveApplication::with('dates')->where('employee_profile_id', $employee->id)->where('status', 'approved')->get();
                        if ($approvedLeaveApplications->count() > 0) 
                        {
                            foreach ($approvedLeaveApplications as $leave) {
                                foreach ($leave->dates as $leaveDate) {
                                    $startDate = $leaveDate->date_from;
                                    $endDate = $leaveDate->date_to;
                            
                                    // Check if any date within the range falls within the array of dates
                                    $dateRange = collect(\Carbon\CarbonPeriod::create($startDate, $endDate));
                                    $matchingDates = $dateRange->filter(function ($date) use ($datesToCompare) {
                                        return in_array($date->format('Y-m-d'), $datesToCompare);
                                    });
                            
                                    if ($matchingDates->count() > 0) {
                                        
                                        foreach ($matchingDates as $matchingDate) {
                                            echo "Matching date: " . $matchingDate->format('Y-m-d') . "\n";
                                        }
                                    }
                                    else
                                    {
                                        if($absent_credit_value !=0)
                                        {
                                            $employeeCredit = new ModelsEmployeeLeaveCredit();
                                            $employeeCredit->leave_type_id = $vl_leave->id;
                                            $employeeCredit->employee_profile_id = $employee->id;
                                            $employeeCredit->operation = "deduct";
                                            $employeeCredit->reason = "Absent";
                                            $employeeCredit->absent_total =$total_absences;
                                            $employeeCredit->credit_value = $absent_credit_value;
                                            $employeeCredit->date = date('Y-m-d');
                                            $employeeCredit->save();

                                        }
                                      
    
                                    }
                                }
                            }
                        }
        
                            if($undertime_credit_value !=0)
                                            {
                            $employeeCredit = new ModelsEmployeeLeaveCredit();
                            $employeeCredit->leave_type_id = $vl_leave->id;
                            $employeeCredit->employee_profile_id = $employee->id;
                            $employeeCredit->operation = "deduct";
                            $employeeCredit->reason = "Undertime";
                            $employeeCredit->undertime_total = $total_undertime;
                            $employeeCredit->credit_value = $undertime_credit_value;
                            $employeeCredit->date = date('Y-m-d');
                            $employeeCredit->save();
                        }
        
                    }
                    
    
                     foreach ($leaveTypes as $leaveType) {
                       
                         $month_credit_value = $leaveType->leave_credit_year/12;
                        
    
                         $employeeCredit = new ModelsEmployeeLeaveCredit();
                         $employeeCredit->leave_type_id = $leaveType->id;
                         $employeeCredit->employee_profile_id = $employee->id;
                         $employeeCredit->operation = "add";
                         $employeeCredit->reason = "Monthly Leave Credits";
                         $employeeCredit->credit_value = $month_credit_value;
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
    
    public function create()
    {
       
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

    /**
     * Display the specified resource.
     */
    public function show(LeaveCredit $leaveCredit)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaveCredit $leaveCredit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeaveCredit $leaveCredit)
    {
        //
    }

   
}

