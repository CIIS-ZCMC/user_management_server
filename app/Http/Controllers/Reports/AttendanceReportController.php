<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Support\Facades\DB;
use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Helpers\ReportHelpers;
use App\Http\Resources\AttendanceReportResource;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\AssignArea;
use App\Models\DailyTimeRecords;
use App\Models\Division;
use App\Models\Department;
use App\Models\DeviceLogs;
use App\Models\EmployeeProfile;
use App\Models\EmployeeSchedule;
use App\Models\EmploymentType;
use App\Models\LeaveType;
use App\Models\Section;
use App\Models\Unit;
use PhpParser\Node\Expr\Assign;;

use App\Http\Controllers\DTR\DeviceLogsController;
use App\Http\Controllers\DTR\DTRcontroller;
use App\Http\Controllers\PayrollHooks\ComputationController;
use App\Models\Devices;
use SebastianBergmann\CodeCoverage\Report\Xml\Report;

/**
 * Class AttendanceReportController
 * @package App\Http\Controllers\Reports
 * 
 * Controller for handling attendance reports.
 */
class AttendanceReportController extends Controller
{
    private $CONTROLLER_NAME = "Attendance Reports";
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


    public function Attendance($year_of, $month_of, $i, $record_dtr)
    {
        return [
            'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
            'firstin' => $record_dtr[0]['first_in'],
            'firstout' => $record_dtr[0]['first_in'],
            'secondin' => $record_dtr[0]['second_in'],
            'secondout' => $record_dtr[0]['second_out'],
            'total_working_minutes' => $record_dtr[0]['total_working_minutes'],
            'overtime_minutes' => $record_dtr[0]['overtime_minutes'],
            'undertime_minutes' => $record_dtr[0]['undertime_minutes'],
            'overall_minutes_rendered' => $record_dtr[0]['overall_minutes_rendered'],
            'total_minutes_reg' => $record_dtr[0]['total_minutes_reg']
        ];
    }

    public function GenerateDataReport($first_half, $second_half, $month_of, $year_of, $profiles)
    {
        ini_set('max_execution_time', 7200);

        $biometricIds = DailyTimeRecords::whereYear('dtr_date', $year_of)
            ->whereMonth('dtr_date', $month_of)
            ->pluck('biometric_id');
        $profiles = EmployeeProfile::whereIn('biometric_id', [511])
            //->where('biometric_id', 565) // 494
            ->get();
        $data = [];


        // $whole_month = $request->whole_month;
        $first_half = $first_half;
        $second_half = $second_half;

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

            if (count($dtr) == 0) {
                $dvc_logs =  DeviceLogs::where('biometric_id', $biometric_id)
                    ->where('active', 1);
                $this->DeviceLog->GenerateEntry($dvc_logs->get(), null, true);
            } else if (count($this->DeviceLog->CheckDTR($biometric_id))) {

                $this->DeviceLog->GenerateEntry($this->DeviceLog->CheckDTR($biometric_id), null, true);
            }



            foreach ($dtr as $val) {
                $bioEntry = [
                    'first_entry' => $val->first_in ?? $val->second_in,
                    'date_time' => $val->first_in ?? $val->second_in
                ];
                $Schedule = ReportHelpers::CurrentSchedule($biometric_id, $bioEntry, false);
                $DaySchedule = $Schedule['daySchedule'];
                $empschedule[] = $DaySchedule;


                $dtrdate = $val->dtr_date;
                $dvc_logs =  DeviceLogs::where('biometric_id', $biometric_id)
                    ->where('dtr_date', $dtrdate)
                    ->where('active', 1);
                //xxxxxxxxxxxxxxxxxxxxxx
                if ($dvc_logs->exists()) {
                    $checkdtr = DailyTimeRecords::whereDate('dtr_date', $dtrdate)->where('biometric_id', $biometric_id);
                    if ($checkdtr->exists()) {

                        $this->DeviceLog->RegenerateEntry($dvc_logs->get(), $biometric_id, false);
                    } else {
                        $this->DeviceLog->GenerateEntry($dvc_logs->get(), $dtrdate, true);
                    }
                }


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
                    ReportHelpers::saveTotalWorkingHours(
                        $validate,
                        $val,
                        $val,
                        $DaySchedule,
                        true
                    );
                }
            }


            $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();


            if ($employee->leaveApplications) {
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
                        'dates_covered' => ReportHelpers::getDateIntervals($rows['date_from'], $rows['date_to'])
                    ];
                }
            }



            //Official business
            if ($employee->officialBusinessApplications) {
                $officialBusiness = array_values($employee->officialBusinessApplications->filter(function ($row) {
                    return $row['status'] == "approved";
                })->toarray());
                $obData = [];
                foreach ($officialBusiness as $rows) {
                    $obData[] = [
                        'purpose' => $rows['purpose'],
                        'date_from' => $rows['date_from'],
                        'date_to' => $rows['date_to'],
                        'dates_covered' => ReportHelpers::getDateIntervals($rows['date_from'], $rows['date_to']),
                    ];
                }
            }

            if ($employee->officialTimeApplications) {
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
                        'dates_covered' => ReportHelpers::getDateIntervals($rows['date_from'], $rows['date_to'])
                    ];
                }
            }

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
            if (count($empschedule) >= 1) {
                $empschedule = array_map(function ($sc) {
                    // return isset($sc['scheduleDate']) && (int)date('d', strtotime($sc['scheduleDate']));
                    return (int)date('d', strtotime($sc['scheduleDate']));
                }, ReportHelpers::Allschedule($biometric_id, $month_of, $year_of, null, null, null, null)['schedule']);
            }
            $days_In_Month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);
            // echo "Name :" . $Employee?->personalInformation->name() . "\n Biometric_id :" . $Employee->biometric_id . "\n" ?? "\n" . "\n";

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


            // Ensure you handle object properties correctly
            $AbsentDays = array_values(array_filter(array_map(function ($d) use ($presentDays) {
                if (!in_array($d, $presentDays) && $d !== null) {
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

                    if (
                        isset($recordDTR[0]) && // Ensure $recordDTR[0] exists
                        (
                            ($recordDTR[0]->first_in && $recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out) || // all entry
                            (!$recordDTR[0]->first_in && !$recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out) || //3-4
                            ($recordDTR[0]->first_in && $recordDTR[0]->first_out && !$recordDTR[0]->second_in && !$recordDTR[0]->second_out) || // 1-2
                            ($recordDTR[0]->first_in && $recordDTR[0]->first_out && $recordDTR[0]->second_in && !$recordDTR[0]->second_out) // 1-2-3
                        )
                    ) {
                        $attd[] = $this->Attendance($year_of, $month_of, $i, $recordDTR);
                        $total_Month_WorkingMinutes += $recordDTR[0]->total_working_minutes;
                        $total_Month_Overtime += $recordDTR[0]->overtime_minutes;
                        $total_Month_Undertime += $recordDTR[0]->undertime_minutes;
                    } else {
                        $invalidEntry[] = $this->Attendance($year_of, $month_of, $i, $recordDTR);
                    }
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




            $presentCount = count(array_filter($attd, function ($d) {
                return $d['total_working_minutes'] !== 0;
            }));
            $Number_Absences = count($absences) - count($lwop);
            $schedule_ = ReportHelpers::Allschedule($biometric_id, $month_of, $year_of, null, null, null, null)['schedule'];

            $scheds = array_map(function ($d) {
                return (int)date('d', strtotime($d['scheduleDate']));
            }, $schedule_);

            $filtered_scheds = array_values(array_filter($scheds, function ($value) use ($init, $days_In_Month) {
                return $value >= $init && $value <= $days_In_Month;
            }));

            $employeeAssignedAreas =  $employee->assignedAreas->first();
            $salaryGrade = $employeeAssignedAreas->salary_grade_id;
            $salaryStep  = $employeeAssignedAreas->salary_grade_step;

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

    // private function GenerateReportYearMonthOf($first_half, $second_half, $month_of, $year_of, $profiles)
    // {
    //     $arr_data = [];
    //     $init_val = 1;
    //     $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);

    //     if ($first_half) {
    //         $days_in_month = 15;
    //     } else if ($second_half) {
    //         $init_val = 16;
    //     }

    //     foreach ($profiles as $profile) {
    //         if (!$profile->employeeProfile) {
    //             continue; // Skip this profile if employeeProfile is null
    //         }

    //         $employee_biometric_id = $profile->employeeProfile->biometric_id;

    //         $dtr = DailyTimeRecords::select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
    //             ->where(function ($query) use ($employee_biometric_id, $month_of, $year_of) {
    //                 $query->where('biometric_id',  $employee_biometric_id)
    //                     ->whereMonth(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
    //                     ->whereYear(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
    //             })
    //             ->orWhere(function ($query) use ($employee_biometric_id, $month_of, $year_of) {
    //                 $query->where('biometric_id',  $employee_biometric_id)
    //                     ->whereMonth(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
    //                     ->whereYear(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
    //             })
    //             ->get();


    //         $employee_schedules = [];
    //         $total_days_with_tardiness = 0;

    //         if (count($dtr) === 0) {
    //             $device_logs = DeviceLogs::where('biometric_id', $employee_biometric_id)->where('active', 1);
    //         } else if (count($this->DeviceLog->CheckDTR($employee_biometric_id))) {
    //             $this->DEVICE_LOG->GenerateEntry($this->DEVICE_LOG->CheckDTR($employee_biometric_id), null, true);
    //         }

    //         foreach ($dtr as $d) {
    //             $bio_entry = [
    //                 'first_entry' => $d->first_in ?? $d->second_in,
    //                 'date_time' => $d->first_in ?? $d->second_in
    //             ];

    //             $first_in = $d->first_in;
    //             $second_in = $d->second_in;
    //             $record_dtr_date = Carbon::parse($d->dtr_date);

    //             $current_schedule = ReportHelpers::CurrentSchedule($employee_biometric_id, $bio_entry, false);
    //             $day_schedule = $current_schedule['daySchedule'];
    //             $employee_schedules[] = $day_schedule;

    //             $dtr_date = $d->dtr_date;
    //             $device_logs = DeviceLogs::where('biometric_id', $employee_biometric_id)
    //                 ->where('dtr_date', $dtr_date)
    //                 ->where('active', 1);


    //             if ($device_logs->exists()) {
    //                 $check_dtr = DailyTimeRecords::whereDate('dtr_date', $dtr_date)->where('biometric_id', $employee_biometric_id);
    //                 if ($check_dtr->exists()) {
    //                     $this->DEVICE_LOG->RegenerateEntry($device_logs->get(), $employee_biometric_id, false);
    //                 } else {
    //                     $this->DEVICE_LOG->RegenerateEntry($device_logs->get(), $dtr_date, true);
    //                 }
    //             }

    //             if (count($day_schedule) >= 1) {
    //                 $validate = [
    //                     (object)[
    //                         'id' => $d->id,
    //                         'first_in' => $d->first_in,
    //                         'first_out' => $d->first_out,
    //                         'second_in' => $d->second_in,
    //                         'second_out' => $d->second_out
    //                     ]
    //                 ];

    //                 $startOfDay8 = $record_dtr_date->copy()->startOfDay()->addHours(8);
    //                 $startOfDay13 = $record_dtr_date->copy()->startOfDay()->addHours(13);

    //                 if ($first_in && Carbon::parse($first_in)->gt($startOfDay8)) {
    //                     $total_days_with_tardiness++;
    //                 }
    //                 if ($second_in && Carbon::parse($second_in)->gt($startOfDay13)) {
    //                     $total_days_with_tardiness++;
    //                 }

    //                 ReportHelpers::saveTotalWorkingHours(
    //                     $validate,
    //                     $d,
    //                     $d,
    //                     $day_schedule,
    //                     true
    //                 );
    //             }
    //         }

    //         // FOR LEAVE APPLICATIONS
    //         if ($profile->employeeProfile->leaveApplications) {
    //             $leave_applications = $profile->employeeProfile->leaveApplications->filter(function ($row) {
    //                 return $row['status'] === "received";
    //             });

    //             $leave_data = [];
    //             foreach ($leave_applications as $leave_application) {
    //                 $leave_data[] = [
    //                     'country' => $leave_application['country'],
    //                     'city' => $leave_application['city'],
    //                     'from' => $leave_application['date_from'],
    //                     'to' => $leave_application['date_to'],
    //                     'leavetype' => LeaveType::find($leave_application['leave_type_id'])->name ?? "",
    //                     'without_pay' => $leave_application['without_pay'],
    //                     'date_covered' => ReportHelpers::getDateIntervals($leave_application['date_from'], $leave_application['date_to'])
    //                 ];
    //             }
    //         }

    //         // FOR OFFICIAL BUSINESS
    //         if ($profile->employeeProfile->officialBusinessApplications) {
    //             $official_business = array_values($profile->employeeProfile->officialBusinessApplications->filter(function ($row) {
    //                 return $row['status'] === "approved";
    //             })->toArray());

    //             $official_business_data = [];

    //             foreach ($official_business as $ob) {
    //                 $official_business_data[] = [
    //                     'purpose' => $ob['purpose'],
    //                     'date_from' => $ob['date_from'],
    //                     'date_to' => $ob['date_to'],
    //                     'date_covered' => ReportHelpers::getDateIntervals($ob['date_from'], $ob['date_to'])
    //                 ];
    //             }
    //         }

    //         // OFFIICAL TIME
    //         if ($profile->employeeProfile->officialTimeApplications) {
    //             $official_time = $profile->employeeProfile->officialTimeApplications->filter(function ($row) {
    //                 return $row['status'] === "approved";
    //             });

    //             $official_time_data  = [];

    //             foreach ($official_time as $ot) {
    //                 $official_time_data[] = [
    //                     'date_from' => $ot['date_from'],
    //                     'date_to' => $ot['date_to'],
    //                     'purpose' => $ot['purpose'],
    //                     'date_covered' => ReportHelpers::getDateIntervals($ot['date_from'], $ot['date_to'])
    //                 ];
    //             }
    //         }

    //         // CTO APPLICATIONS
    //         if ($profile->employeeProfile->ctoApplications) {
    //             $cto_applications = $profile->employeeProfile->ctoApplications->filter(function ($row) {
    //                 return $row['status'] === "approved";
    //             });

    //             $cto_data = [];

    //             foreach ($cto_applications as $cto) {
    //                 $cto_data[] = [
    //                     'date' => date('Y-m-d', strtotime($cto['date'])),
    //                     'purpose' => $cto['purpose'],
    //                     'remarks' => $cto['remarks'],
    //                 ];
    //             }
    //         }

    //         if (count($employee_schedules) >= 1) {
    //             $employee_schedules = array_map(function ($sched) {
    //                 return (int) date('d', strtotime($sched['scheduleDate']));
    //             }, ReportHelpers::Allschedule($employee_biometric_id, $month_of, $year_of, null, null, null, null)['schedule']);
    //         }

    //         // array initializations
    //         $attendance = [];
    //         $leave_without_pay = [];
    //         $leave_with_pay = [];
    //         $obot = [];
    //         $absences = [];
    //         $day_off = [];
    //         $total_month_working_minutes = 0;
    //         $total_month_overtime = 0;
    //         $total_month_undertime = 0;
    //         $total_hours_missed = 0;
    //         $invalid_entry = [];

    //         // present days
    //         $present_days = array_map(function ($d) use ($employee_schedules) {
    //             if (in_array($d['day'], $employee_schedules)) {
    //                 return $d['day'];
    //             }
    //         }, $dtr->toArray());

    //         // absent days
    //         $absent_days = array_values(array_filter(array_map(function ($d) use ($present_days) {
    //             if (!in_array($d, $present_days) && $d !== null) {
    //                 return $d;
    //             }
    //         }, $employee_schedules)));


    //         for ($i = $init_val; $i <= $days_in_month; $i++) {
    //             $filtered_leave_dates = [];
    //             $leave_status = [];

    //             foreach ($leave_data as $leave) {
    //                 foreach ($leave['date_covered'] as $date) {
    //                     $filtered_leave_dates[] = [
    //                         'dateReg' => strtotime($date),
    //                         'status' => $leave['without_pay']
    //                     ];
    //                 }
    //             }

    //             $leave_application = array_filter($filtered_leave_dates, function ($ts) use ($year_of, $month_of, $i) {
    //                 $date_to_compare = date('Y-m-d', (int)$ts);

    //                 $date_to_match = date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' .  $i));
    //                 return $date_to_compare === $date_to_match;
    //             });

    //             $leave_count = count($leave_application);

    //             // check official business dates
    //             $filtered_OB_dates = [];
    //             foreach ($official_business_data as $ob) {
    //                 foreach ($ob['date_covered'] as  $date) {
    //                     $filtered_OB_dates[] = strtotime($date);
    //                 }
    //             }

    //             $OB_application = array_filter($filtered_OB_dates, function ($ts) use ($year_of, $month_of, $i) {
    //                 $date_to_compare = date('Y-m-d', (int)$ts);

    //                 $date_to_match = date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' .  $i));
    //                 return $date_to_compare === $date_to_match;
    //             });
    //             $official_business_count = count($OB_application);

    //             // check official time dates
    //             $filtered_OT_dates = [];
    //             foreach ($official_business_data as $ob) {
    //                 foreach ($ob['date_covered'] as  $date) {
    //                     $filtered_OB_dates[] = strtotime($date);
    //                 }
    //             }
    //             $OT_application = array_filter($filtered_OT_dates, function ($ts) use ($year_of, $month_of, $i) {
    //                 $date_to_compare = date('Y-m-d', (int)$ts);

    //                 $date_to_match = date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' .  $i));
    //                 return $date_to_compare === $date_to_match;
    //             });
    //             $official_time_count = count($OT_application);

    //             // check CTO applications dates

    //             $CTO_application = array_filter($cto_data, function ($ts) use ($year_of, $month_of, $i) {
    //                 $date_to_compare = date('Y-m-d', strtotime($ts['date']));
    //                 $date_to_match = date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' .  $i));
    //                 return $date_to_compare === $date_to_match;
    //             });
    //             $cto_count = count($CTO_application);

    //             if ($leave_count) {
    //                 if (array_values($leave_application)[0]['status']) {
    //                     $leave_without_pay[] = [
    //                         'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
    //                     ];
    //                 } else {
    //                     $leave_with_pay[] = [
    //                         'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
    //                     ];
    //                     $total_month_working_minutes += 480;
    //                 }
    //             } else if ($official_business_count || $official_time_count) {
    //                 $obot[] = [
    //                     'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),

    //                 ];
    //                 $total_month_working_minutes += 480;
    //             } else {
    //                 if (in_array($i, $present_days) && in_array($i, $employee_schedules)) {
    //                     $record_dtr = array_values(array_filter($dtr->toArray(), function ($d) use ($year_of, $month_of, $i) {
    //                         return $d['dtr_date'] === date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i));
    //                     }));

    //                     if (
    //                         ($record_dtr[0]['first_in'] && $record_dtr[0]['first_out'] && $record_dtr[0]['second_in'] && $record_dtr[0]['second_out']) || // all entry
    //                         (!$record_dtr[0]['first_in'] && !$record_dtr[0]['first_out'] && $record_dtr[0]['second_in'] && $record_dtr[0]['second_out'])  || //3-4
    //                         ($record_dtr[0]['first_in'] && $record_dtr[0]['first_out'] && !$record_dtr[0]['second_in'] && !$record_dtr[0]['second_out']) || // 1-2
    //                         ($record_dtr[0]['first_in'] && $record_dtr[0]['first_out'] && $record_dtr[0]['second_in'] && !$record_dtr[0]['second_out']) // 1-2-3
    //                     ) {

    //                         $attendance[] = $this->Attendance($year_of, $month_of, $i, $record_dtr);
    //                         $total_month_working_minutes += $record_dtr[0]['total_working_minutes'];
    //                         $total_month_overtime += $record_dtr[0]['overtime_minutes'];
    //                         $total_month_undertime += $record_dtr[0]['undertime_minutes'];
    //                         $total_hours_missed += ReportHelpers::ToHours((480 - $record_dtr[0]['total_working_minutes']));
    //                     } else {
    //                         $invalid_entry[] =  $this->Attendance($year_of, $month_of, $i, $record_dtr);
    //                     }
    //                 } else if (
    //                     in_array($i, $absent_days) &&
    //                     in_array($i, $employee_schedules) &&
    //                     strtotime(date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i))) <  strtotime(date('Y-m-d'))
    //                 ) {
    //                     $absences[] = [
    //                         'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
    //                     ];
    //                 } else {
    //                     $day_off[] = [
    //                         'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
    //                     ];
    //                 }
    //             }
    //         }

    //         $present_count = count(array_filter($attendance, function ($d) {
    //             return $d['total_working_minutes'] !== 0;
    //         }));

    //         $number_of_absences = count($absences) - count($leave_without_pay);
    //         $schedule_ = ReportHelpers::Allschedule($employee_biometric_id, $month_of, $year_of, null, null, null, null)['schedule'];

    //         $scheds = array_map(function ($d) {
    //             return (int) date('d', strtotime($d['scheduleDate']));
    //         }, $schedule_);

    //         $filtered_scheds = array_values(array_filter($scheds, function ($value) use ($init_val, $days_in_month) {
    //             return $value >= $init_val && $value <= $days_in_month;
    //         }));

    //         // $employee_assigned_areas = $employee->assigndAreas->first();
    //         // $salary_grade = $employee_assigned_areas->salary_grade_id;
    //         // $salary_step = $employee_assigned_areas->salary_grade_step;


    //         // $employeeAssignedAreas =  $employee->assignedAreas->first();
    //         // $salaryGrade = $employeeAssignedAreas->salary_grade_id;
    //         // $salaryStep  = $employeeAssignedAreas->salary_grade_step;

    //         // $basicSalary = $this->computed->BasicSalary($salaryGrade, $salaryStep, count($filtered_scheds));
    //         // $GrossSalary = $this->computed->GrossSalary($presentCount, $basicSalary['GrandTotal']);
    //         // $Rates = $this->computed->Rates($basicSalary['GrandTotal']);
    //         // $undertimeRate = $this->computed->UndertimeRates($total_Month_Undertime, $Rates);
    //         // $absentRate = $this->computed->AbsentRates($Number_Absences, $Rates);
    //         // $NetSalary = $this->computed->NetSalaryFromTimeDeduction($Rates, $presentCount, $undertimeRate, $absentRate, $basicSalary['Total']);

    //         $arr_data[] = [
    //             'id' => $profile->id,
    //             'employee_biometric_id' => $profile->employeeProfile->biometric_id,
    //             'employee_id' => $profile->employeeProfile->employee_id,
    //             'employee_name' => $profile->employeeProfile->personalInformation->employeeName(),
    //             'employment_type' => $profile->employeeProfile->employmentType->name,
    //             'employee_designation_name' => $profile->employeeProfile->findDesignation()['name'] ?? '',
    //             'employee_designation_code' => $profile->employeeProfile->findDesignation()['code'] ?? '',
    //             'sector' => $profile->employeeProfile->assignedArea->findDetails()['sector'] ?? '',
    //             'area_name' => $profile->employeeProfile->assignedArea->findDetails()['details']['name'] ?? '',
    //             'area_code' => $profile->employeeProfile->assignedArea->findDetails()['details']['code'] ?? '',
    //             'from' => $init_val,
    //             'to' => $days_in_month,
    //             'month' => $month_of,
    //             'year' => $year_of,
    //             'total_working_minutes' => $total_month_working_minutes,
    //             'total_working_hours' => ReportHelpers::ToHours($total_month_working_minutes),
    //             'total_overtime_minutes' => $total_month_overtime,
    //             'total_undertime_minutes' => $total_month_undertime,
    //             'total_hours_missed' => $total_hours_missed,
    //             'total_days_with_tardiness' => $total_days_with_tardiness,
    //             'total_of_absent_days' => $number_of_absences,
    //             'total_of_present_days' => $present_count,
    //             'total_of_leave_without_pay' => count($leave_without_pay),
    //             'total_of_leave_with_pay' => count($leave_with_pay),
    //             'total_invalid_entry' => count($invalid_entry),
    //             'total_of_day_off' => count($day_off),
    //             'schedule' => count($filtered_scheds),
    //         ];
    //     }

    //     return $arr_data;
    // }

    // private function GenerateReportDateRange($start_date, $end_date, $profiles)
    // {
    //     $arr_data = [];
    //     $init_val = 1;
    //     $date_range = [$start_date, $end_date];



    //     // Determine the range of days to process
    //     $start_day = Carbon::parse($start_date)->day;
    //     $end_day = Carbon::parse($end_date)->day;
    //     $start_year = Carbon::parse($start_date)->year;
    //     $end_year = Carbon::parse($end_date)->year;
    //     $start_month = Carbon::parse($start_date)->month;
    //     $end_month = Carbon::parse($end_date)->month;

    //     foreach ($profiles as $profile) {
    //         if (!$profile->employeeProfile) {
    //             continue;
    //         }

    //         $employee_biometric_id = $profile->employeeProfile->biometric_id;

    //         $dtr = DailyTimeRecords::select(
    //             'id',
    //             'biometric_id',
    //             'first_in',
    //             'second_in',
    //             'first_out',
    //             'second_out',
    //             'dtr_date',
    //             'total_working_minutes',
    //             'undertime_minutes',
    //             'overtime_minutes',
    //             'overall_minutes_rendered',
    //             'total_minutes_reg',
    //             DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day')
    //         )
    //             ->where('biometric_id', $employee_biometric_id)
    //             ->whereBetween('first_in', $date_range)
    //             ->whereNotNull('first_in')
    //             ->get();
    //         $employee_schedules = [];
    //         $total_days_with_tardiness = 0;

    //         if (count($dtr) === 0) {
    //             $device_logs = DeviceLogs::where('biometric_id', $employee_biometric_id)->where('active', 1);
    //         } else if (count($this->DEVICE_LOG->CheckDTR($employee_biometric_id))) {
    //             $this->DEVICE_LOG->GenerateEntry($this->DEVICE_LOG->CheckDTR($employee_biometric_id), null, true);
    //         }


    //         foreach ($dtr as $d) {
    //             $bio_entry = [
    //                 'first_entry' => $d->first_in ?? $d->second_in,
    //                 'date_time' => $d->first_in ?? $d->second_in,
    //             ];

    //             $first_in = $d->first_in;
    //             $second_in = $d->second_in;
    //             $record_dtr_date = Carbon::parse($d->dtr_date);

    //             $current_schedule = ReportHelpers::CurrentSchedule($employee_biometric_id, $bio_entry, false);
    //             $day_schedule = $current_schedule['daySchedule'];
    //             $employee_schedules[] = $day_schedule;

    //             $dtr_date = $d->dtr_date;
    //             $device_logs = DeviceLogs::where('biometric_id', $employee_biometric_id)
    //                 ->where('dtr_date', $dtr_date)
    //                 ->where('active', 1);

    //             if ($device_logs->exists()) {
    //                 $check_dtr = DailyTimeRecords::whereDate('dtr_date', $dtr_date)->where('biometric_id', $employee_biometric_id);
    //                 if ($check_dtr->exists()) {
    //                     $this->DEVICE_LOG->RegenerateEntry($device_logs->get(), $employee_biometric_id, false);
    //                 } else {
    //                     $this->DEVICE_LOG->RegenerateEntry($device_logs->get(), $dtr_date, true);
    //                 }
    //             }


    //             if (count($day_schedule) >= 1) {
    //                 $validate = [
    //                     (object)[
    //                         'id' => $d->id,
    //                         'first_in' => $d->first_in,
    //                         'first_out' => $d->first_out,
    //                         'second_in' => $d->second_in,
    //                         'second_out' => $d->second_out
    //                     ]
    //                 ];

    //                 $startOfDay8 = $record_dtr_date->copy()->startOfDay()->addHours(8);
    //                 $startOfDay13 = $record_dtr_date->copy()->startOfDay()->addHours(13);

    //                 if ($first_in && Carbon::parse($first_in)->gt($startOfDay8)) {
    //                     $total_days_with_tardiness++;
    //                 }
    //                 if ($second_in && Carbon::parse($second_in)->gt($startOfDay13)) {
    //                     $total_days_with_tardiness++;
    //                 }

    //                 ReportHelpers::saveTotalWorkingHours(
    //                     $validate,
    //                     $d,
    //                     $d,
    //                     $day_schedule,
    //                     true
    //                 );
    //             }
    //         }



    //         // FOR LEAVE APPLICATIONS
    //         if ($profile->employeeProfile->leaveApplications) {
    //             $leave_applications = $profile->employeeProfile->leaveApplications->filter(function ($row) {
    //                 return $row['status'] === "received";
    //             });

    //             $leave_data = [];
    //             foreach ($leave_applications as $leave_application) {
    //                 $leave_data[] = [
    //                     'country' => $leave_application['country'],
    //                     'city' => $leave_application['city'],
    //                     'from' => $leave_application['date_from'],
    //                     'to' => $leave_application['date_to'],
    //                     'leavetype' => LeaveType::find($leave_application['leave_type_id'])->name ?? "",
    //                     'without_pay' => $leave_application['without_pay'],
    //                     'date_covered' => ReportHelpers::getDateIntervals($leave_application['date_from'], $leave_application['date_to'])
    //                 ];
    //             }
    //         }

    //         // FOR OFFICIAL BUSINESS
    //         if ($profile->employeeProfile->officialBusinessApplications) {
    //             $official_business = array_values($profile->employeeProfile->officialBusinessApplications->filter(function ($row) {
    //                 return $row['status'] === "approved";
    //             })->toArray());

    //             $official_business_data = [];

    //             foreach ($official_business as $ob) {
    //                 $official_business_data[] = [
    //                     'purpose' => $ob['purpose'],
    //                     'date_from' => $ob['date_from'],
    //                     'date_to' => $ob['date_to'],
    //                     'date_covered' => ReportHelpers::getDateIntervals($ob['date_from'], $ob['date_to'])
    //                 ];
    //             }
    //         }

    //         // OFFIICAL TIME
    //         if ($profile->employeeProfile->officialTimeApplications) {
    //             $official_time = $profile->employeeProfile->officialTimeApplications->filter(function ($row) {
    //                 return $row['status'] === "approved";
    //             });

    //             $official_time_data  = [];

    //             foreach ($official_time as $ot) {
    //                 $official_time_data[] = [
    //                     'date_from' => $ot['date_from'],
    //                     'date_to' => $ot['date_to'],
    //                     'purpose' => $ot['purpose'],
    //                     'date_covered' => ReportHelpers::getDateIntervals($ot['date_from'], $ot['date_to'])
    //                 ];
    //             }
    //         }

    //         // CTO APPLICATIONS
    //         if ($profile->employeeProfile->ctoApplications) {
    //             $cto_applications = $profile->employeeProfile->ctoApplications->filter(function ($row) {
    //                 return $row['status'] === "approved";
    //             });

    //             $cto_data = [];

    //             foreach ($cto_applications as $cto) {
    //                 $cto_data[] = [
    //                     'date' => date('Y-m-d', strtotime($cto['date'])),
    //                     'purpose' => $cto['purpose'],
    //                     'remarks' => $cto['remarks'],
    //                 ];
    //             }
    //         }

    //         if (count($employee_schedules) >= 1) {
    //             $month_of = $start_month;
    //             $year_of = $start_year;
    //             $employee_schedules = array_map(function ($sched) {
    //                 return (int) date('d', strtotime($sched['scheduleDate']));
    //             }, ReportHelpers::Allschedule($employee_biometric_id, $month_of, $year_of, null, null, null, null)['schedule']);
    //         }

    //         // array initializations
    //         $attendance = [];
    //         $leave_without_pay = [];
    //         $leave_with_pay = [];
    //         $obot = [];
    //         $absences = [];
    //         $day_off = [];
    //         $total_month_working_minutes = 0;
    //         $total_month_overtime = 0;
    //         $total_month_undertime = 0;
    //         $total_hours_missed = 0;
    //         $invalid_entry = [];

    //         // present days
    //         $present_days = array_map(function ($d) use ($employee_schedules) {
    //             if (in_array($d['day'], $employee_schedules)) {
    //                 return $d['day'];
    //             }
    //         }, $dtr->toArray());

    //         // absent days
    //         $absent_days = array_values(array_filter(array_map(function ($d) use ($present_days) {
    //             if (!in_array($d, $present_days) && $d !== null) {
    //                 return $d;
    //             }
    //         }, $employee_schedules)));

    //         for ($i = $start_day; $i <= $end_day; $i++) {
    //             $filtered_leave_dates = [];
    //             $leave_status = [];
    //             $month_of = $start_month;
    //             $year_of = $start_year;

    //             foreach ($leave_data as $leave) {
    //                 foreach ($leave['date_covered'] as $date) {
    //                     $filtered_leave_dates[] = [
    //                         'dateReg' => strtotime($date),
    //                         'status' => $leave['without_pay']
    //                     ];
    //                 }
    //             }

    //             $leave_application = array_filter($filtered_leave_dates, function ($ts) use ($year_of, $month_of, $i) {
    //                 $date_to_compare = date('Y-m-d', (int)$ts);

    //                 $date_to_match = date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' .  $i));
    //                 return $date_to_compare === $date_to_match;
    //             });

    //             $leave_count = count($leave_application);

    //             // check official business dates
    //             $filtered_OB_dates = [];
    //             foreach ($official_business_data as $ob) {
    //                 foreach ($ob['date_covered'] as  $date) {
    //                     $filtered_OB_dates[] = strtotime($date);
    //                 }
    //             }

    //             $OB_application = array_filter($filtered_OB_dates, function ($ts) use ($year_of, $month_of, $i) {
    //                 $date_to_compare = date('Y-m-d', (int)$ts);

    //                 $date_to_match = date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' .  $i));
    //                 return $date_to_compare === $date_to_match;
    //             });
    //             $official_business_count = count($OB_application);

    //             // check official time dates
    //             $filtered_OT_dates = [];
    //             foreach ($official_business_data as $ob) {
    //                 foreach ($ob['date_covered'] as  $date) {
    //                     $filtered_OB_dates[] = strtotime($date);
    //                 }
    //             }
    //             $OT_application = array_filter($filtered_OT_dates, function ($ts) use ($year_of, $month_of, $i) {
    //                 $date_to_compare = date('Y-m-d', (int)$ts);

    //                 $date_to_match = date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' .  $i));
    //                 return $date_to_compare === $date_to_match;
    //             });
    //             $official_time_count = count($OT_application);

    //             // check CTO applications dates

    //             $CTO_application = array_filter($cto_data, function ($ts) use ($year_of, $month_of, $i) {
    //                 $date_to_compare = date('Y-m-d', strtotime($ts['date']));
    //                 $date_to_match = date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' .  $i));
    //                 return $date_to_compare === $date_to_match;
    //             });
    //             $cto_count = count($CTO_application);

    //             if ($leave_count) {
    //                 if (array_values($leave_application)[0]['status']) {
    //                     $leave_without_pay[] = [
    //                         'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
    //                     ];
    //                 } else {
    //                     $leave_with_pay[] = [
    //                         'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
    //                     ];
    //                     $total_month_working_minutes += 480;
    //                 }
    //             } else if ($official_business_count || $official_time_count) {
    //                 $obot[] = [
    //                     'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),

    //                 ];
    //                 $total_month_working_minutes += 480;
    //             } else {
    //                 if (in_array($i, $present_days) && in_array($i, $employee_schedules)) {
    //                     $record_dtr = array_values(array_filter($dtr->toArray(), function ($d) use ($year_of, $month_of, $i) {
    //                         return $d['dtr_date'] === date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i));
    //                     }));

    //                     if (
    //                         ($record_dtr[0]['first_in'] && $record_dtr[0]['first_out'] && $record_dtr[0]['second_in'] && $record_dtr[0]['second_out']) || // all entry
    //                         (!$record_dtr[0]['first_in'] && !$record_dtr[0]['first_out'] && $record_dtr[0]['second_in'] && $record_dtr[0]['second_out'])  || //3-4
    //                         ($record_dtr[0]['first_in'] && $record_dtr[0]['first_out'] && !$record_dtr[0]['second_in'] && !$record_dtr[0]['second_out']) || // 1-2
    //                         ($record_dtr[0]['first_in'] && $record_dtr[0]['first_out'] && $record_dtr[0]['second_in'] && !$record_dtr[0]['second_out']) // 1-2-3
    //                     ) {

    //                         $attendance[] = $this->Attendance($year_of, $month_of, $i, $record_dtr);
    //                         $total_month_working_minutes += $record_dtr[0]['total_working_minutes'];
    //                         $total_month_overtime += $record_dtr[0]['overtime_minutes'];
    //                         $total_month_undertime += $record_dtr[0]['undertime_minutes'];
    //                         $total_hours_missed += ReportHelpers::ToHours((480 - $record_dtr[0]['total_working_minutes']));
    //                     } else {
    //                         $invalid_entry[] =  $this->Attendance($year_of, $month_of, $i, $record_dtr);
    //                     }
    //                 } else if (
    //                     in_array($i, $absent_days) &&
    //                     in_array($i, $employee_schedules) &&
    //                     strtotime(date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i))) <  strtotime(date('Y-m-d'))
    //                 ) {
    //                     $absences[] = [
    //                         'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
    //                     ];
    //                 } else {
    //                     $day_off[] = [
    //                         'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
    //                     ];
    //                 }
    //             }

    //             $present_count = count(array_filter($attendance, function ($d) {
    //                 return $d['total_working_minutes'] !== 0;
    //             }));

    //             $number_of_absences = count($absences) - count($leave_without_pay);
    //             $schedule_ = ReportHelpers::Allschedule($employee_biometric_id, $month_of, $year_of, null, null, null, null)['schedule'];

    //             $scheds = array_map(function ($d) {
    //                 return (int) date('d', strtotime($d['scheduleDate']));
    //             }, $schedule_);

    //             $filtered_scheds = array_values(array_filter($scheds, function ($value) use ($start_day, $end_day) {
    //                 return $value >= $start_day && $value <= $end_day;
    //             }));
    //         }

    //         $arr_data[] = [
    //             'id' => $profile->id,
    //             'employee_biometric_id' => $profile->employeeProfile->biometric_id,
    //             'employee_id' => $profile->employeeProfile->employee_id,
    //             'employee_name' => $profile->employeeProfile->personalInformation->employeeName(),
    //             'employment_type' => $profile->employeeProfile->employmentType->name,
    //             'employee_designation_name' => $profile->employeeProfile->findDesignation()['name'] ?? '',
    //             'employee_designation_code' => $profile->employeeProfile->findDesignation()['code'] ?? '',
    //             'sector' => $profile->employeeProfile->assignedArea->findDetails()['sector'] ?? '',
    //             'area_name' => $profile->employeeProfile->assignedArea->findDetails()['details']['name'] ?? '',
    //             'area_code' => $profile->employeeProfile->assignedArea->findDetails()['details']['code'] ?? '',
    //             'from' => $start_day,
    //             'to' => $end_day,
    //             'month' => $month_of,
    //             'year' => $year_of,
    //             'total_working_minutes' => $total_month_working_minutes,
    //             'total_working_hours' => ReportHelpers::ToHours($total_month_working_minutes),
    //             'total_overtime_minutes' => $total_month_overtime,
    //             'total_undertime_minutes' => $total_month_undertime,
    //             'total_hours_missed' => $total_hours_missed,
    //             'total_days_with_tardiness' => $total_days_with_tardiness,
    //             'total_of_absent_days' => $number_of_absences,
    //             'total_of_present_days' => $present_count,
    //             'total_of_leave_without_pay' => count($leave_without_pay),
    //             'total_of_leave_with_pay' => count($leave_with_pay),
    //             'total_invalid_entry' => count($invalid_entry),
    //             'total_of_day_off' => count($day_off),
    //             'schedule' => count($filtered_scheds),
    //         ];
    //     }

    //     return $arr_data;
    // }

    public function filterAttendanceReport(Request $request)
    {
        try {
            $results = collect();
            $area_id = $request->area_id;
            $area_under = $request->area_under;
            $sector = ucfirst($request->sector);
            $employment_type = $request->employment_type_id;
            $designation_id = $request->designation_id;
            $absent_without_pay = $request->absent_without_pay ?? false; // new parameter to filter absences without pay
            $absent_without_official_leave = $request->absent_without_official_leave ?? false; // parameter to filter absences without official leave
            $whole_month = $request->whole_month;
            $first_half = (bool) $request->first_half;
            $second_half  = (bool) $request->second_half;
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $month_of = (int) $request->month_of;
            $year_of = (int) $request->year_of;
            $limit = $request->limit; // default limit is 100
            $report_type = $request->report_type; // new parameter for report type [tardiness/absences]
            $sort_order = $request->sort_order; // new parameter for sort order [asc/desc]
            $whole_month = true;

            switch ($sector) {
                case 'Division':
                    switch ($area_under) {
                        case 'all':
                            $current_date = Carbon::now()->toDateString(); // Get current date in YYYY-MM-DD format

                            $query = null;

                            if ($year_of && $month_of) {
                                $query = DailyTimeRecords::whereYear('dtr_date', $year_of)
                                    ->whereMonth('dtr_date', $month_of)
                                    ->pluck('biometric_id');
                            } else if ($start_date && $end_date) {
                                $query = DailyTimeRecords::whereBetween('dtr_date', [$start_date, $end_date])->pluck('biometric_id');
                            }

                            $dtr_biometric_ids = $query;

                            $profiles = collect();

                            $division_profiles = AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.dailyTimeRecords', 'employeeProfile.leaveApplications'])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($current_date && $year_of && $month_of, function ($query) use ($current_date, $year_of, $month_of) {
                                    $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $year_of, $month_of) {
                                        $query->whereYear('dtr_date', $year_of)
                                            ->whereMonth('dtr_date', $month_of)
                                            ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                    });
                                })
                                ->when($current_date && $start_date && $end_date, function ($query) use ($current_date, $start_date, $end_date) {
                                    $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $start_date, $end_date) {
                                        $query->whereBetween('dtr_date', [$start_date, $end_date])
                                            ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                    });
                                })
                                ->when($dtr_biometric_ids, function ($query) use ($dtr_biometric_ids) {
                                    return $query->whereHas('employeeProfile', function ($q) use ($dtr_biometric_ids) {
                                        $q->whereIn('biometric_id', $dtr_biometric_ids);
                                    });
                                })
                                ->when($designation_id, function ($query) use ($designation_id) { // filter by designation
                                    return $query->where('designation_id', $designation_id);
                                })
                                ->when($employment_type, function ($query) use ($employment_type) { // filter by employment type
                                    $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                        $q->where('employment_type_id', $employment_type);
                                    });
                                })
                                ->get();

                            $profiles = $profiles->merge($division_profiles);

                            $departments = Department::where('division_id', $area_id)->get();
                            foreach ($departments as $department) {
                                $department_profiles = AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.dailyTimeRecords', 'employeeProfile.leaveApplications'])
                                    ->where('department_id', $department->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($current_date && $year_of && $month_of, function ($query) use ($current_date, $year_of, $month_of) {
                                        $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $year_of, $month_of) {
                                            $query->whereYear('dtr_date', $year_of)
                                                ->whereMonth('dtr_date', $month_of)
                                                ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                        });
                                    })
                                    ->when($current_date && $start_date && $end_date, function ($query) use ($current_date, $start_date, $end_date) {
                                        $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $start_date, $end_date) {
                                            $query->whereBetween('dtr_date', [$start_date, $end_date])
                                                ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                        });
                                    })
                                    ->when($dtr_biometric_ids, function ($query) use ($dtr_biometric_ids) {
                                        return $query->whereHas('employeeProfile', function ($q) use ($dtr_biometric_ids) {
                                            $q->whereIn('biometric_id', $dtr_biometric_ids);
                                        });
                                    })
                                    ->when($designation_id, function ($query) use ($designation_id) { // filter by designation
                                        return $query->where('designation_id', $designation_id);
                                    })
                                    ->when($employment_type, function ($query) use ($employment_type) { // filter by employment type
                                        $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                            $q->where('employment_type_id', $employment_type);
                                        });
                                    })
                                    ->get();

                                $profiles = $profiles->merge($department_profiles);

                                $sections = Section::where('department_id', $department->id)->get();
                                foreach ($sections as $section) {
                                    $section_profiles = AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.dailyTimeRecords', 'employeeProfile.leaveApplications'])
                                        ->where('section_id', $section->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($current_date && $year_of && $month_of, function ($query) use ($current_date, $year_of, $month_of) {
                                            $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $year_of, $month_of) {
                                                $query->whereYear('dtr_date', $year_of)
                                                    ->whereMonth('dtr_date', $month_of)
                                                    ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                            });
                                        })
                                        ->when($current_date && $start_date && $end_date, function ($query) use ($current_date, $start_date, $end_date) {
                                            $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $start_date, $end_date) {
                                                $query->whereBetween('dtr_date', [$start_date, $end_date])
                                                    ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                            });
                                        })
                                        ->when($dtr_biometric_ids, function ($query) use ($dtr_biometric_ids) {
                                            return $query->whereHas('employeeProfile', function ($q) use ($dtr_biometric_ids) {
                                                $q->whereIn('biometric_id', $dtr_biometric_ids);
                                            });
                                        })
                                        ->when($designation_id, function ($query) use ($designation_id) { // filter by designation
                                            return $query->where('designation_id', $designation_id);
                                        })
                                        ->when($employment_type, function ($query) use ($employment_type) { // filter by employment type
                                            $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                                $q->where('employment_type_id', $employment_type);
                                            });
                                        })
                                        ->get();

                                    $profiles = $profiles->merge($section_profiles);

                                    $units = Unit::where('section_id', $section->id)->get();
                                    foreach ($units as $unit) {
                                        $unit_profiles = AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.dailyTimeRecords', 'employeeProfile.leaveApplications'])
                                            ->where('unit_id', $unit->id)
                                            ->where('employee_profile_id', '<>', 1)
                                            ->when($current_date && $year_of && $month_of, function ($query) use ($current_date, $year_of, $month_of) {
                                                $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $year_of, $month_of) {
                                                    $query->whereYear('dtr_date', $year_of)
                                                        ->whereMonth('dtr_date', $month_of)
                                                        ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                                });
                                            })
                                            ->when($current_date && $start_date && $end_date, function ($query) use ($current_date, $start_date, $end_date) {
                                                $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $start_date, $end_date) {
                                                    $query->whereBetween('dtr_date', [$start_date, $end_date])
                                                        ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                                });
                                            })
                                            ->when($dtr_biometric_ids, function ($query) use ($dtr_biometric_ids) {
                                                return $query->whereHas('employeeProfile', function ($q) use ($dtr_biometric_ids) {
                                                    $q->whereIn('biometric_id', $dtr_biometric_ids);
                                                });
                                            })
                                            ->when($designation_id, function ($query) use ($designation_id) { // filter by designation
                                                return $query->where('designation_id', $designation_id);
                                            })
                                            ->when($employment_type, function ($query) use ($employment_type) { // filter by employment type
                                                $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                                    $q->where('employment_type_id', $employment_type);
                                                });
                                            })
                                            ->get();

                                        $profiles = $profiles->merge($unit_profiles);
                                    }
                                }
                            }

                            // Get sections directly under the division (if any) that are not under any department
                            $sections = Section::where('division_id', $area_id)->whereNull('department_id')->get();
                            foreach ($sections as $section) {
                                $section_profiles = AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.dailyTimeRecords', 'employeeProfile.leaveApplications'])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($current_date && $year_of && $month_of, function ($query) use ($current_date, $year_of, $month_of) {
                                        $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $year_of, $month_of) {
                                            $query->whereYear('dtr_date', $year_of)
                                                ->whereMonth('dtr_date', $month_of)
                                                ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                        });
                                    })
                                    ->when($current_date && $start_date && $end_date, function ($query) use ($current_date, $start_date, $end_date) {
                                        $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $start_date, $end_date) {
                                            $query->whereBetween('dtr_date', [$start_date, $end_date])
                                                ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                        });
                                    })
                                    ->when($dtr_biometric_ids, function ($query) use ($dtr_biometric_ids) {
                                        return $query->whereHas('employeeProfile', function ($q) use ($dtr_biometric_ids) {
                                            $q->whereIn('biometric_id', $dtr_biometric_ids);
                                        });
                                    })
                                    ->when($designation_id, function ($query) use ($designation_id) { // filter by designation
                                        return $query->where('designation_id', $designation_id);
                                    })
                                    ->when($employment_type, function ($query) use ($employment_type) { // filter by employment type
                                        $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                            $q->where('employment_type_id', $employment_type);
                                        });
                                    })
                                    ->get();

                                $profiles = $profiles->merge($section_profiles);

                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $unit_profiles = AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.dailyTimeRecords', 'employeeProfile.leaveApplications'])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($current_date && $year_of && $month_of, function ($query) use ($current_date, $year_of, $month_of) {
                                            $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $year_of, $month_of) {
                                                $query->whereYear('dtr_date', $year_of)
                                                    ->whereMonth('dtr_date', $month_of)
                                                    ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                            });
                                        })
                                        ->when($current_date && $start_date && $end_date, function ($query) use ($current_date, $start_date, $end_date) {
                                            $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $start_date, $end_date) {
                                                $query->whereBetween('dtr_date', [$start_date, $end_date])
                                                    ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                            });
                                        })
                                        ->when($dtr_biometric_ids, function ($query) use ($dtr_biometric_ids) {
                                            return $query->whereHas('employeeProfile', function ($q) use ($dtr_biometric_ids) {
                                                $q->whereIn('biometric_id', $dtr_biometric_ids);
                                            });
                                        })
                                        ->when($designation_id, function ($query) use ($designation_id) { // filter by designation
                                            return $query->where('designation_id', $designation_id);
                                        })
                                        ->when($employment_type, function ($query) use ($employment_type) { // filter by employment type
                                            $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                                $q->where('employment_type_id', $employment_type);
                                            });
                                        })
                                        ->get();

                                    $profiles = $profiles->merge($unit_profiles);
                                }
                            }

                            $profiles = $profiles->take($limit);

                            if ($year_of && $month_of) {
                                $results = $this->GenerateReportYearMonthOf($first_half, $second_half, $month_of, $year_of, $profiles);;
                            } else if ($start_date && $end_date) {
                                $results =  $this->GenerateReportDateRange($start_date, $end_date, $profiles);
                            } else {
                                $results = [];
                                return response()->json([
                                    'message' => 'Invalid date'
                                ]);
                            }
                            break;
                        case 'staff':
                            $current_date = Carbon::now()->toDateString(); // Get current date in YYYY-MM-DD format

                            $query = null;

                            if ($year_of && $month_of) {
                                $query = DailyTimeRecords::whereYear('dtr_date', $year_of)
                                    ->whereMonth('dtr_date', $month_of)
                                    ->pluck('biometric_id');
                            } else if ($start_date && $end_date) {
                                $query = DailyTimeRecords::whereBetween('dtr_date', [$start_date, $end_date])->pluck('biometric_id');
                            }

                            $dtr_biometric_ids = $query;

                            $profiles = collect();

                            $division_profiles = AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.dailyTimeRecords', 'employeeProfile.leaveApplications'])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($current_date && $year_of && $month_of, function ($query) use ($current_date, $year_of, $month_of) {
                                    $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $year_of, $month_of) {
                                        $query->whereYear('dtr_date', $year_of)
                                            ->whereMonth('dtr_date', $month_of)
                                            ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                    });
                                })
                                ->when($current_date && $start_date && $end_date, function ($query) use ($current_date, $start_date, $end_date) {
                                    $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $start_date, $end_date) {
                                        $query->whereBetween('dtr_date', [$start_date, $end_date])
                                            ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                    });
                                })
                                ->when($dtr_biometric_ids, function ($query) use ($dtr_biometric_ids) {
                                    return $query->whereHas('employeeProfile', function ($q) use ($dtr_biometric_ids) {
                                        $q->whereIn('biometric_id', $dtr_biometric_ids);
                                    });
                                })
                                ->when($designation_id, function ($query) use ($designation_id) { // filter by designation
                                    return $query->where('designation_id', $designation_id);
                                })
                                ->when($employment_type, function ($query) use ($employment_type) { // filter by employment type
                                    $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                        $q->where('employment_type_id', $employment_type);
                                    });
                                })
                                ->get();

                            $profiles = $division_profiles;
                            $profiles = $profiles->take($limit);

                            if ($year_of && $month_of) {
                                $results = $this->GenerateReportYearMonthOf($first_half, $second_half, $month_of, $year_of, $profiles);;
                            } else if ($start_date && $end_date) {
                                $results =  $this->GenerateReportDateRange($start_date, $end_date, $profiles);
                            } else {
                                $results = [];
                                return response()->json([
                                    'message' => 'Invalid date'
                                ]);
                            }
                            break;
                        default:
                            return response()->json([
                                'message' => 'Invalid report type'
                            ], 400); // Added status code for better response
                    }
                    break;
                case 'Department':
                    switch ($area_under) {
                        case 'all':
                            $current_date = Carbon::now()->toDateString(); // Get current date in YYYY-MM-DD format

                            $query = null;

                            if ($year_of && $month_of) {
                                $query = DailyTimeRecords::whereYear('dtr_date', $year_of)
                                    ->whereMonth('dtr_date', $month_of)
                                    ->pluck('biometric_id');
                            } else if ($start_date && $end_date) {
                                $query = DailyTimeRecords::whereBetween('dtr_date', [$start_date, $end_date])->pluck('biometric_id');
                            }

                            $dtr_biometric_ids = $query;

                            $profiles = collect();

                            $department_profiles =  AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.dailyTimeRecords', 'employeeProfile.leaveApplications'])
                                ->where('department_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($current_date && $year_of && $month_of, function ($query) use ($current_date, $year_of, $month_of) {
                                    $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $year_of, $month_of) {
                                        $query->whereYear('dtr_date', $year_of)
                                            ->whereMonth('dtr_date', $month_of)
                                            ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                    });
                                })
                                ->when($current_date && $start_date && $end_date, function ($query) use ($current_date, $start_date, $end_date) {
                                    $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $start_date, $end_date) {
                                        $query->whereBetween('dtr_date', [$start_date, $end_date])
                                            ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                    });
                                })
                                ->when($dtr_biometric_ids, function ($query) use ($dtr_biometric_ids) {
                                    return $query->whereHas('employeeProfile', function ($q) use ($dtr_biometric_ids) {
                                        $q->whereIn('biometric_id', $dtr_biometric_ids);
                                    });
                                })
                                ->when($designation_id, function ($query) use ($designation_id) { // filter by designation
                                    return $query->where('designation_id', $designation_id);
                                })
                                ->when($employment_type, function ($query) use ($employment_type) { // filter by employment type
                                    $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                        $q->where('employment_type_id', $employment_type);
                                    });
                                })
                                ->get();


                            $profiles = $profiles->merge($department_profiles);

                            $sections = Section::where('department_id', $area_id)->get();
                            foreach ($sections as $section) {
                                $section_profiles = AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.dailyTimeRecords', 'employeeProfile.leaveApplications'])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($current_date && $year_of && $month_of, function ($query) use ($current_date, $year_of, $month_of) {
                                        $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $year_of, $month_of) {
                                            $query->whereYear('dtr_date', $year_of)
                                                ->whereMonth('dtr_date', $month_of)
                                                ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                        });
                                    })
                                    ->when($current_date && $start_date && $end_date, function ($query) use ($current_date, $start_date, $end_date) {
                                        $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $start_date, $end_date) {
                                            $query->whereBetween('dtr_date', [$start_date, $end_date])
                                                ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                        });
                                    })
                                    ->when($dtr_biometric_ids, function ($query) use ($dtr_biometric_ids) {
                                        return $query->whereHas('employeeProfile', function ($q) use ($dtr_biometric_ids) {
                                            $q->whereIn('biometric_id', $dtr_biometric_ids);
                                        });
                                    })
                                    ->when($designation_id, function ($query) use ($designation_id) { // filter by designation
                                        return $query->where('designation_id', $designation_id);
                                    })
                                    ->when($employment_type, function ($query) use ($employment_type) { // filter by employment type
                                        $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                            $q->where('employment_type_id', $employment_type);
                                        });
                                    })
                                    ->get();

                                $profiles = $profiles->merge($section_profiles);

                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $unit_profiles = AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.dailyTimeRecords', 'employeeProfile.leaveApplications'])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($current_date && $year_of && $month_of, function ($query) use ($current_date, $year_of, $month_of) {
                                            $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $year_of, $month_of) {
                                                $query->whereYear('dtr_date', $year_of)
                                                    ->whereMonth('dtr_date', $month_of)
                                                    ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                            });
                                        })
                                        ->when($current_date && $start_date && $end_date, function ($query) use ($current_date, $start_date, $end_date) {
                                            $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $start_date, $end_date) {
                                                $query->whereBetween('dtr_date', [$start_date, $end_date])
                                                    ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                            });
                                        })
                                        ->when($dtr_biometric_ids, function ($query) use ($dtr_biometric_ids) {
                                            return $query->whereHas('employeeProfile', function ($q) use ($dtr_biometric_ids) {
                                                $q->whereIn('biometric_id', $dtr_biometric_ids);
                                            });
                                        })
                                        ->when($designation_id, function ($query) use ($designation_id) { // filter by designation
                                            return $query->where('designation_id', $designation_id);
                                        })
                                        ->when($employment_type, function ($query) use ($employment_type) { // filter by employment type
                                            $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                                $q->where('employment_type_id', $employment_type);
                                            });
                                        })
                                        ->get();

                                    $profiles = $profiles->merge($unit_profiles);
                                }
                            }

                            $profiles = $profiles->take($limit);

                            if ($year_of && $month_of) {
                                $results = $this->GenerateReportYearMonthOf($first_half, $second_half, $month_of, $year_of, $profiles);;
                            } else if ($start_date && $end_date) {
                                $results =  $this->GenerateReportDateRange($start_date, $end_date, $profiles);
                            } else {
                                $results = [];
                                return response()->json([
                                    'message' => 'Invalid date'
                                ]);
                            }

                            break;
                        case 'staff':
                            $current_date = Carbon::now()->toDateString(); // Get current date in YYYY-MM-DD format

                            $query = null;

                            if ($year_of && $month_of) {
                                $query = DailyTimeRecords::whereYear('dtr_date', $year_of)
                                    ->whereMonth('dtr_date', $month_of)
                                    ->pluck('biometric_id');
                            } else if ($start_date && $end_date) {
                                $query = DailyTimeRecords::whereBetween('dtr_date', [$start_date, $end_date])->pluck('biometric_id');
                            }

                            $dtr_biometric_ids = $query;

                            $profiles = collect();

                            $department_profiles =  AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.dailyTimeRecords', 'employeeProfile.leaveApplications'])
                                ->where('department_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($current_date && $year_of && $month_of, function ($query) use ($current_date, $year_of, $month_of) {
                                    $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $year_of, $month_of) {
                                        $query->whereYear('dtr_date', $year_of)
                                            ->whereMonth('dtr_date', $month_of)
                                            ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                    });
                                })
                                ->when($current_date && $start_date && $end_date, function ($query) use ($current_date, $start_date, $end_date) {
                                    $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $start_date, $end_date) {
                                        $query->whereBetween('dtr_date', [$start_date, $end_date])
                                            ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                    });
                                })
                                ->when($dtr_biometric_ids, function ($query) use ($dtr_biometric_ids) {
                                    return $query->whereHas('employeeProfile', function ($q) use ($dtr_biometric_ids) {
                                        $q->whereIn('biometric_id', $dtr_biometric_ids);
                                    });
                                })
                                ->when($designation_id, function ($query) use ($designation_id) { // filter by designation
                                    return $query->where('designation_id', $designation_id);
                                })
                                ->when($employment_type, function ($query) use ($employment_type) { // filter by employment type
                                    $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                        $q->where('employment_type_id', $employment_type);
                                    });
                                })
                                ->get();


                            $profiles = $profiles->merge($department_profiles);
                            $profiles = $profiles->take($limit);

                            if ($year_of && $month_of) {
                                $results = $this->GenerateReportYearMonthOf($first_half, $second_half, $month_of, $year_of, $profiles);;
                            } else if ($start_date && $end_date) {
                                $results =  $this->GenerateReportDateRange($start_date, $end_date, $profiles);
                            } else {
                                $results = [];
                                return response()->json([
                                    'message' => 'Invalid date'
                                ]);
                            }
                            break;
                        default:
                            return response()->json([
                                'message' => 'Invalid report type'
                            ]);
                    }
                    break;
                case 'Section':
                    switch ($area_under) {
                        case 'all':
                            $current_date = Carbon::now()->toDateString(); // Get current date in YYYY-MM-DD format

                            $query = null;

                            if ($year_of && $month_of) {
                                $query = DailyTimeRecords::whereYear('dtr_date', $year_of)
                                    ->whereMonth('dtr_date', $month_of)
                                    ->pluck('biometric_id');
                            } else if ($start_date && $end_date) {
                                $query = DailyTimeRecords::whereBetween('dtr_date', [$start_date, $end_date])->pluck('biometric_id');
                            }

                            $dtr_biometric_ids = $query;

                            $profiles = collect();

                            $section_profiles = AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.dailyTimeRecords', 'employeeProfile.leaveApplications'])
                                ->where('section_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($current_date && $year_of && $month_of, function ($query) use ($current_date, $year_of, $month_of) {
                                    $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $year_of, $month_of) {
                                        $query->whereYear('dtr_date', $year_of)
                                            ->whereMonth('dtr_date', $month_of)
                                            ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                    });
                                })
                                ->when($current_date && $start_date && $end_date, function ($query) use ($current_date, $start_date, $end_date) {
                                    $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $start_date, $end_date) {
                                        $query->whereBetween('dtr_date', [$start_date, $end_date])
                                            ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                    });
                                })
                                ->when($dtr_biometric_ids, function ($query) use ($dtr_biometric_ids) {
                                    return $query->whereHas('employeeProfile', function ($q) use ($dtr_biometric_ids) {
                                        $q->whereIn('biometric_id', $dtr_biometric_ids);
                                    });
                                })
                                ->when($designation_id, function ($query) use ($designation_id) { // filter by designation
                                    return $query->where('designation_id', $designation_id);
                                })
                                ->when($employment_type, function ($query) use ($employment_type) { // filter bt emloyment type
                                    $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                        $q->where('employment_type_id', $employment_type);
                                    });
                                })
                                ->get();


                            $units = Unit::where('section_id', $area_id)->get();

                            foreach ($units as $unit) {
                                $unit_profiles = AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.dailyTimeRecords', 'employeeProfile.leaveApplications'])
                                    ->where('unit_id', $unit->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($current_date && $year_of && $month_of, function ($query) use ($current_date, $year_of, $month_of) {
                                        $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $year_of, $month_of) {
                                            $query->whereYear('dtr_date', $year_of)
                                                ->whereMonth('dtr_date', $month_of)
                                                ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                        });
                                    })
                                    ->when($current_date && $start_date && $end_date, function ($query) use ($current_date, $start_date, $end_date) {
                                        $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $start_date, $end_date) {
                                            $query->whereBetween('dtr_date', [$start_date, $end_date])
                                                ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                        });
                                    })
                                    ->when($dtr_biometric_ids, function ($query) use ($dtr_biometric_ids) {
                                        return $query->whereHas('employeeProfile', function ($q) use ($dtr_biometric_ids) {
                                            $q->whereIn('biometric_id', $dtr_biometric_ids);
                                        });
                                    })
                                    ->when($dtr_biometric_ids, function ($query) use ($dtr_biometric_ids) {
                                        return $query->whereHas('employeeProfile', function ($q) use ($dtr_biometric_ids) {
                                            $q->whereIn('biometric_id', $dtr_biometric_ids);
                                        });
                                    })
                                    ->when($designation_id, function ($query) use ($designation_id) { // filter by designation
                                        return $query->where('designation_id', $designation_id);
                                    })
                                    ->when($employment_type, function ($query) use ($employment_type) { // filter bt emloyment type
                                        $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                            $q->where('employment_type_id', $employment_type);
                                        });
                                    })
                                    ->get();
                            }

                            $profiles = $section_profiles->merge($unit_profiles)->take($limit);

                            if ($year_of && $month_of) {
                                $results = $this->GenerateReportYearMonthOf($first_half, $second_half, $month_of, $year_of, $profiles);;
                            } else if ($start_date && $end_date) {
                                $results =  $this->GenerateReportDateRange($start_date, $end_date, $profiles);
                            } else {
                                $results = [];
                                return response()->json([
                                    'message' => 'Invalid date'
                                ]);
                            }
                            break;
                        case 'staff':
                            $current_date = Carbon::now()->toDateString(); // Get current date in YYYY-MM-DD format

                            $query = null;

                            if ($year_of && $month_of) {
                                $query = DailyTimeRecords::whereYear('dtr_date', $year_of)
                                    ->whereMonth('dtr_date', $month_of)
                                    ->pluck('biometric_id');
                            } else if ($start_date && $end_date) {
                                $query = DailyTimeRecords::whereBetween('dtr_date', [$start_date, $end_date])->pluck('biometric_id');
                            }

                            $dtr_biometric_ids = $query;

                            $profiles = collect();

                            $section_profiles = AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.dailyTimeRecords', 'employeeProfile.leaveApplications'])
                                ->where('section_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($current_date && $year_of && $month_of, function ($query) use ($current_date, $year_of, $month_of) {
                                    $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $year_of, $month_of) {
                                        $query->whereYear('dtr_date', $year_of)
                                            ->whereMonth('dtr_date', $month_of)
                                            ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                    });
                                })
                                ->when($current_date && $start_date && $end_date, function ($query) use ($current_date, $start_date, $end_date) {
                                    $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $start_date, $end_date) {
                                        $query->whereBetween('dtr_date', [$start_date, $end_date])
                                            ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                                    });
                                })
                                ->when($dtr_biometric_ids, function ($query) use ($dtr_biometric_ids) {
                                    return $query->whereHas('employeeProfile', function ($q) use ($dtr_biometric_ids) {
                                        $q->whereIn('biometric_id', $dtr_biometric_ids);
                                    });
                                })
                                ->when($designation_id, function ($query) use ($designation_id) { // filter by designation
                                    return $query->where('designation_id', $designation_id);
                                })
                                ->when($employment_type, function ($query) use ($employment_type) { // filter bt emloyment type
                                    $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                        $q->where('employment_type_id', $employment_type);
                                    });
                                })
                                ->get();

                            $profiles = $section_profiles->take($limit);

                            if ($year_of && $month_of) {
                                $results = $this->GenerateReportYearMonthOf($first_half, $second_half, $month_of, $year_of, $profiles);;
                            } else if ($start_date && $end_date) {
                                $results =  $this->GenerateReportDateRange($start_date, $end_date, $profiles);
                            } else {
                                $results = [];
                                return response()->json([
                                    'message' => 'Invalid date'
                                ]);
                            }
                            break;
                        default:
                            return response()->json([
                                'message' => 'Invalid report type'
                            ], 400); // Added status code for better response
                    }


                case 'Unit':
                    $current_date = Carbon::now()->toDateString(); // Get current date in YYYY-MM-DD format

                    $query = null;

                    if ($year_of && $month_of) {
                        $query = DailyTimeRecords::whereYear('dtr_date', $year_of)
                            ->whereMonth('dtr_date', $month_of)
                            ->pluck('biometric_id');
                    } else if ($start_date && $end_date) {
                        $query = DailyTimeRecords::whereBetween('dtr_date', [$start_date, $end_date])->pluck('biometric_id');
                    }

                    $dtr_biometric_ids = $query;

                    $profiles = collect();

                    $unit_profiles =  AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.dailyTimeRecords', 'employeeProfile.leaveApplications'])
                        ->where('unit_id', $area_id)
                        ->where('employee_profile_id', '<>', 1)
                        ->when($current_date && $year_of && $month_of, function ($query) use ($current_date, $year_of, $month_of) {
                            $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $year_of, $month_of) {
                                $query->whereYear('dtr_date', $year_of)
                                    ->whereMonth('dtr_date', $month_of)
                                    ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                            });
                        })
                        ->when($current_date && $start_date && $end_date, function ($query) use ($current_date, $start_date, $end_date) {
                            $query->whereHas('employeeProfile.dailyTimeRecords', function ($query) use ($current_date, $start_date, $end_date) {
                                $query->whereBetween('dtr_date', [$start_date, $end_date])
                                    ->whereDate('dtr_date', '<>', $current_date); // Exclude current dtr of the employee
                            });
                        })
                        ->when($dtr_biometric_ids, function ($query) use ($dtr_biometric_ids) {
                            return $query->whereHas('employeeProfile', function ($q) use ($dtr_biometric_ids) {
                                $q->whereIn('biometric_id', $dtr_biometric_ids);
                            });
                        })
                        ->when($designation_id, function ($query) use ($designation_id) { // filter by designation
                            return $query->where('designation_id', $designation_id);
                        })
                        ->when($employment_type, function ($query) use ($employment_type) { // filter by employment type
                            $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                $q->where('employment_type_id', $employment_type);
                            });
                        })
                        ->get();

                    $profiles = $profiles->merge($unit_profiles);
                    $profiles = $profiles->take($limit);


                    if ($year_of && $month_of) {
                        $results = $this->GenerateDataReport($first_half, $second_half, $month_of, $year_of, $profiles);
                    } else if ($start_date && $end_date) {
                        // $results =  $this->GenerateReportDateRange($start_date, $end_date, $profiles);
                        $results = $this->GenerateDataReport($first_half, $second_half, 7, 2024, $profiles);;
                    } else {
                        $results = [];
                        return response()->json([
                            'message' => 'Invalid date'
                        ]);
                    }
                    break;
                default:
                    return response()->json(
                        [
                            'message' => 'Invalid input. Please enter a valid sector'
                        ]
                    );
            }

            // Format the output based on the report type
            // switch ($report_type) {
            //     case 'absences': // Sort the result based on total absent days
            //         usort($results, function ($a, $b) use ($sort_order) {
            //             return $sort_order === 'desc'
            //                 ? $b['total_of_absent_days'] <=> $a['total_of_absent_days']
            //                 : $a['total_of_absent_days'] <=> $b['total_of_absent_days'];
            //         });
            //         break;
            //     case 'tardiness': // Sort the result based on total undertime minutes
            //         usort($results, function ($a, $b) use ($sort_order) {
            //             return $sort_order === 'desc'
            //                 ? $b['total_undertime_minutes'] <=> $a['total_undertime_minutes']
            //                 : $a['total_undertime_minutes'] <=> $b['total_undertime_minutes'];
            //         });
            //         break;
            //     default:
            //         return response()->json([
            //             'message' => 'Invalid report type'
            //         ], 400); // Added status code for better response
            // }

            return response()->json([
                'count' => count($results),
                'data' => $results,
                'message' => 'Successfully retrieved data.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            // Log the error and return an internal server error response
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterAttendanceReport', $th->getMessage());
            return response()->json(
                [
                    'message' => $th->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
