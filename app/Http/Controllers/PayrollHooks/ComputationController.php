<?php

namespace App\Http\Controllers\PayrollHooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalaryGrade;
use App\Methods\Helpers;
use App\Models\EmployeeProfile;
use App\Models\LeaveType;
use App\Http\Controllers\PayrollHooks\GenerateReportController;
class ComputationController extends Controller
{
    protected $helper;
    protected $Working_Days;
    protected $Working_Hours;


     public function __construct() {
        $this->Working_Days = 22;
        $this->Working_Hours = 8;
        $this->helper = new Helpers();

    }

    public function BasicSalary($sg, $step,$schedcount)
    {
        $SG = SalaryGrade::where('salary_grade_number', $sg)->where('is_active', 1)->whereDate('effective_at', "<=", date('Y-m-d'))->first();
        $salaryGrade = 0;

        switch ($step) {
            case 1:
                $salaryGrade = $SG->one;
                break;

            case 2:
                $salaryGrade = $SG->two;
                break;
            case 3:
                $salaryGrade = $SG->three;
                break;
            case 4:

                $salaryGrade = $SG->four;
                break;
            case 5:

                $salaryGrade = $SG->five;
                break;
            case 6:
                $salaryGrade = $SG->six;
                break;

            case 7:
                $salaryGrade = $SG->seven;
                break;

            case 8:
                $salaryGrade = $SG->eight;
                break;
        }
      //  $salaryGrade = 35097;
      if(!$schedcount){
        $schedcount = 1;
      }
    return [
        'Total'=> floor(( $this->Working_Days * $salaryGrade / $this->Working_Days) * 100) / 100,
        'GrandTotal'=> $salaryGrade,
    ];
    }

    public function GrossSalary($present_Days,$salary,$NumbersOfAbsences){

        if($NumbersOfAbsences  == 0 ){
            return round($salary,2);
        }
         return round(($present_Days * $salary) / $this->Working_Days,2); // Contstant value. Required number of days
    }

    public function Rates($basic_Salary,$schedCount){
        if(!$schedCount){
            return [
                'Weekly' => 0,
                'Daily' => 0,
                'Hourly' =>0,
                'Minutes' => 0,
            ];
        }

        $per_day = $basic_Salary / $this->Working_Days;

        // Calculate the per-hour rate
        $per_hour = $per_day / $this->Working_Hours;

        // Calculate the per-minute rate
        $per_minute = $per_hour / 60;

        // Calculate the per-week rate (assuming 5 workdays in a week)
        $per_week = $per_day * 5;

        // Return rates, rounded to 3 decimal places
        return [
            'Weekly'  => round($per_week, 2),
            'Daily'   => round($per_day, 2),
            'Hourly'  => round($per_hour, precision: 2),
            'Minutes' => round($per_minute, 2),
        ];

    }

    public function UndertimeRates($total_Month_Undertime,$Rates){
            return $total_Month_Undertime * $Rates['Minutes'];
    }

    public function AbsentRates($Number_Absences,$Rates){
        return round($Rates['Daily'] * $Number_Absences,2) ;
    }

    public function NetSalaryFromTimeDeduction($GrossSalary,$undertimeRate,$absentRate,$emptype){

        $deduction =  $absentRate;
        if ($emptype == "Job Order"){
            $deduction += $undertimeRate;
        }
        $net =  floor(round( $GrossSalary,2) * 100) /100;
        return $net - $deduction ;

    }

    public function OutofPayroll($overallnetSalary,$employmentType){
        $limit = 5000;
        if($employmentType == "Job Order"){
            $limit = 2500;
        }



        if ($overallnetSalary < $limit){
            return true;
        }

        return false;

    }




}
