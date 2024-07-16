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
//SalaryGrade
class GenerateReportController extends Controller
{
    protected $helper;
    protected $computed;

    protected $DeviceLog;

    protected $dtr ;
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

    public function test(Request $request)
    {
<<<<<<< HEAD

        for ($i = 0; $i < 2; $i++) {
            $this->GenerateDataReport($request);
        }
        return  $this->GenerateDataReport($request);
=======
        return $this->dtr->RegenerateDTR();

>>>>>>> bf451cabc8c3e8c649e431451e05a39d6a03c40d
    }

    public function AsyncrounousRun_GenerateDataReport(Request $request)
    {
<<<<<<< HEAD
        for ($i = 0; $i < 2; $i++) {
            $this->GenerateDataReport($request);
        }
        return  $this->GenerateDataReport($request);

        //         $firsthalf = new Request([
        //                 'month_of'=>$request->month_of,
        //                 'year_of'=>$request->year_of,
        //                 'whole_month'=>0,
        //                 'first_half'=>1,
        //                 'second_half'=>0
        //         ]);

        //         $secondhalf = new Request([
        //             'month_of'=>$request->month_of,
        //             'year_of'=>$request->year_of,
        //             'whole_month'=>0,
        //             'first_half'=>0,
        //             'second_half'=>1
        //     ]);

        //     $whole = new Request([
        //         'month_of'=>$request->month_of,
        //         'year_of'=>$request->year_of,
        //         'whole_month'=>1,
        //         'first_half'=>0,
        //         'second_half'=>0
        // ]);

        //         $second_half = clone $request;
        //         $second_half->merge(['second_half' => 1]);
        //         $whole= clone $request;
        //         $whole->merge(['second_half' => 1]);
        //         $whole_month = [];
        //         $first_half = [];
        //         $second_half = [];
        //         for ($i=0; $i < 2; $i++) {
        //          $this->GenerateDataReport($whole);
        //         }

        //         return [
        //             'first_half'=> $this->GenerateDataReport($firsthalf),
        //             'second_half'=>$this->GenerateDataReport($secondhalf),
        //             'whole'=>$this->GenerateDataReport($whole),
        //         ];
    }



    public function GenerateDataReport(Request $request)
    {
=======
        for ($i=0; $i < 2; $i++) {
                     $this->GenerateDataReport($request);
                    }
            return  $this->GenerateDataReport($request);

    }

    public function GenerateDataReport(Request $request){
        ini_set('max_execution_time', 7200);
>>>>>>> bf451cabc8c3e8c649e431451e05a39d6a03c40d
        $month_of = $request->month_of;
        $year_of = $request->year_of;
        $biometricIds = DB::table('daily_time_records')
            ->whereYear('dtr_date', $yearOf)
            ->whereMonth('dtr_date', $monthOf)
            ->pluck('biometric_id');
<<<<<<< HEAD

        // Get employee profiles matching the biometric IDs
        $employeeProfiles = DB::table('employee_profiles')
            // Uncomment this line to filter by biometric IDs
            // ->whereIn('biometric_id', $biometricIds)
            ->where('biometric_id', 511) // Example biometric ID for testing
=======
        $profiles = DB::table('employee_profiles')
             ->whereIn('biometric_id', $biometricIds)
           //->where('biometric_id', 565) // 494
>>>>>>> bf451cabc8c3e8c649e431451e05a39d6a03c40d
            ->get();
        $data = [];

        $whole_month = $request->whole_month;
        $first_half = $request->first_half;
        $second_half = $request->second_half;
        $init = 1;
        if ($first_half) {
            $days_In_Month = 15;
        } else if ($second_half) {
            $init = 16;
        }

        foreach ($profiles as $row) {
            $Employee = EmployeeProfile::find($row->id);
            $biometric_id = $row->biometric_id;
            $dtr = DB::table('daily_time_records')
                ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
                ->where(function ($query) use ($biometricId, $monthOf, $yearOf) {
                    $query->where('biometric_id', $biometricId)
                        ->whereMonth(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $monthOf)
                        ->whereYear(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $yearOf);
                })
                ->orWhere(function ($query) use ($biometricId, $monthOf, $yearOf) {
                    $query->where('biometric_id', $biometricId)
                        ->whereMonth(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $monthOf)
                        ->whereYear(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $yearOf);
                })
                ->get();
            $empschedule = [];

            if (count($dtr) == 0) {
                $dvc_logs =  DeviceLogs::where('biometric_id', $biometric_id)
                    ->where('active', 1);
                $this->DeviceLog->GenerateEntry($dvc_logs->get(), null, true);
            } else if (count($this->DeviceLog->CheckDTR($biometric_id))) {

                $this->DeviceLog->GenerateEntry($this->DeviceLog->CheckDTR($biometric_id), null, true);
            }



            foreach ($dtr as $val) {
                $bioEntry = [
                    'first_entry' => $record->first_in ?? $record->second_in,
                    'date_time' => $record->first_in ?? $record->second_in
                ];
             $Schedule = $this->helper->CurrentSchedule($biometric_id, $bioEntry, false);
                $DaySchedule = $Schedule['daySchedule'];
                $empschedule[] = $DaySchedule;


<<<<<<< HEAD
                $dtrdate =  $val->dtr_date;
                $dvc_logs =  DeviceLogs::where('biometric_id', $biometric_id)
                    ->where('dtr_date', $dtrdate)
                    ->where('active', 1);
=======
                $dtrdate = $val->dtr_date;
                $dvc_logs =  DeviceLogs::where('biometric_id',$biometric_id)
                ->where('dtr_date', $dtrdate)
                ->where('active',1);
>>>>>>> bf451cabc8c3e8c649e431451e05a39d6a03c40d
                //xxxxxxxxxxxxxxxxxxxxxx
                if ($dvc_logs->exists()) {
                    $checkdtr = DailyTimeRecords::whereDate('dtr_date', $dtrdate)->where('biometric_id', $biometric_id);
                    if ($checkdtr->exists()) {

<<<<<<< HEAD
                        $this->DeviceLog->RegenerateEntry($dvc_logs->get(), $biometric_id, false);
                    } else {
                        $this->DeviceLog->GenerateEntry($dvc_logs->get(), $dtrdate, false);
=======
                       $this->DeviceLog->RegenerateEntry($dvc_logs->get(),$biometric_id,false);
                    }else {
                       $this->DeviceLog->GenerateEntry($dvc_logs->get(),$dtrdate,true);
>>>>>>> bf451cabc8c3e8c649e431451e05a39d6a03c40d
                    }
                }


                // Save total working hours if schedule is valid
                if (count($daySchedule) >= 1) {
                    $validate = [
                        (object)[
                            'id' => $record->id,
                            'first_in' => $record->first_in,
                            'first_out' => $record->first_out,
                            'second_in' => $record->second_in,
                            'second_out' => $record->second_out
                        ],
                    ];
                    $this->helper->saveTotalWorkingHours(
                        $validate,
                        $record,
                        $record,
                        $daySchedule,
                        true
                    );
                }
            }







            $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();
            if ($employee->leaveApplications) {
                $leaveApplications = $employee->leaveApplications->filter(function ($application) {
                    return $application['status'] == "received";
                });

                foreach ($leaveApplications as $leave) {
                    $leaveData[] = [
                        'country' => $leave['country'],
                        'city' => $leave['city'],
                        'from' => $leave['date_from'],
                        'to' => $leave['date_to'],
                        'leaveType' => LeaveType::find($leave['leave_type_id'])->name ?? "",
                        'withoutPay' => $leave['without_pay'],
                        'datesCovered' => $this->helper->getDateIntervals($leave['date_from'], $leave['date_to'])
                    ];
                }
            }

            // Process official business applications
            $officialBusinessData = [];
            if ($employee->officialBusinessApplications) {
                $officialBusinessApplications = $employee->officialBusinessApplications->filter(function ($application) {
                    return $application['status'] == "approved";
                })->toArray();

                foreach ($officialBusinessApplications as $officialBusiness) {
                    $officialBusinessData[] = [
                        'purpose' => $officialBusiness['purpose'],
                        'dateFrom' => $officialBusiness['date_from'],
                        'dateTo' => $officialBusiness['date_to'],
                        'datesCovered' => $this->helper->getDateIntervals($officialBusiness['date_from'], $officialBusiness['date_to']),
                    ];
                }
            }

            // Process official time applications
            $officialTimeData = [];
            if ($employee->officialTimeApplications) {
                $officialTimeApplications = $employee->officialTimeApplications->filter(function ($application) {
                    return $application['status'] == "approved";
                });

                foreach ($officialTimeApplications as $officialTime) {
                    $officialTimeData[] = [
                        'dateFrom' => $officialTime['date_from'],
                        'dateTo' => $officialTime['date_to'],
                        'purpose' => $officialTime['purpose'],
                        'datesCovered' => $this->helper->getDateIntervals($officialTime['date_from'], $officialTime['date_to'])
                    ];
                }
            }

            // Process CTO applications
            $ctoData = [];
            if ($employee->ctoApplications) {
                $ctoApplications = $employee->ctoApplications->filter(function ($application) {
                    return $application['status'] == "approved";
                });

                foreach ($ctoApplications as $cto) {
                    $ctoData[] = [
                        'date' => date('Y-m-d', strtotime($cto['date'])),
                        'purpose' => $cto['purpose'],
                        'remarks' => $cto['remarks'],
                    ];
                }
            }

            // Filter schedules for the month
            if (count($employeeSchedules) >= 1) {
                $employeeSchedules = array_map(function ($schedule) {
                    return (int)date('d', strtotime($schedule['scheduleDate']));
                }, $this->helper->Allschedule($biometricId, $monthOf, $yearOf, null, null, null, null)['schedule']);
            }

            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $monthOf, $yearOf);

            // Initialize variables for attendance data
            $attendanceData = [];
            $leaveWithoutPay = [];
            $leaveWithPay = [];
            $officialBusinessOrTime = [];
            $absences = [];
            $dayOff = [];
            $totalMonthWorkingMinutes = 0;
            $totalMonthOvertime = 0;
            $totalMonthUndertime = 0;
            $invalidEntries = [];

            // Determine present and absent days
            $presentDays = array_map(function ($day) use ($employeeSchedules) {
                if (in_array($day->day, $employeeSchedules)) {
                    return  $day->day;
                }
            }, $dailyTimeRecords->toArray());

            $absentDays = array_values(array_filter(array_map(function ($day) use ($presentDays) {
                if (!in_array($day, $presentDays) && $day != null) {
                    return  $day;
                }
            }, $empschedule)));

            for ($i = $init; $i <= $days_In_Month; $i++) {



                // Filter leave dates
                $filteredLeaveDates = [];
                foreach ($leaveData as $leave) {
                    foreach ($leave['datesCovered'] as $date) {
                        $filteredLeaveDates[] = [
                            'dateReg' => strtotime($date),
                            'status' => $leave['withoutPay']
                        ];
                    }
                }

                $leaveApplication = array_filter($filteredLeaveDates, function ($timestamp) use ($yearOf, $monthOf, $day) {
                    $dateToCompare = date('Y-m-d', $timestamp['dateReg']);
                    $dateToMatch = date('Y-m-d', strtotime("$yearOf-$monthOf-$day"));
                    return $dateToCompare === $dateToMatch;
                });

                $leaveCount = count($leaveApplication);

                // Filter official business dates
                $filteredOfficialBusinessDates = [];
                foreach ($officialBusinessData as $business) {
                    foreach ($business['datesCovered'] as $date) {
                        $filteredOfficialBusinessDates[] = strtotime($date);
                    }
                }

                $officialBusinessApplication = array_filter($filteredOfficialBusinessDates, function ($timestamp) use ($yearOf, $monthOf, $day) {
                    $dateToCompare = date('Y-m-d', $timestamp);
                    $dateToMatch = date('Y-m-d', strtotime("$yearOf-$monthOf-$day"));
                    return $dateToCompare === $dateToMatch;
                });

                $officialBusinessCount = count($officialBusinessApplication);

                // Filter official time dates
                $filteredOfficialTimeDates = [];
                foreach ($officialTimeData as $time) {
                    foreach ($time['datesCovered'] as $date) {
                        $filteredOfficialTimeDates[] = strtotime($date);
                    }
                }

                $officialTimeApplication = array_filter($filteredOfficialTimeDates, function ($timestamp) use ($yearOf, $monthOf, $day) {
                    $dateToCompare = date('Y-m-d', $timestamp);
                    $dateToMatch = date('Y-m-d', strtotime("$yearOf-$monthOf-$day"));
                    return $dateToCompare === $dateToMatch;
                });

                $officialTimeCount = count($officialTimeApplication);

                // Filter CTO dates
                $ctoApplication = array_filter($ctoData, function ($row) use ($yearOf, $monthOf, $day) {
                    $dateToCompare = date('Y-m-d', strtotime($row['date']));
                    $dateToMatch = date('Y-m-d', strtotime("$yearOf-$monthOf-$day"));
                    return $dateToCompare === $dateToMatch;
                });

                $ctoCount = count($ctoApplication);

                // Process leave without pay
                if ($leaveCount) {
                    if (array_values($leaveApplication)[0]['status']) {
                        $leaveWithoutPay[] = [
                            'dateRecord' => date('Y-m-d', strtotime("$yearOf-$monthOf-$day")),
                        ];
                    } else {
                        $leaveWithPay[] = [
                            'dateRecord' => date('Y-m-d', strtotime("$yearOf-$monthOf-$day")),
                        ];
                        $totalMonthWorkingMinutes += 480;
                    }
                }
                // Process official business or official time
                else if ($officialBusinessCount ||  $officialTimeCount) {
                    $officialBusinessOrTime[] = [
                        'dateRecord' => date('Y-m-d', strtotime("$yearOf-$monthOf-$day")),
                    ];
                    $totalMonthWorkingMinutes += 480;
                }
                // Process attendance
                else if (in_array($day, $presentDays) && in_array($day, $employeeSchedules)) {
                    $recordDTR = array_values(array_filter($dailyTimeRecords->toArray(), function ($d) use ($yearOf, $monthOf, $day) {
                        return $d->dtr_date == date('Y-m-d', strtotime("$yearOf-$monthOf-$day"));
                    }));

                    if (
                        ($recordDTR[0]->first_in && $recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out) || // all entries
                        (!$recordDTR[0]->first_in && !$recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out) || // 3-4
                        ($recordDTR[0]->first_in && $recordDTR[0]->first_out && !$recordDTR[0]->second_in && !$recordDTR[0]->second_out) || // 1-2
                        ($recordDTR[0]->first_in && $recordDTR[0]->first_out && $recordDTR[0]->second_in && !$recordDTR[0]->second_out) // 1-2-3
                    ) {
                        $attendanceData[] = $this->Attendance($yearOf, $monthOf, $day, $recordDTR);
                        $totalMonthWorkingMinutes += $recordDTR[0]->total_working_minutes;
                        $totalMonthOvertime += $recordDTR[0]->overtime_minutes;
                        $totalMonthUndertime += $recordDTR[0]->undertime_minutes;
                    } else {
                        $invalidEntries[] =  $this->Attendance($yearOf, $monthOf, $day, $recordDTR);
                    }
                }
                // Process absences
                else if (
                    in_array($day, $absentDays) &&
                    in_array($day, $employeeSchedules) &&
                    strtotime(date('Y-m-d', strtotime("$yearOf-$monthOf-$day"))) < strtotime(date('Y-m-d'))
                ) {
                    $absences[] = [
                        'dateRecord' => date('Y-m-d', strtotime("$yearOf-$monthOf-$day")),
                    ];
                }
                // Process day off
                else {
                    $dayOff[] = [
                        'dateRecord' => date('Y-m-d', strtotime("$yearOf-$monthOf-$day")),
                    ];
                }
            }

            // Calculate attendance statistics
            $presentCount = count(array_filter($attendanceData, function ($d) {
                return $d['total_working_minutes'] !== 0;
            }));
            $numberOfAbsences = count($absences) - count($leaveWithoutPay);
            $employeeSchedules = $this->helper->Allschedule($biometricId, $monthOf, $yearOf, null, null, null, null)['schedule'];

            $scheduledDays = array_map(function ($schedule) {
                return (int)date('d', strtotime($schedule['scheduleDate']));
            }, $employeeSchedules);

            $filteredSchedules = array_values(array_filter($scheduledDays, function ($value) use ($startDay, $daysInMonth) {
                return $value >= $startDay && $value <= $daysInMonth;
            }));

            $employeeAssignedAreas = $employee->assignedAreas->first();
            $salaryGrade = $employeeAssignedAreas->salary_grade_id;
            $salaryStep = $employeeAssignedAreas->salary_grade_step;

            $basicSalary = $this->computed->BasicSalary($salaryGrade, $salaryStep, count($filtered_scheds));
            $GrossSalary = $this->computed->GrossSalary($presentCount, $basicSalary['GrandTotal']);
            $Rates = $this->computed->Rates($basicSalary['GrandTotal']);
            $undertimeRate = $this->computed->UndertimeRates($total_Month_Undertime, $Rates);
            $absentRate = $this->computed->AbsentRates($Number_Absences, $Rates);
            $NetSalary = $this->computed->NetSalaryFromTimeDeduction($Rates, $presentCount, $undertimeRate, $absentRate, $basicSalary['Total']);
            $data[] = [
                'Biometric_id' => $biometric_id,
                'EmployeeNo' => $Employee->employee_id,
                'Name' => $Employee->personalInformation->name(),
                'Payroll' => $init . " - " . $days_In_Month,
                'From' => $init,
                'To' => $days_In_Month,
                'Month' => $month_of,
                'Year' => $year_of,
                'Is_out' => $this->computed->OutofPayroll($NetSalary, $init, $days_In_Month),
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
                'GrossSalary' => $basicSalary['Total'],
                'TimeDeductions' => [
                    'AbsentRate' => $absentRate,
                    'UndertimeRate' => $undertimeRate,
                ],
                'NetSalary' => $NetSalary


                // 'Attendance'=>$attd,
                // 'Invalid'=>$invalidEntry,
                // 'absences'=>$absences,
                // 'Leavewopay'=>$lwop,
                //  'Leavewpay'=>$lwp,
                //  'Absences'=>$absences,
                //  'Dayoff'=>$dayoff
            ];
        }

        return $data;
    }
}
