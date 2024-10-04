<?php

namespace App\Http\Controllers\PayrollHooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmployeeProfile;
use App\Models\DailyTimeRecords;
use Illuminate\Support\Facades\DB;
use App\Methods\Helpers;
use App\Models\LeaveType;
use App\Models\SalaryGrade;
use App\Models\DeviceLogs;
use App\Http\Controllers\PayrollHooks\ComputationController;
use App\Http\Controllers\DTR\DeviceLogsController;
use App\Http\Controllers\DTR\DTRcontroller;
use Carbon\Carbon;
use App\Models\InActiveEmployee;
use App\Helpers\Helpers as help;


class GenerateReportController extends Controller
{
    protected $helper;
    protected $computed;

    protected $DeviceLog;

    protected $dtr;
    public function __construct()
    {
        $this->helper = new Helpers();
        $this->computed = new ComputationController();
        $this->DeviceLog = new DeviceLogsController();
        $this->dtr = new DTRcontroller();
    }


    public function ToHours($minutes)
    {
        $hours = $minutes / 60;
        return $hours;
    }


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


    public function getNightDifferentialHours($startTime, $endTime, $biometric_id, $wBreak, $DaySchedule)
    {
        // $startTime = "2024-07-14 21:46:46";
        // $endTime = "2024-07-15 06:13:35";



        if (count($wBreak) == 0 && $startTime && $endTime && count($DaySchedule)) {

            // Convert start and end times to DateTime objects
            $startTime = new \DateTime($startTime);
            $endTime = new \DateTime($endTime);

            //   echo $startTime->format('Y-m-d H:i:s') . " " . $endTime->format('Y-m-d H:i:s') . " " . count($wBreak) . " " . count($DaySchedule) . "\n";

            // Ensure that the end time is after the start time
            if ($endTime <= $startTime) {
                $endTime->modify('+1 day');
            }
            //  return $endTime->format("Y-m-d H:i:s");
            $totalMinutes = 0;
            $totalHours = 0;
            $details = [];

            // Loop through each day in the range
            $current = clone $startTime;

            while ($current <= $endTime) {
                // Calculate night period overlaps for the current day
                $nightStart = (clone $current)->setTime(18, 0, 0);
                $midnight = (clone $current)->setTime(0, 0, 0)->modify('+1 day');
                $nightEnd = (clone $current)->setTime(6, 0, 0)->modify('+1 day');

                // Calculate overlap with night period for the first day (6 PM to 12 AM)
                $overlapStart = max($current, $nightStart);
                $overlapEnd = min($endTime, $midnight);

                // Calculate total minutes and hours for the first day overlap (6 PM to 12 AM)
                if ($overlapStart <= $overlapEnd) {
                    $interval = $overlapStart->diff($overlapEnd);
                    $minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
                    $hours = $interval->h + ($interval->i / 60);

                    // $check = DailyTimeRecords::where('biometric_id', $biometric_id)
                    //     ->where('dtr_date', $overlapStart->format('Y-m-d'));

                    // if ($check->exists()) {
                    $details[] = [
                        'minutes' => $minutes,
                        'hours' => round($hours, 0),
                        'date' => $overlapStart->format('Y-m-d'),
                        'period' => '6 PM to 12 AM',
                        'biometric_id' => $biometric_id,
                    ];
                    //  }

                    $totalMinutes += $minutes;
                    $totalHours += $hours;
                }

                // Check if there is an overlap into the next day (12 AM to 6 AM)
                if ($endTime > $midnight) {
                    $nextDayOverlapStart = $midnight;
                    $nextDayOverlapEnd = min($endTime, $nightEnd);

                    $interval = $nextDayOverlapStart->diff($nextDayOverlapEnd);
                    $minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
                    $hours = $interval->h + ($interval->i / 60);

                    // $check = DailyTimeRecords::where('biometric_id', $biometric_id)
                    //     ->where('dtr_date', $nextDayOverlapStart->format('Y-m-d'))->get();

                    //if ($check->exists()) {
                    $details[] = [
                        'minutes' => $minutes,
                        'hours' => round($hours, 0),
                        'date' => $nextDayOverlapStart->format('Y-m-d'),
                        'period' => '12 AM to 6 AM',
                        'biometric_id' => $biometric_id,
                    ];
                    //  }

                    $totalMinutes += $minutes;
                    $totalHours += $hours;
                }

                // Move to the next day
                $current->modify('+1 day')->setTime(0, 0, 0);
            }


            if ($totalMinutes && $totalHours) {
                return [
                    'biometric_id' => $biometric_id,
                    'total_minutes' => $totalMinutes,
                    'total_hours' => round($totalHours, 0),
                    'details' => $details,
                ];
            }
        }

        return null; // Return null if the conditions are not met
    }

    public function test(Request $request)
    {
        return $this->GenerateDataReport($request);
    }

    public function AsyncrounousRun_GenerateDataReport(Request $request)
    {
        for ($i = 0; $i < 2; $i++) {
            $this->GenerateDataReport($request);
        }
        return $this->GenerateDataReport($request);
    }
    public function divideintoTwo($number)
    {
        $firstHalf = floor($number / 2);

        $secondHalf = $number - $firstHalf;
        return [help::customRound($firstHalf), help::customRound($secondHalf)];
    }



    public function GenerateDataReport(Request $request)
    {

        ini_set('max_execution_time', 86400); //24 hours compiling time
        $month_of = $request->month_of;
        $year_of = $request->year_of;


        $employeeIds = DB::table('daily_time_records')
            ->whereYear('dtr_date', $year_of)
            ->whereMonth('dtr_date', $month_of)
            ->pluck('biometric_id'); //employee_id
        $profiles = EmployeeProfile::whereIn('biometric_id', $employeeIds)
            // ->limit(7)
            ->get();



        // $profiles = EmployeeProfile::where("biometric_id",493)->get();



        $data = [];

        $days_In_Month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);
        $daysTotalMonth = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);
        $defaultInit = 1;
        $nightDifferentials = [];
        $whole_month = $request->whole_month;
        $first_half = $request->first_half;
        $second_half = $request->second_half;
        $init = 1;
        $count = [];
        foreach ($profiles as $row) {
            $Employee = $row;
            if (!$Employee->assignedArea) {
                continue;
            }

            if ($Employee->employmentType->name == "Job Order") {
                if ($first_half) {
                    $init = 1;
                    $days_In_Month = 15;
                } else if ($second_half) {
                    $init = 16;
                }
            } else {
                $init = 1;
                $days_In_Month = $daysTotalMonth;
            }
            if ($first_half || $second_half) {
                if ($Employee->employmentType->name == "Job Order") {
                    // echo "Job Order \n";
                    $data[] = $this->retrieveData($Employee, $row, $month_of, $year_of, $init, $days_In_Month, $defaultInit, $daysTotalMonth, $request);
                }
            } else {
                if ($Employee->employmentType->name != "Job Order") {
                    $data[] = $this->retrieveData($Employee, $row, $month_of, $year_of, $init, $days_In_Month, $defaultInit, $daysTotalMonth, $request);
                }
            }
        }


        return $data;
    }


    public function retrieveData($Employee, $row, $month_of, $year_of, $init, $days_In_Month, $defaultInit, $daysTotalMonth, $request)
    {
        $nightDifferentials = [];
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
            $wBreak = $Schedule['break_Time_Req'];
            $nightDifferentials[] = $this->getNightDifferentialHours($val->first_in, $val->first_out, $biometric_id, [], $DaySchedule);
        }

        if ($Employee) {

            $leavedata = $this->processLeaveApplications($Employee);
            $obData = $this->processOfficialBusiness($Employee);
            $otData = $this->processOfficialTime($Employee);
            $ctoData = $this->processCTO($Employee);


            if (count($empschedule) >= 1) {
                $empschedule = array_map(function ($sc) {
                    // return isset($sc['scheduleDate']) && (int)date('d', strtotime($sc['scheduleDate']));
                    return (int) date('d', strtotime($sc['scheduleDate']));
                }, $this->helper->Allschedule($biometric_id, $month_of, $year_of, null, null, null, null)['schedule']);
            }

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

            $presentDays = array_map(function ($d) use ($empschedule) {
                if (in_array($d->day, $empschedule)) {
                    return $d->day;
                }
            }, $dtr->toArray());

            $AbsentDays = array_values(array_filter(array_map(function ($d) use ($presentDays) {
                if (!in_array($d, $presentDays) && $d != null) {
                    return $d;
                }
            }, $empschedule)));


            for ($i = $init; $i <= $days_In_Month; $i++) {
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


                // echo "Name :".$Employee?->personalInformation->name()."\n Biometric_id :".$Employee->biometric_id."\n"?? "\n"."\n";
                for ($i = $init; $i <= $days_In_Month; $i++) {

                    $leaveApplication = array_filter($filteredleaveDates, function ($timestamp) use ($year_of, $month_of, $i,) {
                        $dateToCompare = date('Y-m-d', $timestamp['dateReg']);
                        $dateToMatch = date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i));
                        return $dateToCompare === $dateToMatch;
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


                    if ($leave_Count) {

                        if (array_values($leaveApplication)[0]['status']) {
                            //  echo $i."-LwoPay \n";
                            $lwop[] = [
                                'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
                            ];
                            // deduct to salary
                        } else {
                            //  echo $i."-LwPay \n";
                            $lwp[] = [
                                'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
                            ];
                            $total_Month_WorkingMinutes += 480;
                        }
                    } else if ($ob_Count || $ot_Count || $cto_Count) {
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

                        if (isset($recordDTR[0])) {
                            if (
                                ($recordDTR[0]->first_in && $recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out) || // all entry
                                (!$recordDTR[0]->first_in && !$recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out) || //3-4
                                ($recordDTR[0]->first_in && $recordDTR[0]->first_out && !$recordDTR[0]->second_in && !$recordDTR[0]->second_out) || // 1-2
                                ($recordDTR[0]->first_in && $recordDTR[0]->first_out && $recordDTR[0]->second_in && !$recordDTR[0]->second_out) // 1-2-3
                            ) {
                                $attd[] = $this->Attendance($year_of, $month_of, $i, $recordDTR);
                                $total_Month_WorkingMinutes += $recordDTR[0]->total_working_minutes;
                                $total_Month_Overtime += $recordDTR[0]->overtime_minutes;
                                $total_Month_Undertime += $recordDTR[0]->undertime_minutes;
                            } else {
                                $invalidEntry[] = $this->Attendance($year_of, $month_of, $i, $recordDTR);
                            }
                        } else {
                            // Handle the case where $recordDTR[0] does not exist
                            $invalidEntry[] = "No record found for the given day.";
                        }
                    } else if (
                        in_array($i, $AbsentDays) &&
                        in_array($i, $empschedule) &&
                        strtotime(date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i))) < strtotime(date('Y-m-d'))
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



                $presentCount = count(array_filter($attd, function ($d) {
                    return $d['total_working_minutes'] !== 0;
                }));


                $Number_Absences = count($absences) - count($lwop);
                $schedule_ = $this->helper->Allschedule($biometric_id, $month_of, $year_of, null, null, null, null)['schedule'];

                $scheds = array_map(function ($d) {
                    return (int) date('d', strtotime($d['scheduleDate']));
                }, $schedule_);

                $filtered_scheds = array_values(array_filter($scheds, function ($value) use ($init, $days_In_Month) {
                    return $value >= $init && $value <= $days_In_Month;
                }));

                $filtered_scheds_forsal = array_values(array_filter($scheds, function ($value) use ($defaultInit, $daysTotalMonth) {
                    return $value >= $defaultInit && $value <= $daysTotalMonth;
                }));

                $employeeAssignedAreas = $Employee->assignedAreas->first();
                $salaryGrade = $employeeAssignedAreas->salary_grade_id ?? 1;
                $salaryStep = $employeeAssignedAreas->salary_grade_step ?? 1;



                $basicSalary = $this->computed->BasicSalary($salaryGrade, $salaryStep, count($filtered_scheds_forsal));



                //return $presentCount * $basicSalary['GrandTotal'] / count($filtered_scheds);
                $GrossSalary = $this->computed->GrossSalary($presentCount, $basicSalary['GrandTotal'], count($filtered_scheds));
                $Rates = $this->computed->Rates($basicSalary['GrandTotal'], count($filtered_scheds_forsal));

                $undertimeRate = $this->computed->UndertimeRates($total_Month_Undertime, $Rates);
                $absentRate = $this->computed->AbsentRates($Number_Absences, $Rates);


                $NetSalary = $this->computed->NetSalaryFromTimeDeduction($Rates, $total_Month_WorkingMinutes, $undertimeRate, $absentRate, $basicSalary['Total']);


                //  $data[]=InActiveEmployee::where('employee_id',$Employee->employee_id)->first();

                $OverAllnetSalary = $this->TOTALNETSALARY($request, $biometric_id);



                $leaveApplication = array_values($Employee->leaveApplications->filter(function ($row) use ($month_of, $year_of) {
                    if ($row->name == "Study Leave") {
                        return date('Y', strtotime($row->date_from)) === $year_of && date('m', strtotime($row->date_from)) === $month_of;
                    }
                })->toArray());

                list($firstHalf, $secondHalf) = $this->divideintoTwo($OverAllnetSalary);
                return [
                    'Biometric_id' => $biometric_id,
                    'Payroll' => $init . " - " . $days_In_Month,
                    'From' => $init,
                    'To' => $days_In_Month,
                    'Month' => $month_of,
                    'Year' => $year_of,
                    'Is_out' => $this->computed->OutofPayroll($OverAllnetSalary, $Employee->employmentType),
                    'NightDifferentials' => array_values(array_filter($nightDifferentials, function ($row) use ($biometric_id) {
                        return isset($row['biometric_id']) && $row['biometric_id'] == $biometric_id;
                    })),
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
                    'GrandBasicSalary' => $basicSalary['GrandTotal'],
                    'Rates' => $Rates,
                    'GrossSalary' => $Rates['Minutes'] * $total_Month_WorkingMinutes,
                    'TimeDeductions' => [
                        'AbsentRate' => $absentRate,
                        'UndertimeRate' => $undertimeRate,
                    ],
                    'NetSalary' => $NetSalary,
                    'OverallNetSalary' => $OverAllnetSalary,

                    'Employee' => [
                        'employee_id' => $Employee->employee_id,
                        'Information' => $Employee->personalInformation,
                        'Designation' => $Employee->findDesignation(),
                        'Hired' => $Employee->date_hired,
                        'EmploymentType' => $Employee->employmentType,
                        'Excluded' => InActiveEmployee::where('employee_id', $Employee->employee_id)->first(),
                        'leaveApplications' => $leaveApplication,
                        'employeeLeaveCredits' => $Employee->employeeLeaveCredits

                    ],
                    'Assigned_area' => $Employee->assignedArea->findDetails(),
                    'SalaryData' => [
                        'step' => $Employee->assignedArea->salary_grade_step,
                        'salaryGroup' => $Employee->assignedArea->salaryGrade,
                    ],


                    // 'Attendance'=>$attd,
                    // 'Invalid'=>$invalidEntry,
                    // 'absences'=>$absences,
                    // 'Leavewopay'=>$lwop,
                    //  'Leavewpay'=>$lwp,
                    //  'Absences'=>$absences,
                    //  'Dayoff'=>$dayoff
                ];
            }
        }
    }
    public function TOTALNETSALARY($request, $biometric_id)
    {
        ini_set('max_execution_time', 86400); // 24 hours compiling time

        $month_of = $request->month_of;
        $year_of = $request->year_of;



        $profiles = EmployeeProfile::where('biometric_id', $biometric_id)
            ->get();

        $data = [];

        // Set the full range for a month (1 to 30)
        $init = 1;
        $days_In_Month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);

        foreach ($profiles as $row) {
            $Employee = $row;

            // Skip if the employee has no assigned area
            if (!$Employee->assignedArea) {
                continue;
            }

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
                $wBreak = $Schedule['break_Time_Req'];
                $nightDifferentials[] = $this->getNightDifferentialHours($val->first_in, $val->first_out, $biometric_id, [], $DaySchedule);

                if (count($DaySchedule) >= 1) {
                    $validate = [
                        (object) [
                            'id' => $val->id,
                            'first_in' => $val->first_in,
                            'first_out' => $val->first_out,
                            'second_in' => $val->second_in,
                            'second_out' => $val->second_out
                        ],
                    ];
                }
            }

            // Remaining processing logic for leave applications, official business, official time, etc.
            $leaveData = $this->processLeaveApplications($Employee);
            $obData = $this->processOfficialBusiness($Employee);
            $otData = $this->processOfficialTime($Employee);
            $ctoData = $this->processCTO($Employee);



            if (count($empschedule) >= 1) {
                $empschedule = array_map(function ($sc) {
                    // return isset($sc['scheduleDate']) && (int)date('d', strtotime($sc['scheduleDate']));
                    return (int) date('d', strtotime($sc['scheduleDate']));
                }, $this->helper->Allschedule($biometric_id, $month_of, $year_of, null, null, null, null)['schedule']);
            }

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

            $presentDays = array_map(function ($d) use ($empschedule) {
                if (in_array($d->day, $empschedule)) {
                    return $d->day;
                }
            }, $dtr->toArray());

            $AbsentDays = array_values(array_filter(array_map(function ($d) use ($presentDays) {
                if (!in_array($d, $presentDays) && $d != null) {
                    return $d;
                }
            }, $empschedule)));

            for ($i = $init; $i <= $days_In_Month; $i++) {
                $filteredleaveDates = [];
                $leaveStatus = [];

                // echo "Name :".$Employee?->personalInformation->name()."\n Biometric_id :".$Employee->biometric_id."\n"?? "\n"."\n";
                for ($i = $init; $i <= $days_In_Month; $i++) {

                    $leaveApplication = array_filter($filteredleaveDates, function ($timestamp) use ($year_of, $month_of, $i,) {
                        $dateToCompare = date('Y-m-d', $timestamp['dateReg']);
                        $dateToMatch = date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i));
                        return $dateToCompare === $dateToMatch;
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


                    if ($leave_Count) {

                        if (array_values($leaveApplication)[0]['status']) {
                            //  echo $i."-LwoPay \n";
                            $lwop[] = [
                                'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
                            ];
                            // deduct to salary
                        } else {
                            //  echo $i."-LwPay \n";
                            $lwp[] = [
                                'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
                            ];
                            $total_Month_WorkingMinutes += 480;
                        }
                    } else if ($ob_Count || $ot_Count || $cto_Count) {
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

                        if (isset($recordDTR[0])) {
                            if (
                                ($recordDTR[0]->first_in && $recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out) || // all entry
                                (!$recordDTR[0]->first_in && !$recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out) || //3-4
                                ($recordDTR[0]->first_in && $recordDTR[0]->first_out && !$recordDTR[0]->second_in && !$recordDTR[0]->second_out) || // 1-2
                                ($recordDTR[0]->first_in && $recordDTR[0]->first_out && $recordDTR[0]->second_in && !$recordDTR[0]->second_out) // 1-2-3
                            ) {
                                $attd[] = $this->Attendance($year_of, $month_of, $i, $recordDTR);
                                $total_Month_WorkingMinutes += $recordDTR[0]->total_working_minutes;
                                $total_Month_Overtime += $recordDTR[0]->overtime_minutes;
                                $total_Month_Undertime += $recordDTR[0]->undertime_minutes;
                            } else {
                                $invalidEntry[] = $this->Attendance($year_of, $month_of, $i, $recordDTR);
                            }
                        } else {
                            // Handle the case where $recordDTR[0] does not exist
                            $invalidEntry[] = "No record found for the given day.";
                        }
                    } else if (
                        in_array($i, $AbsentDays) &&
                        in_array($i, $empschedule) &&
                        strtotime(date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i))) < strtotime(date('Y-m-d'))
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



                $presentCount = count(array_filter($attd, function ($d) {
                    return $d['total_working_minutes'] !== 0;
                }));
                $Number_Absences = count($absences) - count($lwop);
                $schedule_ = $this->helper->Allschedule($biometric_id, $month_of, $year_of, null, null, null, null)['schedule'];

                $scheds = array_map(function ($d) {
                    return (int) date('d', strtotime($d['scheduleDate']));
                }, $schedule_);

                $filtered_scheds = array_values(array_filter($scheds, function ($value) use ($init, $days_In_Month) {
                    return $value >= $init && $value <= $days_In_Month;
                }));


                $employeeAssignedAreas = $Employee->assignedAreas->first();
                $salaryGrade = $employeeAssignedAreas->salary_grade_id ?? 1;
                $salaryStep = $employeeAssignedAreas->salary_grade_step ?? 1;



                $basicSalary = $this->computed->BasicSalary($salaryGrade, $salaryStep, count($filtered_scheds));


                //return $presentCount * $basicSalary['GrandTotal'] / count($filtered_scheds);
                $GrossSalary = $this->computed->GrossSalary($presentCount, $basicSalary['GrandTotal'], count($filtered_scheds));
                $Rates = $this->computed->Rates($basicSalary['GrandTotal'], count($filtered_scheds));

                $undertimeRate = $this->computed->UndertimeRates($total_Month_Undertime, $Rates);
                $absentRate = $this->computed->AbsentRates($Number_Absences, $Rates);
                $NetSalary = $this->computed->NetSalaryFromTimeDeduction($Rates, $total_Month_WorkingMinutes, $undertimeRate, $absentRate, $basicSalary['Total']);


                return $NetSalary;
            }
        }
    }


    protected function processCTO($employee)
    {
        if (!$employee->ctoApplications) {
            return [];
        }

        $ctoApplications = $employee->ctoApplications->filter(function ($row) {
            return $row['status'] == "approved";
        });

        $ctoData = [];
        foreach ($ctoApplications as $rows) {
            $ctoData[] = [
                'date' => date('Y-m-d', strtotime($rows['date'])),
                'purpose' => $rows['purpose'],
                'remarks' => $rows['remarks'],
            ];
        }

        return $ctoData;
    }

    protected function processOfficialTime($employee)
    {
        if (!$employee->officialTimeApplications) {
            return [];
        }

        $officialTime = $employee->officialTimeApplications->filter(function ($row) {
            return $row['status'] == "approved";
        });

        $otData = [];
        foreach ($officialTime as $rows) {
            $otData[] = [
                'date_from' => $rows['date_from'],
                'date_to' => $rows['date_to'],
                'purpose' => $rows['purpose'],
                'dates_covered' => $this->helper->getDateIntervals($rows['date_from'], $rows['date_to']),
            ];
        }

        return $otData;
    }

    protected function processOfficialBusiness($employee)
    {
        if (!$employee->officialBusinessApplications) {
            return [];
        }

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

        return $obData;
    }


    protected function processLeaveApplications($employee)
    {
        if (!$employee->leaveApplications) {
            return [];
        }

        $leaveApplications = $employee->leaveApplications->filter(function ($row) {
            return $row['status'] == "received";
        });

        $leaveData = [];
        foreach ($leaveApplications as $rows) {
            $leaveData[] = [
                'country' => $rows['country'],
                'city' => $rows['city'],
                'from' => $rows['date_from'],
                'to' => $rows['date_to'],
                'leavetype' => LeaveType::find($rows['leave_type_id'])->name ?? "",
                'without_pay' => $rows['without_pay'],
                'dates_covered' => $this->helper->getDateIntervals($rows['date_from'], $rows['date_to']),
            ];
        }

        return $leaveData;
    }
}
