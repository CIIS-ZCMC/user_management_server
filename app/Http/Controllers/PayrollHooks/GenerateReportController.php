<?php

namespace App\Http\Controllers\PayrollHooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmployeeProfile;
use App\Models\DailyTimeRecord;
use Illuminate\Support\Facades\DB;
use App\Methods\Helpers;
use App\Models\LeaveType;
<<<<<<< HEAD

=======
use App\Models\SalaryGrade;
use App\Http\Controllers\PayrollHooks\ComputationController;
//SalaryGrade
>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
class GenerateReportController extends Controller
{
    protected $helper;
    protected $computed;

    public function __construct()
    {
        $this->helper = new Helpers();
<<<<<<< HEAD
=======
        $this->computed = new ComputationController();
>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
    }


    public function ToHours($minutes)
    {
        $hours = $minutes / 60;
        return $hours;
    }
<<<<<<< HEAD
=======


>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
    public function Attendance($year_of, $month_of, $i, $recordDTR)
    {
        return [
            'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
            'firstin' => $recordDTR[0]->first_in,
            'firstout' => $recordDTR[0]->first_out,
            'secondin' => $recordDTR[0]->second_in,
            'secondout' => $recordDTR[0]->second_out,
            'total_working_minutes' => $recordDTR[0]->total_working_minutes,
            'overtime_minutes' => $recordDTR[0]->overtime_minutes,
            'undertime_minutes' => $recordDTR[0]->undertime_minutes,
            'overall_minutes_rendered' => $recordDTR[0]->overall_minutes_rendered,
            'total_minutes_reg' => $recordDTR[0]->total_minutes_reg
        ];
    }

    public function test(Request $request)
    {
<<<<<<< HEAD
        // Retrieve month and year from the request
        $month_of = (int) $request->month_of;
        $year_of = (int) $request->year_of;

        // Get biometric IDs from daily_time_records table for the specified month and year
=======
        $month_of = $request->month_of;
        $year_of = $request->year_of;
>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
        $biometricIds = DB::table('daily_time_records')
            ->whereYear('dtr_date', $year_of)
            ->whereMonth('dtr_date', $month_of)
            ->pluck('biometric_id');

        // Get employee profiles matching the biometric IDs
        $profiles = DB::table('employee_profiles')
<<<<<<< HEAD
            // Uncomment this line to filter by biometric IDs
            // ->whereIn('biometric_id', $biometricIds)
            ->where('biometric_id', 493) // Example biometric ID for testing
=======
            // ->whereIn('biometric_id', $biometricIds)
            ->where('biometric_id', 493) // 494
>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
            ->get();

        $data = [];

<<<<<<< HEAD
        // Iterate through each employee profile
        foreach ($profiles as $row) {
            // Retrieve the employee profile using the model
            $Employee = EmployeeProfile::find($row->id);
            $biometric_id = $row->biometric_id;

            // Get daily time records for the employee in the specified month and year
=======
        $data = [];

        foreach ($profiles as $row) {
            $Employee = EmployeeProfile::find($row->id);
            $biometric_id = $row->biometric_id;

>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
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
<<<<<<< HEAD

            $empschedule = [];

            // Process each daily time record
=======
            $empschedule = [];

>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
            foreach ($dtr as $val) {
                $bioEntry = [
                    'first_entry' => $val->first_in ?? $val->second_in,
                    'date_time' => $val->first_in ?? $val->second_in
                ];
                $Schedule = $this->helper->CurrentSchedule($biometric_id, $bioEntry, false);
                $DaySchedule = $Schedule['daySchedule'];
                $empschedule[] = $DaySchedule;

<<<<<<< HEAD
                // Save total working hours if schedule is valid
=======
>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
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

            // Retrieve the employee profile again to access related data
            $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();

            // Process leave applications
            if ($employee->leaveApplications) {
                $leaveapp = $employee->leaveApplications->filter(function ($row) {
                    return $row['status'] == "received";
                });

<<<<<<< HEAD
=======
            if ($employee->leaveApplications) {
                //Leave Applications
                $leaveapp  = $employee->leaveApplications->filter(function ($row) {
                    return $row['status'] == "received";
                });



>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
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

<<<<<<< HEAD
            // Process official business applications
=======


            //Official business
>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
            if ($employee->officialBusinessApplications) {
                $officialBusiness = array_values($employee->officialBusinessApplications->filter(function ($row) {
                    return $row['status'] == "approved";
                })->toArray());
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

<<<<<<< HEAD
            // Process official time applications
            if ($employee->officialTimeApplications) {
=======
            if ($employee->officialTimeApplications) {
                //Official Time
>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
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

<<<<<<< HEAD
            // Process CTO applications
=======
>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
            if ($employee->ctoApplications) {
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
<<<<<<< HEAD
=======
            if (count($empschedule) >= 1) {
                $empschedule = array_map(function ($sc) {
                    // return isset($sc['scheduleDate']) && (int)date('d', strtotime($sc['scheduleDate']));
                    return (int)date('d', strtotime($sc['scheduleDate']));
                }, $this->helper->Allschedule($biometric_id, $month_of, $year_of, null, null, null, null)['schedule']);
            }
            $days_In_Month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);
            // echo "Name :".$Employee?->personalInformation->name()."\n Biometric_id :".$Employee->biometric_id."\n"?? "\n"."\n";

            $attd = [];
            $lwop = [];
            $lwp = [];
            $obot = [];
            $absences = [];
            $dayoff = [];
            $total_Month_WorkingMinutes = 0;
            $total_Month_Overtime = 0;
            $total_Month_Undertime = 0;
            $invalidEntry = [];
>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4

            // Filter schedules for the month
            if (count($empschedule) >= 1) {
                $empschedule = array_map(function ($sc) {
                    return (int)date('d', strtotime($sc['scheduleDate']));
                }, $this->helper->Allschedule($biometric_id, $month_of, $year_of, null, null, null, null)['schedule']);
            }

<<<<<<< HEAD
            $days_In_Month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);

            // Initialize variables for attendance data
            $attd = [];
            $lwop = [];
            $lwp = [];
            $obot = [];
            $absences = [];
            $dayoff = [];
            $total_Month_WorkingMinutes = 0;
            $total_Month_Overtime = 0;
            $total_Month_Undertime = 0;
            $invalidEntry = [];

            // Determine present and absent days
=======
>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
            $presentDays = array_map(function ($d) use ($empschedule) {
                if (in_array($d->day, $empschedule)) {
                    return  $d->day;
                }
            }, $dtr->toArray());

            $AbsentDays = array_values(array_filter(array_map(function ($d) use ($presentDays) {
                if (!in_array($d, $presentDays) && $d != null) {
                    return  $d;
                }
            }, $empschedule)));

<<<<<<< HEAD
            // Determine the range of days to process
=======

>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
            $whole_month = $request->whole_month;
            $first_half = $request->first_half;
            $second_half = $request->second_half;
            $init = 1;
            if ($first_half) {
                $days_In_Month = 15;
            } else if ($second_half) {
                $init = 16;
            }

<<<<<<< HEAD
            // Iterate through each day of the month
            for ($i = $init; $i <= $days_In_Month; $i++) {

                // Filter leave dates
=======


            for ($i = $init; $i <= $days_In_Month; $i++) {



>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
                $filteredleaveDates = [];
                $leaveStatus = [];
                foreach ($leavedata as $row) {
                    foreach ($row['dates_covered'] as $date) {
                        $filteredleaveDates[] = [
                            'dateReg' => strtotime($date),
                            'status' => $row['without_pay']
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
                    return $dateToCompare === $dateToMatch;
                });

                $leave_Count = count($leaveApplication);

<<<<<<< HEAD
                // Filter official business dates
=======
                $leave_Count = count($leaveApplication);

                //Check obD ates
>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
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

<<<<<<< HEAD
                // Filter official time dates
=======
                //Check otDates
>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
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

<<<<<<< HEAD
                // Filter CTO dates
=======
>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
                $ctoApplication = array_filter($ctoData, function ($row) use ($year_of, $month_of, $i) {
                    $dateToCompare = date('Y-m-d', strtotime($row['date']));
                    $dateToMatch = date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i));
                    return $dateToCompare === $dateToMatch;
                });
                $cto_Count = count($ctoApplication);

<<<<<<< HEAD
                // Process leave without pay
                if ($leave_Count) {
                    if (array_values($leaveApplication)[0]['status']) {
                        $lwop[] = [
                            'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
                        ];
                    } else {
=======

                if ($leave_Count) {

                    if (array_values($leaveApplication)[0]['status']) {
                        //  echo $i."-LwoPay \n";
                        $lwop[] = [
                            'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
                        ];
                        // deduct to salary
                    } else {
                        //  echo $i."-LwPay \n";
>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
                        $lwp[] = [
                            'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
                        ];
                        $total_Month_WorkingMinutes += 480;
                    }
<<<<<<< HEAD
                }
                // Process official business or official time
                else if ($ob_Count ||  $ot_Count) {
                    $obot[] = [
                        'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
                    ];
                    $total_Month_WorkingMinutes += 480;
                }
                // Process attendance
                else if (in_array($i, $presentDays) && in_array($i, $empschedule)) {
                    $recordDTR = array_values(array_filter($dtr->toArray(), function ($d) use ($year_of, $month_of, $i) {
                        return $d->dtr_date == date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i));
                    }));

                    if (
                        ($recordDTR[0]->first_in && $recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out) || // all entries
                        (!$recordDTR[0]->first_in && !$recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out) || // 3-4
=======
                } else if ($ob_Count ||  $ot_Count) {
                    // echo $i."-ob or ot Paid \n";
                    $obot[] = [
                        'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),

                    ];
                    $total_Month_WorkingMinutes += 480;
                } else

                    if (in_array($i, $presentDays) && in_array($i, $empschedule)) {

                    $recordDTR = array_values(array_filter($dtr->toArray(), function ($d) use ($year_of, $month_of, $i) {
                        return $d->dtr_date == date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i));
                    }));
                    // echo $i."-P \n";


                    if (
                        ($recordDTR[0]->first_in && $recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out) || // all entry
                        (!$recordDTR[0]->first_in && !$recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out)  || //3-4
>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
                        ($recordDTR[0]->first_in && $recordDTR[0]->first_out && !$recordDTR[0]->second_in && !$recordDTR[0]->second_out) || // 1-2
                        ($recordDTR[0]->first_in && $recordDTR[0]->first_out && $recordDTR[0]->second_in && !$recordDTR[0]->second_out) // 1-2-3
                    ) {
                        $attd[] = $this->Attendance($year_of, $month_of, $i, $recordDTR);
                        $total_Month_WorkingMinutes += $recordDTR[0]->total_working_minutes;
                        $total_Month_Overtime += $recordDTR[0]->overtime_minutes;
                        $total_Month_Undertime += $recordDTR[0]->undertime_minutes;
                    } else {
                        $invalidEntry[] =  $this->Attendance($year_of, $month_of, $i, $recordDTR);
                    }
<<<<<<< HEAD
                }
                // Process absences
                else if (
                    in_array($i, $AbsentDays) &&
                    in_array($i, $empschedule) &&
                    strtotime(date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i))) <  strtotime(date('Y-m-d'))
                ) {
                    $absences[] = [
                        'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
                    ];
                }
                // Process day off
                else {
                    $dayoff[] = [
                        'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
                    ];
                }
            }

            // Calculate attendance statistics
=======
                } else if (
                    in_array($i, $AbsentDays) &&
                    in_array($i, $empschedule) &&
                    strtotime(date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i))) <  strtotime(date('Y-m-d'))
                ) {
                    //echo $i."-A  \n";

                    $absences[] = [
                        'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
                    ];
                } else {
                    //   echo $i."-DO\n";
                    $dayoff[] = [
                        'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
                    ];
                }
            }



>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
            $presentCount = count(array_filter($attd, function ($d) {
                return $d['total_working_minutes'] !== 0;
            }));
            $Number_Absences = count($absences) - count($lwop);
            $schedule_ = $this->helper->Allschedule($biometric_id, $month_of, $year_of, null, null, null, null)['schedule'];

            $scheds = array_map(function ($d) {
                return (int)date('d', strtotime($d['scheduleDate']));
            }, $schedule_);

            $filtered_scheds = array_values(array_filter($scheds, function ($value) use ($init, $days_In_Month) {
                return $value >= $init && $value <= $days_In_Month;
            }));

<<<<<<< HEAD
            // Prepare data for the response
            $data[] = [
=======
            $employeeAssignedAreas =  $employee->assignedAreas->first();
            $salaryGrade = $employeeAssignedAreas->salary_grade_id;
            $salaryStep  = $employeeAssignedAreas->salary_grade_step;

            $basicSalary = $this->computed->BasicSalary($salaryGrade, $salaryStep,count($filtered_scheds));
            $GrossSalary = $this->computed->GrossSalary($presentCount,$basicSalary['GrandTotal']);
            $Rates = $this->computed->Rates($basicSalary['GrandTotal']);
            $undertimeRate = $this->computed->UndertimeRates($total_Month_Undertime,$Rates);
            $absentRate = $this->computed->AbsentRates($Number_Absences,$Rates);
            $NetSalary = $this->computed->NetSalary($undertimeRate,$absentRate,$basicSalary['Total']);
            $data[] = [

>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
                'Biometric_id' => $biometric_id,
                'EmployeeNo' => $Employee->employee_id,
                'Name' => $Employee->personalInformation->name(),
                'Payroll' => $init . " - " . $days_In_Month,
                'From' => $init,
                'To' => $days_In_Month,
                'Month' => $month_of,
                'Year' => $year_of,
                'TotalWorkingMinutes' => $total_Month_WorkingMinutes,
                'TotalWorkingHours' => $this->ToHours($total_Month_WorkingMinutes),
                'TotalOvertimeMinutes' => $total_Month_Overtime,
                'TotalUndertimeMinutes' => $total_Month_Undertime,
                'NoofPresentDays' => $presentCount,
                'NoofLeaveWoPay' => count($lwop),
                'NoofLeaveWPay' => count($lwp),
                'NoofAbsences' => $Number_Absences,
                'NoofInvalidEntry' => count($invalidEntry),
                'NoofDayOff' => count($dayoff),
                'schedule' => count($filtered_scheds),
<<<<<<< HEAD
            ];
        }

        // Return the processed data
=======
                'GrandBasicSalary'=> $basicSalary['GrandTotal'],
                'Rates'=>$Rates,
                'GrossSalary'=>$basicSalary['Total'],
                'TimeDeductions'=>[
                    'AbsentRate'=>$absentRate ,
                'UndertimeRate'=>$undertimeRate,
                ],
                'Deducted_from_GrossSal'=>[
                    'DeductedwAbsent'=>$GrossSalary,
                    'DeductedwUndertime'=> $basicSalary['Total'] - $undertimeRate
                ],
                'NetSalary'=> $NetSalary


                // 'Attendance'=>$attd,
                // 'Invalid'=>$invalidEntry,
                // 'absences'=>$absences,
                // 'Leavewopay'=>$lwop,
                //  'Leavewpay'=>$lwp,
                //  'Absences'=>$absences,
                //  'Dayoff'=>$dayoff
            ];
        }
>>>>>>> 535db82cdff42b0395e44973eee864c032c663b4
        return $data;
    }
}
