<?php

namespace App\Http\Controllers\PayrollHooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmployeeProfile;
use App\Models\DailyTimeRecord;
use Illuminate\Support\Facades\DB;
use App\Methods\Helpers;
use App\Models\LeaveType;
class GenerateReportController extends Controller
{
    protected $helper;

    public function __construct()
    {
        $this->helper = new Helpers();

    }


    public function ToHours($minutes) {
        $hours = $minutes / 60;
        return $hours;
    }
    public function Attendance($year_of,$month_of,$i,$recordDTR){
        return [
            'dateRecord'=>date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
            'firstin'=>$recordDTR[0]->first_in,
            'firstout'=>$recordDTR[0]->first_out,
            'secondin'=>$recordDTR[0]->second_in,
            'secondout'=>$recordDTR[0]->second_out,
            'total_working_minutes'=>$recordDTR[0]->total_working_minutes,
            'overtime_minutes'=>$recordDTR[0]->overtime_minutes,
            'undertime_minutes'=>$recordDTR[0]->undertime_minutes,
            'overall_minutes_rendered'=>$recordDTR[0]->overall_minutes_rendered,
            'total_minutes_reg'=>$recordDTR[0]->total_minutes_reg
        ];

    }

    public function test(Request $request){
        $month_of = $request->month_of;
        $year_of = $request->year_of;
        $biometricIds = DB::table('daily_time_records')
            ->whereYear('dtr_date', $year_of)
            ->whereMonth('dtr_date', $month_of)
            ->pluck('biometric_id');
        $profiles = DB::table('employee_profiles')
           //->whereIn('biometric_id', $biometricIds)
           ->where('biometric_id', 476  )// 494
            ->get();


            $data = [];

            foreach ($profiles as $row) {
                $Employee = EmployeeProfile::find($row->id);
        $biometric_id = $row->biometric_id;

                $dtr = DB::table('daily_time_records')
                ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
                ->where(function ($query) use ($biometric_id, $month_of, $year_of) {
                    $query->where('biometric_id', $biometric_id)
                        ->whereMonth(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                        ->whereYear(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
                })
                ->orWhere(function ($query) use ($biometric_id, $month_of, $year_of) {
                    $query->where('biometric_id', $biometric_id)
                        ->whereMonth(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                        ->whereYear(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
                })
                ->get();
                $empschedule = [];

                foreach ($dtr as $val) {
                    $bioEntry = [
                        'first_entry' => $val->first_in ?? $val->second_in,
                        'date_time' => $val->first_in ?? $val->second_in
                    ];
                    $Schedule = $this->helper->CurrentSchedule($biometric_id, $bioEntry, false);
                    $DaySchedule = $Schedule['daySchedule'];
                    $empschedule[] = $DaySchedule;

                    if (count($DaySchedule) >= 1) {
                        $validate = [
                            (object)[
                                'id' => $val->id,
                                'first_in' => $val->first_in,
                                'first_out' => $val->first_out,
                                'second_in' => $val->second_in,
                                'second_out' => $val->second_out
                            ],
                        ];
                        $this->helper->saveTotalWorkingHours(
                            $validate,
                            $val,
                            $val,
                            $DaySchedule,
                            true
                        );
                    }
                }

                $presentDays = array_map(function($d) {
                    return $d->day;
                }, $dtr->toArray());



            $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();



            if($employee->leaveApplications){
                   //Leave Applications
            $leaveapp  = $employee->leaveApplications->filter(function ($row) {
                return $row['status'] == "received";
            });



            $leavedata = [];
            foreach ($leaveapp as $rows) {
                $leavedata[] = [
                    'country' => $rows['country'],
                    'city' => $rows['city'],
                    'from' => $rows['date_from'],
                    'to' => $rows['date_to'],
                    'leavetype' => LeaveType::find($rows['leave_type_id'])->name ?? "",
                    'without_pay' => $rows['without_pay'],
                    'dates_covered' => $this->helper->getDateIntervals($rows['date_from'], $rows['date_to'])
                ];
            }

            }



            //Official business
            if($employee->officialBusinessApplications){
                $officialBusiness = array_values($employee->officialBusinessApplications->filter(function ($row) {
                    return $row['status'] == "approved";
                })->toarray());
                $obData = [];
                foreach ($officialBusiness as $rows) {
                    $obData[] = [
                        'purpose' => $rows['purpose'],
                        'date_from' => $rows['date_from'],
                        'date_to' => $rows['date_to'],
                        'dates_covered' => $this->helper->getDateIntervals($rows['date_from'], $rows['date_to']),
                    ];
                }
            }

            if($employee->officialTimeApplications){
                  //Official Time
            $officialTime = $employee->officialTimeApplications->filter(function ($row) {
                return $row['status'] == "approved";
            });
            $otData = [];
            foreach ($officialTime as $rows) {
                $otData[] = [
                    'date_from' => $rows['date_from'],
                    'date_to' => $rows['date_to'],
                    'purpose' => $rows['purpose'],
                    'dates_covered' => $this->helper->getDateIntervals($rows['date_from'], $rows['date_to'])
                ];
            }
            }

            if( $employee->ctoApplications){
                 $CTO =  $employee->ctoApplications->filter(function ($row) {
                return $row['status'] == "approved";
            });
            $ctoData = [];
            foreach ($CTO as $rows) {
                $ctoData[] = [
                    'date' => date('Y-m-d', strtotime($rows['date'])),
                    'purpose' => $rows['purpose'],
                    'remarks' => $rows['remarks'],
                ];
            }
            }



                if(count($empschedule)>=1){
                    $empschedule = array_map(function ($sc){
                    return isset($sc['scheduleDate']) && (int)date('d', strtotime($sc['scheduleDate']));
                },$empschedule);
                }

                $days_In_Month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);
                // echo "Name :".$Employee?->personalInformation->name()."\n Biometric_id :".$Employee->biometric_id."\n"?? "\n"."\n";

                $attd = [];
                $lwop = [];
                $lwp = [];
                $obot= [];
                $absences = [];
                $dayoff = [];
                $total_Month_WorkingMinutes = 0;
                $total_Month_Overtime = 0;
                $total_Month_Undertime = 0;
                $invalidEntry = [];

                for ($i=1; $i <= $days_In_Month; $i++) {

                    $filteredleaveDates = [];
                    $leaveStatus =[];
                    foreach ($leavedata as $row) {
                        foreach ($row['dates_covered'] as $date) {
                            $filteredleaveDates[] = [
                                'dateReg'=>strtotime($date),
                                'status'=>$row['without_pay']
                            ];

                        }
                    }

                    $leaveApplication = array_filter($filteredleaveDates, function ($timestamp) use (
                        $year_of,
                        $month_of,
                        $i,
                    ) {
                        $dateToCompare = date('Y-m-d', $timestamp['dateReg']);
                        $dateToMatch = date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i));
                        return $dateToCompare === $dateToMatch ;
                    });


                    $leave_Count = count($leaveApplication);

                    //Check obD ates
                    $filteredOBDates = [];
                    foreach ($obData as $row) {
                        foreach ($row['dates_covered'] as $date) {
                            $filteredOBDates[] = strtotime($date);
                        }
                    }
                    $obApplication = array_filter($filteredOBDates, function ($timestamp) use ($year_of, $month_of, $i) {
                        $dateToCompare = date('Y-m-d', $timestamp);
                        $dateToMatch = date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i));
                        return $dateToCompare === $dateToMatch;
                    });
                    $ob_Count = count($obApplication);

                    //Check otDates
                    $filteredOTDates = [];
                    foreach ($otData as $row) {
                        foreach ($row['dates_covered'] as $date) {
                            $filteredOTDates[] = strtotime($date);
                        }
                    }
                    $otApplication = array_filter($filteredOTDates, function ($timestamp) use ($year_of, $month_of, $i) {
                        $dateToCompare = date('Y-m-d', $timestamp);
                        $dateToMatch = date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i));
                        return $dateToCompare === $dateToMatch;
                    });
                    $ot_Count = count($otApplication);

                    $ctoApplication = array_filter($ctoData, function ($row) use ($year_of, $month_of, $i) {
                        $dateToCompare = date('Y-m-d', strtotime($row['date']));
                        $dateToMatch = date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i));
                        return $dateToCompare === $dateToMatch;
                    });
                    $cto_Count = count($ctoApplication);


                    if($leave_Count ){

                        if(array_values($leaveApplication)[0]['status']){
                          //  echo $i."-LwoPay \n";
                            $lwop[] = [
                                'dateRecord'=>date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
                            ];
                            // deduct to salary
                        }else {
                          //  echo $i."-LwPay \n";
                            $lwp[] = [
                                'dateRecord'=>date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
                            ];
                            $total_Month_WorkingMinutes += 480;
                        }

                    }else if($ob_Count ||  $ot_Count ){
                       // echo $i."-ob or ot Paid \n";
                        $obot[] = [
                            'dateRecord'=>date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),

                        ];
                        $total_Month_WorkingMinutes += 480 ;
                    }else

                    if(in_array($i,$presentDays) && in_array($i,$empschedule)){

                        $recordDTR = array_values(array_filter($dtr->toArray(),function($d) use ($year_of, $month_of, $i){
                            return $d->dtr_date==date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i));
                        }));
                       // echo $i."-P \n";


                        if(
                            ($recordDTR[0]->first_in && $recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out) ||
                            (!$recordDTR[0]->first_in && !$recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out)  ||
                            ($recordDTR[0]->first_in && $recordDTR[0]->first_out && !$recordDTR[0]->second_in && !$recordDTR[0]->second_out) ||
                            ($recordDTR[0]->first_in && $recordDTR[0]->first_out && $recordDTR[0]->second_in && !$recordDTR[0]->second_out)
                          ){
                            $attd[] = $this->Attendance($year_of,$month_of,$i,$recordDTR);
                            $total_Month_WorkingMinutes += $recordDTR[0]->total_working_minutes;
                            $total_Month_Overtime +=$recordDTR[0]->overtime_minutes;
                            $total_Month_Undertime += $recordDTR[0]->undertime_minutes;
                        }else {
                            $invalidEntry[] =  $this->Attendance($year_of,$month_of,$i,$recordDTR);
                        }

                    }else if (
                        !in_array($i,$presentDays) &&
                         in_array($i,$empschedule) &&
                         date('D',strtotime($year_of.'-'.$month_of.'-'.$i)) != "Sun" &&
                         date('D',strtotime($year_of.'-'.$month_of.'-'.$i)) != "Sat" &&
                         strtotime(date('Y-m-d',strtotime($year_of.'-'.$month_of.'-'.$i))) <  strtotime(date('Y-m-d'))
                         ){
                        //echo $i."-A  \n";
                        $absences[] = [
                            'dateRecord'=>date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
                        ];
                    }

                    else {
                     //   echo $i."-DO\n";
                        $dayoff[] = [
                            'dateRecord'=>date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
                        ];
                    }
                }

                $presentCount = count(array_filter($attd,function($d){
                    return $d['total_working_minutes'] !==0;
                }));
                $Number_Absences = count($absences);// + count($invalidEntry);

                $data[] = [
                    'Biometric_id'=> $biometric_id,
                    'EmployeeNo'=>$Employee->employee_id,
                    'Name'=>$Employee->personalInformation->name(),
                    'Month'=>$month_of,
                    'Year'=>$year_of,

                    'TotalWorkingMinutes'=>$total_Month_WorkingMinutes,
                    'TotalWorkingHours'=>$this->ToHours($total_Month_WorkingMinutes),
                    'TotalOvertimeMinutes'=>$total_Month_Overtime ,
                    'TotalUndertimeMinutes'=>$total_Month_Undertime,
                    'NoofPresentDays'=>$presentCount ,
                    'NoofLeaveWoPay'=>count($lwop),
                    'NoofLeaveWPay'=>count($lwp),
                    'NoofAbsences'=> $Number_Absences,
                    'NoofInvalidEntry'=>count($invalidEntry),
                    'NoofDayOff'=>count($dayoff),
                    'schedule'=>count($this->helper->Allschedule($biometric_id, $month_of, $year_of, null,null,null,null)['schedule'])

                    //   'Attendance'=>$attd,
                    //    'Invalid'=>$invalidEntry,
                    // 'Leavewopay'=>$lwop,
                    //  'Leavewpay'=>$lwp,
                    //  'Absences'=>$absences,
                    //  'Dayoff'=>$dayoff
                ];
            }
        return $data;






    }
}


