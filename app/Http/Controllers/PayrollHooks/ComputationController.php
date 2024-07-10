<?php

namespace App\Http\Controllers\PayrollHooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalaryGrade;
class ComputationController extends Controller
{

    protected $Working_Days;
    protected $Working_Hours;
     public function __construct() {
        $this->Working_Days = 22;
        $this->Working_Hours = 8;
    }

    public function BasicSalary($sg, $step,$schedcount)
    {
        $SG = SalaryGrade::where('salary_grade_number', $sg)->where('is_active', 1)->whereDate('effective_at', "<=", date('Y-m-d'))->first();
        $salaryGrade = 0;

        switch ($step) {
            case 1:
                $salaryGrade = $SG->one;

            case 2:
                $salaryGrade = $SG->two;

            case 3:
                $salaryGrade = $SG->three;

            case 4:

                $salaryGrade = $SG->four;
            case 5:

                $salaryGrade = $SG->five;
            case 6:
                $salaryGrade = $SG->six;

            case 7:
                $salaryGrade = $SG->seven;

            case 8:
                $salaryGrade = $SG->eight;
        }
        $salaryGrade = 35097;
    return [
        'Total'=> floor(( $schedcount * $salaryGrade / $this->Working_Days) * 100) / 100,
        'GrandTotal'=> $salaryGrade,
    ];
    }

    public function GrossSalary($present_Days,$salary){

        return round($present_Days * $salary / 22,2); // Contstant value. Required number of days

    }

    public function Rates($basic_Salary){

            $per_day = $basic_Salary / $this->Working_Days;
            $per_hour = $per_day / $this->Working_Hours ;
            $per_minutes = $per_hour / 60;
            $per_week = $per_day * 5;

            return [
                'Weekly' => floor($per_week * 100) / 100,
                'Daily' => floor($per_day * 100) / 100,
                'Hourly' => floor($per_hour * 100) / 100,
                'Minutes' => floor($per_minutes * 100) / 100,
            ];


    }

    public function UndertimeRates($total_Month_Undertime,$Rates){
            return $total_Month_Undertime * $Rates['Minutes'];
    }

    public function AbsentRates($Number_Absences,$Rates){
        return round($Rates['Daily'] * $Number_Absences,2) ;
    }

    public function NetSalaryFromTimeDeduction($Rates,$presentCount,$undertimeRate,$absentRate,$grosssalary){
        $deduction = $undertimeRate ;
        $grossSal = $Rates['Daily'] * $presentCount ;
        $net =  floor(round( $grossSal - $deduction,2) * 100) /100;

      

        return $net ;

    }

    public function OutofPayroll($netsalary,$init,$days_In_Month){
        $limit = 5000;
        $halfLimit = $limit / 2;
        
        if (($init >= 1 && $days_In_Month <= 15) || ($init >= 16 && $days_In_Month >=31)) {
            if ($netsalary < $halfLimit) {
                // OUT OF PAYROLL
                return true;
            }
        } elseif ($init >= 1 && $init <= 31) {
            if ($netsalary < $limit) {
                return true;
            }
        }
        
        return false;
        
    }


}
