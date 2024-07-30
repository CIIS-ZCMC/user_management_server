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


    private function Attendance($year_of, $month_of, $i, $recordDTR)
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

    private function ToHours($minutes)
    {
        $hours = $minutes / 60;
        return $hours;
    }


    public function GenerateDataReportPeriod($first_half, $second_half, $month_of, $year_of, $profiles)
    {

        // Extract biometric_ids
        $biometricIds = $profiles->pluck('employeeProfile.biometric_id')->unique();

        $profiles = EmployeeProfile::whereIn('biometric_id', $biometricIds)
            ->get();

        $data = [];

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
            $total_Month_Hour_Missed = 0;
            $total_Days_With_Tardiness = 0;

            foreach ($dtr as $val) {
                $bioEntry = [
                    'first_entry' => $val->first_in ?? $val->second_in,
                    'date_time' => $val->first_in ?? $val->second_in
                ];


                $first_in = $val->first_in;
                $second_in = $val->second_in;
                $record_dtr_date = Carbon::parse($val->dtr_date);
                $Schedule = ReportHelpers::CurrentSchedule($biometric_id, $bioEntry, false);
                $DaySchedule = $Schedule['daySchedule'];
                $empschedule[] = $DaySchedule;
                if (count($DaySchedule) >= 1) {

                    $startOfDay8 = $record_dtr_date->copy()->startOfDay()->addHours(8);
                    $startOfDay13 = $record_dtr_date->copy()->startOfDay()->addHours(13);

                    if ($first_in && Carbon::parse($first_in)->gt($startOfDay8)) {
                        $total_Days_With_Tardiness++;
                    }
                    if ($second_in && Carbon::parse($second_in)->gt($startOfDay13)) {
                        $total_Days_With_Tardiness++;
                    }
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

                    $dtrArray = $dtr->toArray(); // Convert object to array

                    $recordDTR = array_values(array_filter($dtrArray, function ($d) use ($year_of, $month_of, $i) {
                        return isset($d->dtr_date) && $d->dtr_date === date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i));
                    }));


                    if (
                        ($recordDTR[0]->first_in && $recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out) ||
                        (!$recordDTR[0]->first_in && !$recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out) ||
                        ($recordDTR[0]->first_in && $recordDTR[0]->first_out && !$recordDTR[0]->second_in && !$recordDTR[0]->second_out) ||
                        ($recordDTR[0]->first_in && $recordDTR[0]->first_out && $recordDTR[0]->second_in && !$recordDTR[0]->second_out)
                    ) {
                        $attd[] = $this->Attendance($year_of, $month_of, $i, $recordDTR);
                        $total_Month_WorkingMinutes += $recordDTR[0]->total_working_minutes;
                        $total_Month_Overtime += $recordDTR[0]->overtime_minutes;
                        $total_Month_Undertime += $recordDTR[0]->undertime_minutes;
                        $total_Month_Hour_Missed += ReportHelpers::ToHours((480 - $recordDTR[0]->total_working_minutes));
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

            $data[] = [
                'id' => $employee->id,
                'employee_biometric_id' => $employee->biometric_id,
                'employee_id' => $employee->employee_id,
                'employee_name' => $employee->personalInformation->employeeName(),
                'employment_type' => $employee->employmentType->name,
                'employee_designation_name' => $employee->findDesignation()['name'] ?? '',
                'employee_designation_code' => $employee->findDesignation()['code'] ?? '',
                'sector' => $employee->assignedArea->findDetails()['sector'] ?? '',
                'area_name' => $employee->assignedArea->findDetails()['details']['name'] ?? '',
                'area_code' => $employee->assignedArea->findDetails()['details']['code'] ?? '',
                'from' => $init,
                'to' => $days_In_Month,
                'month' => $month_of,
                'year' => $year_of,
                'total_working_minutes' => $total_Month_WorkingMinutes,
                'total_working_hours' => ReportHelpers::ToHours($total_Month_WorkingMinutes),
                'total_overtime_minutes' => $total_Month_Overtime,
                'total_undertime_minutes' => $total_Month_Undertime,
                'total_hours_missed' =>       $total_Month_Hour_Missed,
                'total_days_with_tardiness' => $total_Days_With_Tardiness,
                'total_of_absent_days' => $Number_Absences,
                'total_of_present_days' => $presentCount,
                'total_of_leave_without_pay' => count($lwop),
                'total_of_leave_with_pay' => count($lwp),
                'total_invalid_entry' => count($invalidEntry),
                'total_of_day_off' => count($dayoff),
                'schedule' => count($filtered_scheds),
            ];
        }

        return $data;
    }

    public function GenerateDataReportDateRange($start_date, $end_date, $profiles)
    {
        ini_set('max_execution_time', 7200);

        // Parse start and end dates
        $startDate = Carbon::parse($start_date);
        $endDate = Carbon::parse($end_date);

        // Determine the first and last day of the date range
        $firstDayOfRange = $startDate->day;
        $lastDayOfRange = $endDate->day;


        // Get month and year of start date
        $startMonth = $startDate->month; // Numeric month (1-12)
        $startYear = $startDate->year;   // Year (e.g., 2024)

        // Extract biometric_ids
        $biometricIds = $profiles->pluck('employeeProfile.biometric_id')->unique();

        $profiles = EmployeeProfile::whereIn('biometric_id', $biometricIds)
            ->get();

        $data = [];


        foreach ($profiles as $row) {
            $Employee = EmployeeProfile::find($row->id);
            $biometric_id = $row->biometric_id;
            $dtr = DB::table('daily_time_records')
                ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
                ->where('undertime_minutes', '>', 0)
                ->where(function ($query) use ($biometric_id, $startDate, $endDate) {
                    $query->where('biometric_id', $biometric_id)
                        ->whereBetween(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), [$startDate, $endDate]);
                })
                ->orWhere(function ($query) use ($biometric_id, $startDate, $endDate) {
                    $query->where('biometric_id', $biometric_id)
                        ->whereBetween(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), [$startDate, $endDate]);
                })
                ->get();

            $empschedule = [];
            $total_Month_Hour_Missed = 0;
            $total_Days_With_Tardiness = 0;



            foreach ($dtr as $val) {
                $bioEntry = [
                    'first_entry' => $val->first_in ?? $val->second_in,
                    'date_time' => $val->first_in ?? $val->second_in
                ];


                $first_in = $val->first_in;
                $second_in = $val->second_in;
                $record_dtr_date = Carbon::parse($val->dtr_date);
                $Schedule = ReportHelpers::CurrentSchedule($biometric_id, $bioEntry, false);
                $DaySchedule = $Schedule['daySchedule'];
                $empschedule[] = $DaySchedule;

                if (count($DaySchedule) >= 1) {

                    $startOfDay8 = $record_dtr_date->copy()->startOfDay()->addHours(8);
                    $startOfDay13 = $record_dtr_date->copy()->startOfDay()->addHours(13);

                    if ($first_in && Carbon::parse($first_in)->gt($startOfDay8)) {
                        $total_Days_With_Tardiness++;
                    }
                    if ($second_in && Carbon::parse($second_in)->gt($startOfDay13)) {
                        $total_Days_With_Tardiness++;
                    }
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
                }, ReportHelpers::Allschedule($biometric_id, $startMonth, $startYear, null, null, null, null)['schedule']);
            }
            // $days_In_Month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);
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

            for ($i = $firstDayOfRange; $i <= $lastDayOfRange; $i++) {

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
                    $startYear,
                    $startMonth,
                    $i,
                ) {
                    $dateToCompare = date('Y-m-d', $timestamp['dateReg']);
                    $dateToMatch = date('Y-m-d', strtotime($startYear . '-' . $startMonth . '-' . $i));
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

                $obApplication = array_filter($filteredOBDates, function ($timestamp) use ($startYear, $startMonth, $i) {
                    $dateToCompare = date('Y-m-d', $timestamp);
                    $dateToMatch = date('Y-m-d', strtotime($startYear . '-' . $startMonth . '-' . $i));
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
                $otApplication = array_filter($filteredOTDates, function ($timestamp) use ($startYear, $startMonth, $i) {
                    $dateToCompare = date('Y-m-d', $timestamp);
                    $dateToMatch = date('Y-m-d', strtotime($startYear . '-' . $startMonth . '-' . $i));
                    return $dateToCompare === $dateToMatch;
                });
                $ot_Count = count($otApplication);

                $ctoApplication = array_filter($ctoData, function ($row) use ($startYear, $startMonth, $i) {
                    $dateToCompare = date('Y-m-d', strtotime($row['date']));
                    $dateToMatch = date('Y-m-d', strtotime($startYear . '-' . $startMonth . '-' . $i));
                    return $dateToCompare === $dateToMatch;
                });
                $cto_Count = count($ctoApplication);


                if ($leave_Count) {

                    if (array_values($leaveApplication)[0]['status']) {
                        //  echo $i."-LwoPay \n";
                        $lwop[] = [
                            'dateRecord' => date('Y-m-d', strtotime($startYear . '-' . $startMonth . '-' . $i)),
                        ];
                        // deduct to salary
                    } else {
                        //  echo $i."-LwPay \n";
                        $lwp[] = [
                            'dateRecord' => date('Y-m-d', strtotime($startYear . '-' . $startMonth . '-' . $i)),
                        ];
                        $total_Month_WorkingMinutes += 480;
                    }
                } else if ($ob_Count ||  $ot_Count) {
                    // echo $i."-ob or ot Paid \n";
                    $obot[] = [
                        'dateRecord' => date('Y-m-d', strtotime($startYear . '-' . $startMonth . '-' . $i)),

                    ];
                    $total_Month_WorkingMinutes += 480;
                } else

                    if (in_array($i, $presentDays) && in_array($i, $empschedule)) {

                    $dtrArray = $dtr->toArray(); // Convert object to array

                    $recordDTR = array_values(array_filter($dtrArray, function ($d) use ($startYear, $startMonth, $i) {
                        return isset($d->dtr_date) && $d->dtr_date === date('Y-m-d', strtotime($startYear . '-' . $startMonth . '-' . $i));
                    }));


                    if (isset($recordDTR[0])) {
                        if (
                            ($recordDTR[0]->first_in && $recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out) ||
                            (!$recordDTR[0]->first_in && !$recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out) ||
                            ($recordDTR[0]->first_in && $recordDTR[0]->first_out && !$recordDTR[0]->second_in && !$recordDTR[0]->second_out) ||
                            ($recordDTR[0]->first_in && $recordDTR[0]->first_out && $recordDTR[0]->second_in && !$recordDTR[0]->second_out)
                        ) {
                            $attd[] = $this->Attendance($startYear, $startMonth, $i, $recordDTR);
                            $total_Month_WorkingMinutes += $recordDTR[0]->total_working_minutes;
                            $total_Month_Overtime += $recordDTR[0]->overtime_minutes;
                            $total_Month_Undertime += $recordDTR[0]->undertime_minutes;
                            $total_Month_Hour_Missed += ReportHelpers::ToHours((480 - $recordDTR[0]->total_working_minutes));
                        } else {
                            $invalidEntry[] = $this->Attendance($startYear, $startMonth, $i, $recordDTR);
                        }
                    }
                } else if (
                    in_array($i, $AbsentDays) &&
                    in_array($i, $empschedule) &&
                    strtotime(date('Y-m-d', strtotime($startYear . '-' . $startMonth . '-' . $i))) <  strtotime(date('Y-m-d'))
                ) {
                    //echo $i."-A  \n";

                    $absences[] = [
                        'dateRecord' => date('Y-m-d', strtotime($startYear . '-' . $startMonth . '-' . $i)),
                    ];
                } else {
                    //   echo $i."-DO\n";
                    $dayoff[] = [
                        'dateRecord' => date('Y-m-d', strtotime($startYear . '-' . $startMonth . '-' . $i)),
                    ];
                }
            }

            $presentCount = count(array_filter($attd, function ($d) {
                return $d['total_working_minutes'] !== 0;
            }));
            $Number_Absences = count($absences) - count($lwop);
            $schedule_ = ReportHelpers::Allschedule($biometric_id, $startMonth, $startYear, null, null, null, null)['schedule'];

            $scheds = array_map(function ($d) {
                return (int)date('d', strtotime($d['scheduleDate']));
            }, $schedule_);

            $filtered_scheds = array_values(array_filter($scheds, function ($value) use ($firstDayOfRange, $lastDayOfRange) {
                return $value >= $firstDayOfRange && $value <= $lastDayOfRange;
            }));

            $data[] = [
                'id' => $employee->id,
                'employee_biometric_id' => $employee->biometric_id,
                'employee_id' => $employee->employee_id,
                'employee_name' => $employee->personalInformation->employeeName(),
                'employment_type' => $employee->employmentType->name,
                'employee_designation_name' => $employee->findDesignation()['name'] ?? '',
                'employee_designation_code' => $employee->findDesignation()['code'] ?? '',
                'sector' => $employee->assignedArea->findDetails()['sector'] ?? '',
                'area_name' => $employee->assignedArea->findDetails()['details']['name'] ?? '',
                'area_code' => $employee->assignedArea->findDetails()['details']['code'] ?? '',
                'from' => $firstDayOfRange,
                'to' => $lastDayOfRange,
                'month' => $startMonth,
                'year' => $startYear,
                'total_working_minutes' => $total_Month_WorkingMinutes,
                'total_working_hours' => ReportHelpers::ToHours($total_Month_WorkingMinutes),
                'total_overtime_minutes' => $total_Month_Overtime,
                'total_undertime_minutes' => $total_Month_Undertime,
                'total_hours_missed' =>       $total_Month_Hour_Missed,
                'total_days_with_tardiness' => $total_Days_With_Tardiness,
                'total_of_absent_days' => $Number_Absences,
                'total_of_present_days' => $presentCount,
                'total_of_leave_without_pay' => count($lwop),
                'total_of_leave_with_pay' => count($lwp),
                'total_invalid_entry' => count($invalidEntry),
                'total_of_day_off' => count($dayoff),
                'schedule' => count($filtered_scheds),
            ];
        }


        return $data;
    }

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
                                $results = $this->GenerateDataReportPeriod($first_half, $second_half, $month_of, $year_of, $profiles);
                            } else if ($start_date && $end_date) {
                                $results =  $this->GenerateDataReportDateRange($start_date, $end_date, $profiles);
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
                                $results = $this->GenerateDataReportPeriod($first_half, $second_half, $month_of, $year_of, $profiles);
                            } else if ($start_date && $end_date) {
                                $results =  $this->GenerateDataReportDateRange($start_date, $end_date, $profiles);
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
                                $results = $this->GenerateDataReportPeriod($first_half, $second_half, $month_of, $year_of, $profiles);
                            } else if ($start_date && $end_date) {
                                $results =  $this->GenerateDataReportDateRange($start_date, $end_date, $profiles);
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
                                $results = $this->GenerateDataReportPeriod($first_half, $second_half, $month_of, $year_of, $profiles);
                            } else if ($start_date && $end_date) {
                                $results =  $this->GenerateDataReportDateRange($start_date, $end_date, $profiles);
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
                                $results = $this->GenerateDataReportPeriod($first_half, $second_half, $month_of, $year_of, $profiles);
                            } else if ($start_date && $end_date) {
                                $results =  $this->GenerateDataReportDateRange($start_date, $end_date, $profiles);
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
                                $results = $this->GenerateDataReportPeriod($first_half, $second_half, $month_of, $year_of, $profiles);
                            } else if ($start_date && $end_date) {
                                $results =  $this->GenerateDataReportDateRange($start_date, $end_date, $profiles);
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
                        $results = $this->GenerateDataReportPeriod($first_half, $second_half, $month_of, $year_of, $profiles);
                    } else if ($start_date && $end_date) {
                        $results =  $this->GenerateDataReportDateRange($start_date, $end_date, $profiles);
                    } else {
                        $results = [];
                        return response()->json([
                            'message' => 'Invalid date'
                        ]);
                    }
                    break;
                default:
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

                    $profiles = AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.dailyTimeRecords', 'employeeProfile.leaveApplications'])
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

                    $profiles = $profiles->take($limit);

                    if ($year_of && $month_of) {
                        $results = $this->GenerateDataReportPeriod($first_half, $second_half, $month_of, $year_of, $profiles);
                    } else if ($start_date && $end_date) {
                        $results =  $this->GenerateDataReportDateRange($start_date, $end_date, $profiles);
                    } else {
                        $results = [];
                        return response()->json([
                            'message' => 'Invalid date'
                        ]);
                    }
                    break;
            }

            // Format the output based on the report type
            switch ($report_type) {
                case 'absences': // Sort the result based on total absent days
                    usort($results, function ($a, $b) use ($sort_order) {
                        return $sort_order === 'desc'
                            ? $b['total_of_absent_days'] <=> $a['total_of_absent_days']
                            : $a['total_of_absent_days'] <=> $b['total_of_absent_days'];
                    });
                    break;
                case 'tardiness': // Sort the result based on total undertime minutes
                    usort($results, function ($a, $b) use ($sort_order) {
                        return $sort_order === 'desc'
                            ? $b['total_undertime_minutes'] <=> $a['total_undertime_minutes']
                            : $a['total_undertime_minutes'] <=> $b['total_undertime_minutes'];
                    });
                    break;
                default:
                    return response()->json([
                        'message' => 'Invalid report type'
                    ], 400); // Added status code for better response
            }

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
