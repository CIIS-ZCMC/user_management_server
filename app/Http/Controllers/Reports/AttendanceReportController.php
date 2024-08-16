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
use PhpParser\Node\Expr\Assign;

;

use App\Http\Controllers\DTR\DeviceLogsController;
use App\Http\Controllers\DTR\DTRcontroller;
use App\Http\Controllers\PayrollHooks\ComputationController;
use App\Models\Devices;
use App\Models\Schedule;
use Illuminate\Support\Facades\Cache;
use SebastianBergmann\CodeCoverage\Report\Xml\Report;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

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

    private function getPerfectAttendance($first_half, $second_half, $month_of, $year_of, $employees)
    {
        $data = [];

        $init = 1;
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);

        if ($first_half) {
            $days_in_month = 15;
        } else if ($second_half) {
            $init = 16;
        }

        foreach ($employees as $row) {
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

            $absent_days = [];
            $present_days = [];

            foreach ($dtr as $val) {
                $day_of_month = $val->day;

                if ($day_of_month < $init || $day_of_month > $days_in_month) {
                    continue;
                }

                // Check if the record has attendance data
                if (($val->first_in && $val->first_out) || ($val->second_in && $val->second_out)) {
                    $present_days[] = $day_of_month;
                } else {
                    $absent_days[] = $day_of_month;
                }
            }

            // Check if the employee was present for all days within the selected range
            $is_perfect_attendance = (count($present_days) == ($days_in_month - $init + 1)) && empty($absent_days);

            if ($is_perfect_attendance) {
                $data[] = [
                    'id' => $row->id,
                    'employee_biometric_id' => $row->biometric_id,
                    'employee_id' => $row->employee_id,
                    'employee_name' => $row->personalInformation->employeeName(),
                    'employment_type' => $row->employmentType->name,
                    'employee_designation_name' => $row->findDesignation()['name'] ?? '',
                    'employee_designation_code' => $row->findDesignation()['code'] ?? '',
                    'sector' => $row->assignedArea->findDetails()['sector'] ?? '',
                    'area_name' => $row->assignedArea->findDetails()['details']['name'] ?? '',
                    'area_code' => $row->assignedArea->findDetails()['details']['code'] ?? '',
                    'from' => $init,
                    'to' => $days_in_month,
                    'month' => $month_of,
                    'year' => $year_of,
                ];
            }
        }

        return $data;
    }

    private function retrieveAllEmployees()
    {
        // Fetch all assigned areas, excluding where employee_profile_id is 1
        $assign_areas = AssignArea::with([
            'employeeProfile',
            'employeeProfile.personalInformation',
            'employeeProfile.dailyTimeRecords',
            'employeeProfile.leaveApplications',
            'employeeProfile.schedule'
        ])
            ->where('employee_profile_id', '!=', 1)
            ->whereHas('employeeProfile', function ($query) {
                $query->whereNotNull('biometric_id'); // Ensure the biometric_id is present
            })
            ->get();


        // Extract employee profiles from the assigned areas and return as a collection
        return $assign_areas->map(function ($assign_area) {
            return $assign_area->employeeProfile;
        })->flatten();
    }

    private function retrieveEmployees($key, $params, $area_id)
    {
        $current_date = Carbon::now()->toDateString(); // Get current date in YYYY-MM-DD format
        $month_of = $params['month_of'];
        $year_of = $params['year_of'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $employment_type = $params['employment_type'];
        $designation_id = $params['designation_id'];
        $absent_leave_without_pay = $params['absent_leave_without_pay'];
        $absent_without_official_leave = $params['absent_without_official_leave'];

        $query = null;

        if ($year_of && $month_of) {
            $query = DailyTimeRecords::whereYear('dtr_date', $year_of)
                ->whereMonth('dtr_date', $month_of)
                ->pluck('biometric_id');
        } else if ($start_date && $end_date) {
            $query = DailyTimeRecords::whereBetween('dtr_date', [$start_date, $end_date])->pluck('biometric_id');
        }


        // Retrieve the biometric IDs for absences without official leave
        $leave_biometric_ids = DB::table('leave_applications')
            ->join('employee_profiles', 'leave_applications.employee_profile_id', '=', 'employee_profiles.id')
            ->where('leave_applications.status', 'approved')
            ->whereYear('leave_applications.date_from', $year_of)
            ->whereMonth('leave_applications.date_from', $month_of)
            ->pluck('employee_profiles.biometric_id');

        $absent_biometric_ids = DailyTimeRecords::whereYear('dtr_date', $year_of)
            ->whereMonth('dtr_date', $month_of)
            ->whereNull('first_in')
            ->whereNull('second_in')
            ->whereNull('first_out')
            ->whereNull('second_out')
            ->pluck('biometric_id');

        $absent_without_official_leave_biometric_ids = $absent_biometric_ids->diff($leave_biometric_ids);

        $dtr_biometric_ids = $query;

        // Fetch assigned areas where key matches id and exclude where employee_profile_id is 1
        $assign_areas = AssignArea::with([
            'employeeProfile',
            'employeeProfile.personalInformation',
            'employeeProfile.dailyTimeRecords',
            'employeeProfile.leaveApplications',
            'employeeProfile.schedule'
        ])
            ->where($key, $area_id)
            ->whereHas('employeeProfile', function ($query) {
                $query->whereNotNull('biometric_id'); // Ensure the biometric_id is present
            })
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
            })->when($absent_leave_without_pay, function ($query) use ($absent_leave_without_pay) {
                $query->whereHas('employeeProfile.leaveApplication', function ($q) use ($absent_leave_without_pay) {
                    $q->where('without_pay', $absent_leave_without_pay);
                });
            })->when($absent_without_official_leave, function ($query) use ($absent_without_official_leave_biometric_ids) {
                $query->whereHas('employeeProfile.dailyTimeRecords', function ($q) use ($absent_without_official_leave_biometric_ids) {
                    $q->whereIn('biometric_id', $absent_without_official_leave_biometric_ids);
                });
            })
            ->get();

        // Extract employee profiles from the assigned areas and return as a collection
        return $assign_areas->map(function ($assign_area) {
            return $assign_area->employeeProfile;
        })->flatten();
    }

    /**
     *
     * START OF ABSENCES
     *
     */
    private function AbsencesByPeriod($first_half, $second_half, $month_of, $year_of, $employees)
    {

        $init = 1;
        $days_In_Month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);

        if ($first_half) {
            $days_In_Month = 15;
        } else if ($second_half) {
            $init = 16;
        }

        foreach ($employees as $row) {
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

            foreach ($dtr as $val) {
                $dayOfMonth = $val->day;

                $bioEntry = [
                    'first_entry' => $val->first_in ?? $val->second_in,
                    'date_time' => $val->first_in ?? $val->second_in
                ];

                // Ensure the record falls within the selected half of the month
                if ($dayOfMonth < $init || $dayOfMonth > $days_In_Month) {
                    continue; // Skip records outside the selected half
                }

                $Schedule = ReportHelpers::CurrentSchedule($biometric_id, $bioEntry, false);
                $DaySchedule = $Schedule['daySchedule'];
                $empschedule[] = $DaySchedule;
            }


            $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();


            if ($employee->leaveApplications) {
                //Leave Applications
                $leaveapp = $employee->leaveApplications->filter(function ($row) {
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
                $CTO = $employee->ctoApplications->filter(function ($row) {
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
                // $leaveStatus = [];
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
                } else if ($ob_Count || $ot_Count) {
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
                            $missedHours = round((480 - $recordDTR[0]->total_working_minutes) / 60);
                            $total_Month_Hour_Missed += $missedHours;
                        } else {
                            $invalidEntry[] = $this->Attendance($year_of, $month_of, $i, $recordDTR);
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
                'total_hours_missed' => $total_Month_Hour_Missed,
                'total_of_absent_days' => $Number_Absences,
                'total_of_present_days' => $presentCount,
                'total_of_absent_leave_without_pay' => count($lwop),
                'total_of_leave_with_pay' => count($lwp),
                'total_invalid_entry' => count($invalidEntry),
                'total_of_day_off' => count($dayoff),
                'schedule' => count($filtered_scheds),
            ];
        }

        return $data;
    }

    private function AbsencesByDateRange($start_date, $end_date, $employees)
    {

        $startDate = Carbon::parse($start_date);
        $endDate = Carbon::parse($end_date);


        $startMonth = $startDate->month;
        $startYear = $startDate->year;

        $cacheKey = "absences_by_date_range_{$startDate}_{$endDate}_{$startMonth}_{$startYear}_" . md5(serialize($employees));

        return Cache::rember($cacheKey, 60 * 60, function () use ($startDate, $endDate, $startMonth, $startYear, $employees) {
            $data = [];
            $firstDayOfRange = $startDate->day;
            $lastDayOfRange = $endDate->day;

            foreach ($employees as $row) {
                $biometric_id = $row->biometric_id;
                $dtr = DB::table('daily_time_records')
                    ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
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

                foreach ($dtr as $val) {
                    $dayOfMonth = $val->day;

                    $bioEntry = [
                        'first_entry' => $val->first_in ?? $val->second_in,
                        'date_time' => $val->first_in ?? $val->second_in
                    ];

                    // Ensure the record falls within the selected half of the month
                    if ($dayOfMonth < $firstDayOfRange || $dayOfMonth > $lastDayOfRange) {
                        continue; // Skip records outside the selected half
                    }

                    $first_in = $val->first_in;
                    $second_in = $val->second_in;
                    $record_dtr_date = Carbon::parse($val->dtr_date);
                    $Schedule = ReportHelpers::CurrentSchedule($biometric_id, $bioEntry, false);
                    $DaySchedule = $Schedule['daySchedule'];
                    $empschedule[] = $DaySchedule;
                }


                $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();


                if ($employee->leaveApplications) {
                    //Leave Applications
                    $leaveapp = $employee->leaveApplications->filter(function ($row) {
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
                    $CTO = $employee->ctoApplications->filter(function ($row) {
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
                    // $leaveStatus = [];
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
                    } else if ($ob_Count || $ot_Count) {
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
                                $missedHours = round((480 - $recordDTR[0]->total_working_minutes) / 60);
                                $total_Month_Hour_Missed += $missedHours;
                            } else {
                                $invalidEntry[] = $this->Attendance($startYear, $startMonth, $i, $recordDTR);
                            }
                        } else if (
                            in_array($i, $AbsentDays) &&
                            in_array($i, $empschedule) &&
                            strtotime(date('Y-m-d', strtotime($startYear . '-' . $startMonth . '-' . $i))) < strtotime(date('Y-m-d'))
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
                    'total_hours_missed' => $total_Month_Hour_Missed,
                    'total_of_absent_days' => $Number_Absences,
                    'total_of_present_days' => $presentCount,
                    'total_of_absent_leave_without_pay' => count($lwop),
                    'total_of_leave_with_pay' => count($lwp),
                    'total_invalid_entry' => count($invalidEntry),
                    'total_of_day_off' => count($dayoff),
                    'schedule' => count($filtered_scheds),
                ];
            }

            return $data;
        });
    }
    /**
     *
     * END OF ABSENCES
     *
     */

    /**
     *
     * START OF UNDERTIME
     *
     */
    private function UndertimeByPeriod($first_half, $second_half, $month_of, $year_of, $employees)
    {

        $cacheKey = "undertime_by_period_{$first_half}_{$second_half}_{$month_of}_{$year_of}_" . md5(serialize($employees));
        return Cache::remember($cacheKey, 60 * 60, function () use ($first_half, $second_half, $month_of, $year_of, $employees) {
            $data = [];

            $init = 1;
            $days_In_Month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);

            if ($first_half) {
                $days_In_Month = 15;
            } else if ($second_half) {
                $init = 16;
            }

            foreach ($employees as $row) {
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

                foreach ($dtr as $val) {
                    $dayOfMonth = $val->day;

                    $bioEntry = [
                        'first_entry' => $val->first_in ?? $val->second_in,
                        'date_time' => $val->first_in ?? $val->second_in
                    ];

                    // Ensure the record falls within the selected half of the month
                    if ($dayOfMonth < $init || $dayOfMonth > $days_In_Month) {
                        continue; // Skip records outside the selected half
                    }

                    $Schedule = ReportHelpers::CurrentSchedule($biometric_id, $bioEntry, false);
                    $DaySchedule = $Schedule['daySchedule'];
                    $empschedule[] = $DaySchedule;
                }


                $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();


                if ($employee->leaveApplications) {
                    //Leave Applications
                    $leaveapp = $employee->leaveApplications->filter(function ($row) {
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
                    $CTO = $employee->ctoApplications->filter(function ($row) {
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
                    // $leaveStatus = [];
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
                    } else if ($ob_Count || $ot_Count) {
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
                                $missedHours = round((480 - $recordDTR[0]->total_working_minutes) / 60);
                                $total_Month_Hour_Missed += $missedHours;
                            } else {
                                $invalidEntry[] = $this->Attendance($year_of, $month_of, $i, $recordDTR);
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
                    'total_hours_missed' => $total_Month_Hour_Missed,
                    'total_undertime_minutes' => $total_Month_Undertime,
                    'total_of_absent_leave_without_pay' => count($lwop),
                    'total_of_leave_with_pay' => count($lwp),
                    'total_invalid_entry' => count($invalidEntry),
                    'total_of_day_off' => count($dayoff),
                    'schedule' => count($filtered_scheds),
                ];
            }

            return $data;
        });
    }

    private function UndertimeByDateRange($start_date, $end_date, $employees)
    {

        $startDate = Carbon::parse($start_date);
        $endDate = Carbon::parse($end_date);

        $startMonth = $startDate->month;
        $startYear = $startDate->year;

        $cacheKey = "undertime_by_date_range_{$startDate}_{$endDate}_{$startMonth}_{$startYear}_" . md5(serialize($employees));
        return Cache::rember($cacheKey, 60 * 60, function () use ($startDate, $endDate, $startMonth, $startYear, $employees) {
            $data = [];
            $firstDayOfRange = $startDate->day;
            $lastDayOfRange = $endDate->day;

            foreach ($employees as $row) {
                $biometric_id = $row->biometric_id;
                $dtr = DB::table('daily_time_records')
                    ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
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

                foreach ($dtr as $val) {
                    $dayOfMonth = $val->day;

                    $bioEntry = [
                        'first_entry' => $val->first_in ?? $val->second_in,
                        'date_time' => $val->first_in ?? $val->second_in
                    ];

                    // Ensure the record falls within the selected half of the month
                    if ($dayOfMonth < $firstDayOfRange || $dayOfMonth > $lastDayOfRange) {
                        continue; // Skip records outside the selected half
                    }

                    $first_in = $val->first_in;
                    $second_in = $val->second_in;
                    $record_dtr_date = Carbon::parse($val->dtr_date);
                    $Schedule = ReportHelpers::CurrentSchedule($biometric_id, $bioEntry, false);
                    $DaySchedule = $Schedule['daySchedule'];
                    $empschedule[] = $DaySchedule;
                }


                $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();


                if ($employee->leaveApplications) {
                    //Leave Applications
                    $leaveapp = $employee->leaveApplications->filter(function ($row) {
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
                    $CTO = $employee->ctoApplications->filter(function ($row) {
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
                    // $leaveStatus = [];
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
                    } else if ($ob_Count || $ot_Count) {
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
                                $missedHours = round((480 - $recordDTR[0]->total_working_minutes) / 60);
                                $total_Month_Hour_Missed += $missedHours;
                            } else {
                                $invalidEntry[] = $this->Attendance($startYear, $startMonth, $i, $recordDTR);
                            }
                        } else if (
                            in_array($i, $AbsentDays) &&
                            in_array($i, $empschedule) &&
                            strtotime(date('Y-m-d', strtotime($startYear . '-' . $startMonth . '-' . $i))) < strtotime(date('Y-m-d'))
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
                    'total_of_present_days' => $presentCount,
                    'total_of_absent_leave_without_pay' => count($lwop),
                    'total_of_leave_with_pay' => count($lwp),
                    'total_invalid_entry' => count($invalidEntry),
                    'total_of_day_off' => count($dayoff),
                    'schedule' => count($filtered_scheds),
                ];
            }

            return $data;
        });
    }
    /**
     *
     * END OF UNDERTIME
     *
     */

    /**
     *
     *START OF TARDINESS
     *
     */
    private function TardinessByPeriod($first_half, $second_half, $month_of, $year_of, $employees)
    {

        $cacheKey = "tardiness_by_period_{$first_half}_{$second_half}_{$month_of}_{$year_of}_" . md5(serialize($employees));

        return Cache::remember($cacheKey, 60 * 60, function () use ($first_half, $second_half, $month_of, $year_of, $employees) {
            $data = [];

            $init = 1;
            $days_In_Month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);

            if ($first_half) {
                $days_In_Month = 15;
            } else if ($second_half) {
                $init = 16;
            }

            foreach ($employees as $row) {
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
                    $dayOfMonth = $val->day;

                    $bioEntry = [
                        'first_entry' => $val->first_in ?? $val->second_in,
                        'date_time' => $val->first_in ?? $val->second_in
                    ];

                    // Ensure the record falls within the selected half of the month
                    if ($dayOfMonth < $init || $dayOfMonth > $days_In_Month) {
                        continue; // Skip records outside the selected half
                    }

                    $Schedule = ReportHelpers::CurrentSchedule($biometric_id, $bioEntry, false);
                    $DaySchedule = $Schedule['daySchedule'];
                    $empschedule[] = $DaySchedule;


                    if (!empty($daySchedule) && isset($daySchedule['first_entry'])) {
                        $scheduledInTime = strtotime($daySchedule['first_entry']);
                        $actualInTime = strtotime($bioEntry['date_time']);

                        if ($actualInTime > $scheduledInTime) {
                            $total_Days_With_Tardiness++;
                        }
                    }
                }


                $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();


                if ($employee->leaveApplications) {
                    //Leave Applications
                    $leaveapp = $employee->leaveApplications->filter(function ($row) {
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
                    $CTO = $employee->ctoApplications->filter(function ($row) {
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
                    // $leaveStatus = [];
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
                    } else if ($ob_Count || $ot_Count) {
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
                                $missedHours = round((480 - $recordDTR[0]->total_working_minutes) / 60);
                                $total_Month_Hour_Missed += $missedHours;
                            } else {
                                $invalidEntry[] = $this->Attendance($year_of, $month_of, $i, $recordDTR);
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
                    'total_days_with_tardiness' => $total_Days_With_Tardiness,
                    'total_of_absent_leave_without_pay' => count($lwop),
                    'total_of_leave_with_pay' => count($lwp),
                    'total_invalid_entry' => count($invalidEntry),
                    'total_of_day_off' => count($dayoff),
                    'schedule' => count($filtered_scheds),
                ];
            }

            return $data;
        });
    }

    private function TardinessByDateRange($start_date, $end_date, $employees)
    {

        $startDate = Carbon::parse($start_date);
        $endDate = Carbon::parse($end_date);

        $startMonth = $startDate->month;
        $startYear = $startDate->year;

        $cacheKey = "tardiness_by_date_range_{$startDate}_{$endDate}_{$startMonth}_{$startYear}_" . md5(serialize($employees));
        return Cache::rember($cacheKey, 60 * 60, function () use ($startDate, $endDate, $startMonth, $startYear, $employees) {
            $data = [];
            $firstDayOfRange = $startDate->day;
            $lastDayOfRange = $endDate->day;
            foreach ($employees as $row) {
                $biometric_id = $row->biometric_id;
                $dtr = DB::table('daily_time_records')
                    ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
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
                    $dayOfMonth = $val->day;

                    $bioEntry = [
                        'first_entry' => $val->first_in ?? $val->second_in,
                        'date_time' => $val->first_in ?? $val->second_in
                    ];

                    // Ensure the record falls within the selected half of the month
                    if ($dayOfMonth < $firstDayOfRange || $dayOfMonth > $lastDayOfRange) {
                        continue; // Skip records outside the selected half
                    }

                    $Schedule = ReportHelpers::CurrentSchedule($biometric_id, $bioEntry, false);
                    $DaySchedule = $Schedule['daySchedule'];
                    $empschedule[] = $DaySchedule;


                    if (!empty($daySchedule) && isset($daySchedule['first_entry'])) {
                        $scheduledInTime = strtotime($daySchedule['first_entry']);
                        $actualInTime = strtotime($bioEntry['date_time']);

                        if ($actualInTime > $scheduledInTime) {
                            $total_Days_With_Tardiness++;
                        }
                    }
                }


                $employee = EmployeeProfile::where('biometric_id', $biometric_id)->first();


                if ($employee->leaveApplications) {
                    //Leave Applications
                    $leaveapp = $employee->leaveApplications->filter(function ($row) {
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
                    $CTO = $employee->ctoApplications->filter(function ($row) {
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
                    // $leaveStatus = [];
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
                    } else if ($ob_Count || $ot_Count) {
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
                                $missedHours = round((480 - $recordDTR[0]->total_working_minutes) / 60);
                                $total_Month_Hour_Missed += $missedHours;
                            } else {
                                $invalidEntry[] = $this->Attendance($startYear, $startMonth, $i, $recordDTR);
                            }
                        } else if (
                            in_array($i, $AbsentDays) &&
                            in_array($i, $empschedule) &&
                            strtotime(date('Y-m-d', strtotime($startYear . '-' . $startMonth . '-' . $i))) < strtotime(date('Y-m-d'))
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
                    'total_of_present_days' => $presentCount,
                    'total_of_absent_leave_without_pay' => count($lwop),
                    'total_of_leave_with_pay' => count($lwp),
                    'total_invalid_entry' => count($invalidEntry),
                    'total_of_day_off' => count($dayoff),
                    'schedule' => count($filtered_scheds),
                ];
            }

            return $data;
        });
    }

    /**
     *
     *END OF TARDINESS
     *
     */
    public function xreport(Request $request)
    {
        try {
            $area_id = $request->area_id;
            $sector = ucfirst($request->sector);
            $area_under = strtolower($request->area_under);
            $month_of = (int)$request->month_of;
            $year_of = (int)$request->year_of;
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $employment_type = $request->employment_type_id;
            $designation_id = $request->designation_id;
            $absent_leave_without_pay = $request->absent_leave_without_pay;
            $absent_without_official_leave = $request->absent_without_official_leave;
            $first_half = (bool)$request->first_half;
            $second_half = (bool)$request->second_half;
            $limit = $request->limit;
            $sort_order = $request->sort_order;
            $report_type = $request->report_type;
            $page = $request->page ?? 1; // Default page if not provided
            $per_page = 10;

            $params = [
                'area_id' => $area_id,
                'month_of' => $month_of,
                'year_of' => $year_of,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'employment_type' => $employment_type,
                'designation_id' => $designation_id,
                'absent_leave_without_pay' => $absent_leave_without_pay,
                'absent_without_official_leave' => $absent_without_official_leave,
            ];

            $by_date_range = $start_date && $end_date;
            $by_period = $month_of && $year_of;

            $data = collect();
            $employees = collect();
            $division_employees = collect();
            $department_employees = collect();
            $section_employees = collect();
            $unit_employees = collect();
            $total_employees = 0;

            if ($sector && !$area_id) {
                return response()->json(['message' => 'Area ID is required when Sector is provided'], 400);
            }

            if (!$sector && !$area_id) {
                $employees = $this->retrieveAllEmployees();
                switch ($report_type) {
                    case 'absences':
                        if ($by_date_range) {
                            $data = $this->AbsencesByDateRange($start_date, $end_date, $employees);
                        } elseif ($by_period) {
                            $data = $this->AbsencesByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                        } else {
                            return response()->json(['message' => 'Please provide either a valid date range or month and year for the report.'], 400);
                        }

                        $filtered_data = collect($data)->filter(function ($item) {
                            return $item['total_of_absent_days'] > 0;
                        });
                        // Sort the data by total_of_absent_days in descending order
                        $sorted_data = $filtered_data->sortByDesc('total_of_absent_days');

                        $data = $sorted_data;
                        break;
                    case 'tardiness':
                        if ($by_date_range) {
                            $data = $this->TardinessByDateRange($start_date, $end_date, $employees);
                        } elseif ($by_period) {
                            $data = $this->TardinessByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                        } else {
                            return response()->json(['message' => 'Please provide either a valid date range or month and year for the report.'], 400);
                        }
                        $filtered_data = collect($data)->filter(function ($item) {
                            return $item['total_days_with_tardiness'] > 0;
                        });
                        // Sort the data by total_days_with_tardiness in descending order
                        $sorted_data = $filtered_data->sortByDesc('total_days_with_tardiness');

                        $data = $sorted_data;
                        break;
                    case 'undertime':
                        if ($by_date_range) {
                            $data = $this->UndertimeByDateRange($start_date, $end_date, $employees);
                        } elseif ($by_period) {
                            $data = $this->UndertimeByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                        } else {
                            return response()->json(['message' => 'Please provide either a valid date range or month and year for the report.'], 400);
                        }

                        $filtered_data = collect($data)->filter(function ($item) {
                            return $item['total_undertime_minutes'] > 0;
                        });
                        // Sort the data by total_of_absent_days in descending order
                        $sorted_data = $sort_order === 'asc'
                            ? $filtered_data->sortBy('total_undertime_minutes')
                            : $filtered_data->sortByDesc('total_undertime_minutes');

                        $data = $sorted_data;
                        break;
                    case 'perfect_attendance':
                        $data = $this->getPerfectAttendance($first_half, $second_half, $month_of, $year_of, $employees);

                        break;
                    default:
                        return response()->json(['message' => 'Invalid report type. Allowed types: absences, tardiness, undertime'], 400);
                }
            } else {
                switch ($sector) {
                    case 'Division':
                        switch ($area_under) {
                            case 'all':
                                $division_employees = $this->retrieveEmployees('division_id', $params, $area_id);
                                $employees = $division_employees;
                                $departments = Department::where('division_id', $area_id)->get();
                                foreach ($departments as $department) {
                                    $department_employees = $this->retrieveEmployees('department_id', $params, $department->id);
                                    $employees = $employees->merge($department_employees);
                                    $sections = Section::where('department_id', $department->id);
                                    foreach ($sections as $section) {
                                        $section_employees = $this->retrieveEmployees('section_id', $params, $section->id);
                                        $employees = $employees->merge($section_employees);
                                        $units = Unit::where('section_id', $section->id)->get();
                                        foreach ($units as $unit) {
                                            $unit_employees = $this->retrieveEmployees('unit_id', $params, $unit->id);
                                            $employees = $employees->merge($unit_employees);
                                        }
                                    }
                                }

                                $sections = Section::where('division_id', $area_id)->whereNull('department_id')->get();
                                foreach ($sections as $section) {
                                    $section_employees = $this->retrieveEmployees('section_id', $params, $section->id);
                                    $employees = $employees->merge($section_employees);
                                    $units = Unit::where('section_id', $section->id)->get();
                                    foreach ($units as $unit) {
                                        $unit_employees = $this->retrieveEmployees('unit_id', $params, $unit->id);
                                        $employees = $employees->merge($unit_employees);
                                    }
                                }

                                switch ($report_type) {
                                    case 'absences':
                                        if ($by_date_range) {
                                            $data = $this->AbsencesByDateRange($start_date, $end_date, $employees);
                                        } elseif ($by_period) {
                                            $data = $this->AbsencesByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                        } else {
                                            return response()->json(['message' => 'Please provide either a valid date range or month and year for the report.'], 400);
                                        }
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_of_absent_days'] > 0;
                                        });
                                        // Sort the data by total_of_absent_days in descending order
                                        $sorted_data = $sort_order === 'asc'
                                            ? $filtered_data->sortBy('total_of_absent_days')
                                            : $filtered_data->sortByDesc('total_of_absent_days');

                                        $data = $sorted_data;
                                        break;
                                    case 'tardiness':
                                        if ($by_date_range) {
                                            $data = $this->TardinessByDateRange($start_date, $end_date, $employees);
                                        } elseif ($by_period) {
                                            $data = $this->TardinessByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                        } else {
                                            return response()->json(['message' => 'Please provide either a valid range or month and year for the report'], 400);
                                        }
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_days_with_tardiness'] > 0;
                                        });
                                        // Sort the data by total_days_with_tardiness in descending order
                                        $sorted_data = $sort_order === 'asc'
                                            ? $filtered_data->sortBy('total_days_with_tardiness')
                                            : $filtered_data->sortByDesc('total_days_with_tardiness');
                                        $data = $sorted_data;
                                        break;
                                    case 'undertime':
                                        if ($by_date_range) {
                                            $data = $this->UndertimeByDateRange($start_date, $end_date, $employees);
                                        } elseif ($by_period) {
                                            $data = $this->UndertimeByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                        } else {
                                            return response()->json(['message' => 'Please provide either a valid date range or month and year for the report.'], 400);
                                        }
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_undertime_minutes'] > 0;
                                        });
                                        // Sort the data by total_of_absent_days in descending order
                                        $sorted_data = $sort_order === 'asc'
                                            ? $filtered_data->sortBy('total_undertime_minutes')
                                            : $filtered_data->sortByDesc('total_undertime_minutes');

                                        $data = $sorted_data;
                                        break;
                                    case 'perfect_attendance':
                                        $data = $this->getPerfectAttendance($first_half, $second_half, $month_of, $year_of, $employees);
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_of_absent_days'] > 0;
                                        });
                                        // Sort the data by total_of_absent_days in descending order
                                        $sorted_data = $filtered_data->sortByDesc('total_of_absent_days');

                                        $data = $sorted_data;
                                        break;
                                    default:
                                        return response()->json(['message' => 'Invalid report type']);
                                }
                                break;
                            case 'staff':
                                $division_employees = $this->retrieveEmployees('division_id', $params, $area_id);
                                $employees = $division_employees;

                                switch ($report_type) {
                                    case 'absences':
                                        if ($by_date_range) {
                                            $data = $this->AbsencesByDateRange($start_date, $end_date, $employees);
                                        } elseif ($by_period) {
                                            $data = $this->AbsencesByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                        } else {
                                            return response()->json(['message' => 'Please provide either a valid date range or month and year for the report.'], 400);
                                        }
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_of_absent_days'] > 0;
                                        });
                                        // Sort the data by total_of_absent_days in descending order
                                        $sorted_data = $sort_order === 'asc'
                                            ? $filtered_data->sortBy('total_of_absent_days')
                                            : $filtered_data->sortByDesc('total_of_absent_days');

                                        $filtered_total_employees = $sorted_data->count();
                                        $paginated_data = $filtered_total_employees > $per_page ? $sorted_data->forPage($page, $per_page)->take($limit) : $sorted_data->take($limit);
                                        break;
                                    case 'tardiness':
                                        if ($by_date_range) {
                                            $data = $this->TardinessByDateRange($start_date, $end_date, $employees);
                                        } elseif ($by_period) {
                                            $data = $this->TardinessByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                        } else {
                                            return response()->json(['message' => 'Please provide either a valid range or month and year for the report'], 400);
                                        }
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_days_with_tardiness'] > 0;
                                        });
                                        // Sort the data by total_days_with_tardiness in descending order
                                        $sorted_data = $sort_order === 'asc'
                                            ? $filtered_data->sortBy('total_days_with_tardiness')
                                            : $filtered_data->sortByDesc('total_days_with_tardiness');
                                        $filtered_total_employees = $sorted_data->count();
                                        $paginated_data = $filtered_total_employees > $per_page ? $sorted_data->forPage($page, $per_page)->take($limit) : $sorted_data->take($limit);
                                        break;
                                    case 'undertime':
                                        if ($by_date_range) {
                                            $data = $this->UndertimeByDateRange($start_date, $end_date, $employees);
                                        } elseif ($by_period) {
                                            $data = $this->UndertimeByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                        } else {
                                            return response()->json(['message' => 'Please provide either a valid date range or month and year for the report.'], 400);
                                        }
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_undertime_minutes'] > 0;
                                        });
                                        // Sort the data by total_of_absent_days in descending order
                                        $sorted_data = $sort_order === 'asc'
                                            ? $filtered_data->sortBy('total_undertime_minutes')
                                            : $filtered_data->sortByDesc('total_undertime_minutes');

                                        $filtered_total_employees = $sorted_data->count();
                                        $paginated_data = $filtered_total_employees > $per_page ? $sorted_data->forPage($page, $per_page)->take($limit) : $sorted_data->take($limit);
                                        break;
                                    case 'perfect_attendance':
                                        $data = $this->getPerfectAttendance($first_half, $second_half, $month_of, $year_of, $employees);
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_of_absent_days'] > 0;
                                        });
                                        // Sort the data by total_of_absent_days in descending order
                                        $sorted_data = $filtered_data->sortByDesc('total_of_absent_days');

                                        $filtered_total_employees = $sorted_data->count();
                                        $paginated_data = $filtered_total_employees > $per_page ? $sorted_data->forPage($page, $per_page)->take($limit) : $sorted_data->take($limit);
                                        break;
                                    default:
                                        return response()->json(['message' => 'Invalid report type']);
                                }
                                break;
                        }
                        break;
                    case 'Department':
                        switch ($area_under) {
                            case 'all':
                                $department_employees = $this->retrieveEmployees('department_id', $params, $area_id);
                                $employees = $department_employees;
                                $sections = Section::where('department_id', $area_id)->get();
                                foreach ($sections as $section) {
                                    $section_employees = $this->retrieveEmployees('section_id', $params, $section->id);
                                    $employees = $employees->merge($section_employees);
                                    $units = Unit::where('unit_id', $section->id)->get();
                                    foreach ($units as $unit) {
                                        $unit_employees = $this->retrieveEmployees('unit_id', $params, $unit->id);
                                        $employees = $employees->merge($unit_employees);
                                    }
                                }

                                switch ($report_type) {
                                    case 'absences':
                                        if ($by_date_range) {
                                            $data = $this->AbsencesByDateRange($start_date, $end_date, $employees);
                                        } elseif ($by_period) {
                                            $data = $this->AbsencesByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                        } else {
                                            return response()->json(['message' => 'Please provide either a valid date range or month and year for the report.'], 400);
                                        }
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_of_absent_days'] > 0;
                                        });
                                        // Sort the data by total_of_absent_days in descending order
                                        $sorted_data = $sort_order === 'asc'
                                            ? $filtered_data->sortBy('total_of_absent_days')
                                            : $filtered_data->sortByDesc('total_of_absent_days');

                                        $data = $sorted_data;
                                        break;
                                    case 'tardiness':
                                        if ($by_date_range) {
                                            $data = $this->TardinessByDateRange($start_date, $end_date, $employees);
                                        } elseif ($by_period) {
                                            $data = $this->TardinessByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                        } else {
                                            return response()->json(['message' => 'Please provide either a valid range or month and year for the report'], 400);
                                        }
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_days_with_tardiness'] > 0;
                                        });
                                        // Sort the data by total_days_with_tardiness in descending order
                                        $sorted_data = $sort_order === 'asc'
                                            ? $filtered_data->sortBy('total_days_with_tardiness')
                                            : $filtered_data->sortByDesc('total_days_with_tardiness');
                                        $data = $sorted_data;
                                        break;
                                    case 'undertime':
                                        if ($by_date_range) {
                                            $data = $this->UndertimeByDateRange($start_date, $end_date, $employees);
                                        } elseif ($by_period) {
                                            $data = $this->UndertimeByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                        } else {
                                            return response()->json(['message' => 'Please provide either a valid date range or month and year for the report.'], 400);
                                        }
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_undertime_minutes'] > 0;
                                        });
                                        // Sort the data by total_of_absent_days in descending order
                                        $sorted_data = $sort_order === 'asc'
                                            ? $filtered_data->sortBy('total_undertime_minutes')
                                            : $filtered_data->sortByDesc('total_undertime_minutes');

                                        $data = $sorted_data;
                                        break;
                                    case 'perfect_attendance':
                                        $data = $this->getPerfectAttendance($first_half, $second_half, $month_of, $year_of, $employees);
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_of_absent_days'] > 0;
                                        });
                                        // Sort the data by total_of_absent_days in descending order
                                        $sorted_data = $filtered_data->sortByDesc('total_of_absent_days');
                                        $data = $sorted_data;
                                        break;
                                    default:
                                        return response()->json(['message' => 'Invalid report type']);
                                }
                                break;
                            case 'staff':
                                $department_employees = $this->retrieveEmployees('department_id', $params, $area_id);
                                $employees = $department_employees;

                                switch ($report_type) {
                                    case 'absences':
                                        if ($by_date_range) {
                                            $data = $this->AbsencesByDateRange($start_date, $end_date, $employees);
                                        } elseif ($by_period) {
                                            $data = $this->AbsencesByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                        } else {
                                            return response()->json(['message' => 'Please provide either a valid date range or month and year for the report.'], 400);
                                        }
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_of_absent_days'] > 0;
                                        });
                                        // Sort the data by total_of_absent_days in descending order
                                        $sorted_data = $sort_order === 'asc'
                                            ? $filtered_data->sortBy('total_of_absent_days')
                                            : $filtered_data->sortByDesc('total_of_absent_days');


                                        $data = $sorted_data;
                                        break;
                                    case 'tardiness':
                                        if ($by_date_range) {
                                            $data = $this->TardinessByDateRange($start_date, $end_date, $employees);
                                        } elseif ($by_period) {
                                            $data = $this->TardinessByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                        } else {
                                            return response()->json(['message' => 'Please provide either a valid range or month and year for the report'], 400);
                                        }
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_days_with_tardiness'] > 0;
                                        });
                                        // Sort the data by total_days_with_tardiness in descending order
                                        // Sort the data by total_days_with_tardiness in descending order
                                        $sorted_data = $sort_order === 'asc'
                                            ? $filtered_data->sortBy('total_days_with_tardiness')
                                            : $filtered_data->sortByDesc('total_days_with_tardiness');
                                        $data = $sorted_data;
                                        break;
                                    case 'undertime':
                                        if ($by_date_range) {
                                            $data = $this->UndertimeByDateRange($start_date, $end_date, $employees);
                                        } elseif ($by_period) {
                                            $data = $this->UndertimeByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                        } else {
                                            return response()->json(['message' => 'Please provide either a valid date range or month and year for the report.'], 400);
                                        }
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_undertime_minutes'] > 0;
                                        });
                                        // Sort the data by total_of_absent_days in descending order
                                        $sorted_data = $sort_order === 'asc'
                                            ? $filtered_data->sortBy('total_undertime_minutes')
                                            : $filtered_data->sortByDesc('total_undertime_minutes');


                                        $data = $sorted_data;
                                        break;
                                    case 'perfect_attendance':
                                        $data = $this->getPerfectAttendance($first_half, $second_half, $month_of, $year_of, $employees);
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_of_absent_days'] > 0;
                                        });
                                        // Sort the data by total_of_absent_days in descending order
                                        $sorted_data = $filtered_data->sortByDesc('total_of_absent_days');


                                        $data = $sorted_data;
                                        break;
                                    default:
                                        return response()->json(['message' => 'Invalid report type']);
                                }


                                break;
                        }
                        break;
                    case 'Section':
                        switch ($area_under) {
                            case 'all':
                                $section_employees = $this->retrieveEmployees('section_id', $params, $area_id);
                                $employees = $section_employees;
                                $units = Unit::where('section_id', $area_id)->get();
                                foreach ($units as $unit) {
                                    $unit_employees = $this->retrieveEmployees('unit_id', $params, $unit->id);
                                    $employees = $employees->merge($unit_employees);
                                }

                                switch ($report_type) {
                                    case 'absences':
                                        if ($by_date_range) {
                                            $data = $this->AbsencesByDateRange($start_date, $end_date, $employees);
                                        } elseif ($by_period) {
                                            $data = $this->AbsencesByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                        } else {
                                            return response()->json(['message' => 'Please provide either a valid date range or month and year for the report.'], 400);
                                        }
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_of_absent_days'] > 0;
                                        });
                                        // Sort the data by total_of_absent_days in descending order
                                        $sorted_data = $sort_order === 'asc'
                                            ? $filtered_data->sortBy('total_of_absent_days')
                                            : $filtered_data->sortByDesc('total_of_absent_days');
                                        $data = $sorted_data;
                                        break;
                                    case 'tardiness':
                                        if ($by_date_range) {
                                            $data = $this->TardinessByDateRange($start_date, $end_date, $employees);
                                        } elseif ($by_period) {
                                            $data = $this->TardinessByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                        } else {
                                            return response()->json(['message' => 'Please provide either a valid date range or month and year for the report'], 400);
                                        }
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_days_with_tardiness'] > 0;
                                        });
                                        // Sort the data by total_of_absent_days in descending order
                                        $sorted_data = $sort_order === 'asc'
                                            ? $filtered_data->sortBy('total_of_absent_days')
                                            : $filtered_data->sortByDesc('total_of_absent_days');

                                        // Sort the data by total_days_with_tardiness in descending order
                                        $sorted_data = $sort_order === 'asc'
                                            ? $filtered_data->sortBy('total_days_with_tardiness')
                                            : $filtered_data->sortByDesc('total_days_with_tardiness');
                                        $data = $sorted_data;
                                        break;
                                    case 'undertime':
                                        if ($by_date_range) {
                                            $data = $this->UndertimeByDateRange($start_date, $end_date, $employees);
                                        } elseif ($by_period) {
                                            $data = $this->UndertimeByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                        } else {
                                            return response()->json(['message' => 'Please provide either a valid date range or month and year for the report.'], 400);
                                        }
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_undertime_minutes'] > 0;
                                        });
                                        // Sort the data by total_of_absent_days in descending order
                                        $sorted_data = $sort_order === 'asc'
                                            ? $filtered_data->sortBy('total_undertime_minutes')
                                            : $filtered_data->sortByDesc('total_undertime_minutes');
                                        $data = $sorted_data;
                                        break;
                                    case 'perfect_attendance':
                                        $data = $this->getPerfectAttendance($first_half, $second_half, $month_of, $year_of, $employees);
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_of_absent_days'] > 0;
                                        });
                                        // Sort the data by total_of_absent_days in descending order
                                        $sorted_data = $filtered_data->sortByDesc('total_of_absent_days');

                                        $data = $sorted_data;
                                        break;
                                    default:
                                        return response()->json(['message' => 'Invalid report type']);
                                }
                                break;
                            case 'staff':
                                $section_employees = $this->retrieveEmployees('section_id', $params, $area_id);
                                $employees = $section_employees;

                                switch ($report_type) {
                                    case 'absences':
                                        if ($by_date_range) {
                                            $data = $this->AbsencesByDateRange($start_date, $end_date, $employees);
                                        } elseif ($by_period) {
                                            $data = $this->AbsencesByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                        } else {
                                            return response()->json(['message' => 'Please provide either a valid date range or month and year for the report.'], 400);
                                        }
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_of_absent_days'] > 0;
                                        });
                                        // Sort the data by total_of_absent_days in descending order
                                        $sorted_data = $sort_order === 'asc'
                                            ? $filtered_data->sortBy('total_of_absent_days')
                                            : $filtered_data->sortByDesc('total_of_absent_days');
                                        $data = $sorted_data;
                                    case 'tardiness':
                                        if ($by_date_range) {
                                            $data = $this->TardinessByDateRange($start_date, $end_date, $employees);
                                        } elseif ($by_period) {
                                            $data = $this->TardinessByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                        } else {
                                            return response()->json(['message' => 'Please provide either a valid date range or month and year for the report'], 400);
                                        }
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_days_with_tardiness'] > 0;
                                        });
                                        $sorted_data = $sort_order === 'asc'
                                            ? $filtered_data->sortBy('total_days_with_tardiness') : $filtered_data->sortByDesc('total_days_with_tardiness');
                                        $data = $sorted_data;
                                        break;
                                    case 'undertime':
                                        if ($by_date_range) {
                                            $data = $this->UndertimeByDateRange($start_date, $end_date, $employees);
                                        } elseif ($by_period) {
                                            $data = $this->UndertimeByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                        } else {
                                            return response()->json(['message' => 'Please provide either a valid date range or month and year for the report.'], 400);
                                        }
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_undertime_minutes'] > 0;
                                        });
                                        // Sort the data by total_of_absent_days in descending order
                                        $sorted_data = $sort_order === 'asc'
                                            ? $filtered_data->sortBy('total_undertime_minutes')
                                            : $filtered_data->sortByDesc('total_undertime_minutes');

                                        $data = $sorted_data;
                                        break;
                                    case 'perfect_attendance':
                                        $data = $this->getPerfectAttendance($first_half, $second_half, $month_of, $year_of, $employees);
                                        $filtered_data = collect($data)->filter(function ($item) {
                                            return $item['total_of_absent_days'] > 0;
                                        });
                                        // Sort the data by total_of_absent_days in descending order
                                        $sorted_data = $filtered_data->sortByDesc('total_of_absent_days');

                                        $data = $sorted_data;
                                        break;
                                    default:
                                        return response()->json(['message' => 'Invalid report type']);
                                }
                                break;
                        }
                        break;
                    case 'Unit':
                        $unit_employees = $this->retrieveEmployees('unit_id', $params, $area_id);
                        $employees = $unit_employees;

                        switch ($report_type) {
                            case 'absences':
                                if ($by_date_range) {
                                    $data = $this->AbsencesByDateRange($start_date, $end_date, $employees);
                                } elseif ($by_period) {
                                    $data = $this->AbsencesByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                } else {
                                    return response()->json(['message' => 'Please provide either a valid date range or month and year for the report.'], 400);
                                }
                                $filtered_data = collect($data)->filter(function ($item) {
                                    return $item['total_of_absent_days'] > 0;
                                });
                                // Sort the data by total_of_absent_days in descending order
                                $sorted_data = $sort_order === 'asc'
                                    ? $filtered_data->sortBy('total_of_absent_days')
                                    : $filtered_data->sortByDesc('total_of_absent_days');

                                $data = $sorted_data;
                                break;
                            case 'tardiness':
                                if ($by_date_range) {
                                    $data = $this->TardinessByDateRange($start_date, $end_date, $employees);
                                } elseif ($by_period) {
                                    $data = $this->TardinessByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                } else {
                                    return response()->json(['message' => 'Please provide either a valid range or month and year for the report'], 400);
                                }

                                $filtered_data = collect($data)->filter(function ($item) {
                                    return $item['total_days_with_tardiness'] > 0;
                                });
                                // Sort the data by total_days_with_tardiness in descending order
                                $sorted_data = $sort_order === 'asc'
                                    ? $filtered_data->sortBy('total_days_with_tardiness')
                                    : $filtered_data->sortByDesc('total_days_with_tardiness');
                                $data = $sorted_data;
                                break;
                            case 'undertime':
                                if ($by_date_range) {
                                    $data = $this->UndertimeByDateRange($start_date, $end_date, $employees);
                                } elseif ($by_period) {
                                    $data = $this->UndertimeByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                                } else {
                                    return response()->json(['message' => 'Please provide either a valid date range or month and year for the report.'], 400);
                                }
                                $filtered_data = collect($data)->filter(function ($item) {
                                    return $item['total_undertime_minutes'] > 0;
                                });
                                // Sort the data by total_of_absent_days in descending order
                                $sorted_data = $sort_order === 'asc'
                                    ? $filtered_data->sortBy('total_undertime_minutes')
                                    : $filtered_data->sortByDesc('total_undertime_minutes');

                                $data = $sorted_data;
                                break;
                            case 'perfect_attendance':
                                $data = $this->getPerfectAttendance($first_half, $second_half, $month_of, $year_of, $employees);
                                $filtered_data = collect($data)->filter(function ($item) {
                                    return $item['total_of_absent_days'] > 0;
                                });
                                // Sort the data by total_of_absent_days in descending order
                                $sorted_data = $filtered_data->sortByDesc('total_of_absent_days');

                                $data = $sorted_data;
                                break;
                            default:
                                return response()->json(['message' => 'Invalid report type.']);
                        }

                        break;
                    default:
                        return response()->json(['message' => 'Invalid sector'], 400);
                }
            }

            $data = $data->toArray();
            return response()->json([
                'count' => COUNT($data),
                'message' => 'Successfully retrieved data.',
                'data' => array_values($data) // Ensure it's reindexed properly

            ]);
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

    public function report(Request $request)
    {
        try {
            $area_id = $request->area_id;
            $sector = $request->sector;
            $area_under = strtolower($request->area_under);
            $month_of = (int)$request->month_of;
            $year_of = (int)$request->year_of;
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $employment_type = $request->employment_type_id;
            $designation_id = $request->designation_id;
            $absent_leave_without_pay = $request->absent_leave_without_pay;
            $absent_without_official_leave = $request->absent_without_official_leave;
            $first_half = (bool)$request->first_half;
            $second_half = (bool)$request->second_half;
            $limit = $request->limit;
            $sort_order = $request->sort_order;
            $report_type = $request->report_type;

            $query = '';

            if ($sector && !$area_id) {
                return response()->json(['message' => 'Area ID is required when Sector is provided'], 400);
            }

            $employees = collect();

            // TODO test filtering if area id and sector is empty
            if (!$sector && !$area_id) {
                switch ($report_type) {
                    case 'absences':
                        $employees = DB::table('assigned_areas as a')
                            ->when($designation_id, function ($query, $designation_id) {
                                return $query->where('a.designation_id', $designation_id);
                            })
                            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
                            ->when($employment_type, function ($query, $employment_type) {
                                return $query->where('ep.employment_type_id', $employment_type);
                            })
                            ->leftJoin('user_management_den.personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
                            ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
                            ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
                            ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
                            ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
                            ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
                            ->leftJoin('daily_time_records as dtr', function ($join) use ($month_of, $year_of) {
                                $join->on('b.biometric_id', '=', 'dtr.biometric_id')
                                    ->whereMonth('dtr.dtr_date', '=', $month_of)
                                    ->whereYear('dtr.dtr_date', '=', $year_of);
                            })
                            ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
                            ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
                            ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
                            ->leftJoin('cto_applications as cto', function ($join) {
                                $join->on('ep.id', '=', 'cto.employee_profile_id')
                                    ->where('cto.status', '=', 'approved')
                                    ->whereRaw('DATE(cto.date) = DATE(sch.date)');
                            })
                            ->leftJoin('official_business_applications as oba', function ($join) {
                                $join->on('ep.id', '=', 'oba.employee_profile_id')
                                    ->where('oba.status', '=', 'approved')
                                    ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
                            })
                            ->leftJoin('leave_applications as la', function ($join) {
                                $join->on('ep.id', '=', 'la.employee_profile_id')
                                    ->where('la.status', '=', 'approved')
                                    ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
                            })
                            ->leftJoin('official_time_applications as ota', function ($join) {
                                $join->on('ep.id', '=', 'ota.employee_profile_id')
                                    ->where('ota.status', '=', 'approved')
                                    ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
                            })
                            ->select(
                                'ep.id as employee_profile_id',
                                'ep.employee_id',
                                DB::raw("CONCAT(
            pi.first_name, ' ',
            IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
            pi.last_name,
            IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
            IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
        ) as employee_name"),
                                DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_designation_name'),
                                DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_designation_code'),
                                DB::raw('COUNT(DISTINCT CASE WHEN MONTH(dtr.dtr_date) = ' . $month_of . ' AND YEAR(dtr.dtr_date) = ' . $year_of . ' THEN dtr.dtr_date END) as days_present'),
                                DB::raw("CONVERT(SUM(DISTINCT IF(MONTH(dtr.dtr_date) = $month_of AND YEAR(dtr.dtr_date) = $year_of, dtr.total_working_minutes, 0)), UNSIGNED) as total_working_minutes"),
                                DB::raw("CONVERT(SUM(DISTINCT IF(MONTH(dtr.dtr_date) = $month_of AND YEAR(dtr.dtr_date) = $year_of, dtr.overtime_minutes, 0)), UNSIGNED) as total_overtime_minutes"),
                                DB::raw("CONVERT(SUM(DISTINCT IF(MONTH(dtr.dtr_date) = $month_of AND YEAR(dtr.dtr_date) = $year_of, dtr.undertime_minutes, 0)), UNSIGNED) as total_undertime_minutes"),
                                DB::raw("COUNT(DISTINCT CASE WHEN MONTH(sch.date) = $month_of AND YEAR(sch.date) = $year_of THEN sch.date END) as scheduled_days"),
                                DB::raw("GREATEST(
            COUNT(DISTINCT CASE
                WHEN MONTH(sch.date) = $month_of AND YEAR(sch.date) = $year_of
                AND la.id IS NULL
                AND cto.id IS NULL
                AND oba.id IS NULL
                AND ota.id IS NULL
                THEN sch.date END) - COUNT(DISTINCT dtr.dtr_date), 0) as days_absent"),
                                DB::raw('CONVERT(SUM(ts.total_hours), UNSIGNED) as scheduled_total_hours'),
                                DB::raw("(SELECT COUNT(*) FROM cto_applications cto WHERE cto.employee_profile_id = ep.id AND cto.status = 'approved') as total_cto_applications"),
                                DB::raw("(SELECT COUNT(*) FROM official_business_applications oba WHERE oba.employee_profile_id = ep.id AND oba.status = 'approved') as total_official_business_applications"),
                                DB::raw("(SELECT COUNT(*) FROM leave_applications la WHERE la.employee_profile_id = ep.id AND la.status = 'approved') as total_leave_applications"),
                                DB::raw("(SELECT COUNT(*) FROM official_time_applications ota WHERE ota.employee_profile_id = ep.id AND ota.status = 'approved') as total_official_time_applications")
                            )
                            ->groupBy('ep.id', 'ep.employee_id', 'employee_name', 'employee_designation_name', 'employee_designation_code')
                            ->havingRaw('days_absent > 0')
                            ->when($sort_order, function ($query, $sort_order) {
                                if ($sort_order === 'asc') {
                                    return $query->orderByRaw('days_absent ASC');
                                } elseif ($sort_order === 'desc') {
                                    return $query->orderByRaw('days_absent DESC');
                                } else {
                                    return response()->json(['message' => 'Invalid sort order'], 400);
                                }
                            })
                            ->orderBy('employee_designation_name')->orderBy('ep.id')
                            ->when($limit, function ($query, $limit) {
                                return $query->limit($limit);
                            })
                            ->get();
                        break;
                    case 'tardiness':
                        $employees = DB::table('assigned_areas as a')
                            ->when($designation_id, function ($query, $designation_id) {
                                return $query->where('a.designation_id', $designation_id);
                            })
                            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
                            ->when($employment_type, function ($query, $employment_type) {
                                return $query->where('ep.employment_type_id', $employment_type);
                            })
                            ->leftJoin('user_management_den.personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
                            ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
                            ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
                            ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
                            ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
                            ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
                            ->leftJoin('daily_time_records as dtr', function ($join) use ($month_of, $year_of) {
                                $join->on('b.biometric_id', '=', 'dtr.biometric_id')
                                    ->whereMonth('dtr.dtr_date', '=', $month_of)
                                    ->whereYear('dtr.dtr_date', '=', $year_of);
                            })
                            ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
                            ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
                            ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
                            ->leftJoin('cto_applications as cto', function ($join) {
                                $join->on('ep.id', '=', 'cto.employee_profile_id')
                                    ->where('cto.status', '=', 'approved')
                                    ->whereRaw('DATE(cto.date) = DATE(sch.date)');
                            })
                            ->leftJoin('official_business_applications as oba', function ($join) {
                                $join->on('ep.id', '=', 'oba.employee_profile_id')
                                    ->where('oba.status', '=', 'approved')
                                    ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
                            })
                            ->leftJoin('leave_applications as la', function ($join) {
                                $join->on('ep.id', '=', 'la.employee_profile_id')
                                    ->where('la.status', '=', 'approved')
                                    ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
                            })
                            ->leftJoin('official_time_applications as ota', function ($join) {
                                $join->on('ep.id', '=', 'ota.employee_profile_id')
                                    ->where('ota.status', '=', 'approved')
                                    ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
                            })
                            ->select(
                                'ep.id as employee_profile_id',
                                'ep.employee_id',
                                DB::raw("CONCAT(
            pi.first_name, ' ',
            IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
            pi.last_name,
            IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
            IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
        ) as employee_name"),
                                DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_designation_name'),
                                DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_designation_code'),
                                DB::raw('COUNT(DISTINCT CASE WHEN MONTH(dtr.dtr_date) = ' . $month_of . ' AND YEAR(dtr.dtr_date) = ' . $year_of . ' THEN dtr.dtr_date END) as days_present'),
                                DB::raw("CONVERT(SUM(DISTINCT IF(MONTH(dtr.dtr_date) = $month_of AND YEAR(dtr.dtr_date) = $year_of, dtr.total_working_minutes, 0)), UNSIGNED) as total_working_minutes"),
                                DB::raw("CONVERT(SUM(DISTINCT IF(MONTH(dtr.dtr_date) = $month_of AND YEAR(dtr.dtr_date) = $year_of, dtr.overtime_minutes, 0)), UNSIGNED) as total_overtime_minutes"),
                                DB::raw("CONVERT(SUM(DISTINCT IF(MONTH(dtr.dtr_date) = $month_of AND YEAR(dtr.dtr_date) = $year_of, dtr.undertime_minutes, 0)), UNSIGNED) as total_undertime_minutes"),
                                DB::raw("COUNT(DISTINCT CASE WHEN MONTH(sch.date) = $month_of AND YEAR(sch.date) = $year_of THEN sch.date END) as scheduled_days"),
                                DB::raw("COUNT(DISTINCT CASE
                                                WHEN dtr.first_in > ts.first_in OR (dtr.second_in IS NOT NULL AND dtr.second_in > ts.second_in)
                                                THEN dtr.dtr_date
                                            END) as days_with_tardiness"),
                                DB::raw('CONVERT(SUM(ts.total_hours), UNSIGNED) as scheduled_total_hours'),
                                DB::raw("(SELECT COUNT(*) FROM cto_applications cto WHERE cto.employee_profile_id = ep.id AND cto.status = 'approved') as total_cto_applications"),
                                DB::raw("(SELECT COUNT(*) FROM official_business_applications oba WHERE oba.employee_profile_id = ep.id AND oba.status = 'approved') as total_official_business_applications"),
                                DB::raw("(SELECT COUNT(*) FROM leave_applications la WHERE la.employee_profile_id = ep.id AND la.status = 'approved') as total_leave_applications"),
                                DB::raw("(SELECT COUNT(*) FROM official_time_applications ota WHERE ota.employee_profile_id = ep.id AND ota.status = 'approved') as total_official_time_applications")
                            )
                            ->groupBy('ep.id', 'ep.employee_id', 'employee_name', 'employee_designation_name', 'employee_designation_code')
                            ->havingRaw('days_with_tardiness > 0')
                            ->when($sort_order, function ($query, $sort_order) {
                                if ($sort_order === 'asc') {
                                    return $query->orderByRaw('days_with_tardiness ASC');
                                } elseif ($sort_order === 'desc') {
                                    return $query->orderByRaw('days_with_tardiness DESC');
                                } else {
                                    return response()->json(['message' => 'Invalid sort order'], 400);
                                }
                            })
                            ->orderBy('employee_designation_name')->orderBy('ep.id')
                            ->when($limit, function ($query, $limit) {
                                return $query->limit($limit);
                            })
                            ->get();
                        break;
                    case 'undertime':
                        $employees = DB::table('assigned_areas as a')
                            ->when($designation_id, function ($query, $designation_id) {
                                return $query->where('a.designation_id', $designation_id);
                            })
                            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
                            ->when($employment_type, function ($query, $employment_type) {
                                return $query->where('ep.employment_type_id', $employment_type);
                            })
                            ->leftJoin('user_management_den.personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
                            ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
                            ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
                            ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
                            ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
                            ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
                            ->leftJoin('daily_time_records as dtr', function ($join) use ($month_of, $year_of) {
                                $join->on('b.biometric_id', '=', 'dtr.biometric_id')
                                    ->whereMonth('dtr.dtr_date', '=', $month_of)
                                    ->whereYear('dtr.dtr_date', '=', $year_of);
                            })
                            ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
                            ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
                            ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
                            ->leftJoin('cto_applications as cto', function ($join) {
                                $join->on('ep.id', '=', 'cto.employee_profile_id')
                                    ->where('cto.status', '=', 'approved')
                                    ->whereRaw('DATE(cto.date) = DATE(sch.date)');
                            })
                            ->leftJoin('official_business_applications as oba', function ($join) {
                                $join->on('ep.id', '=', 'oba.employee_profile_id')
                                    ->where('oba.status', '=', 'approved')
                                    ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
                            })
                            ->leftJoin('leave_applications as la', function ($join) {
                                $join->on('ep.id', '=', 'la.employee_profile_id')
                                    ->where('la.status', '=', 'approved')
                                    ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
                            })
                            ->leftJoin('official_time_applications as ota', function ($join) {
                                $join->on('ep.id', '=', 'ota.employee_profile_id')
                                    ->where('ota.status', '=', 'approved')
                                    ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
                            })
                            ->select(
                                'ep.id as employee_profile_id',
                                'ep.employee_id',
                                DB::raw("CONCAT(
            pi.first_name, ' ',
            IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
            pi.last_name,
            IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
            IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
        ) as employee_name"),
                                DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_designation_name'),
                                DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_designation_code'),
                                DB::raw('COUNT(DISTINCT CASE WHEN MONTH(dtr.dtr_date) = ' . $month_of . ' AND YEAR(dtr.dtr_date) = ' . $year_of . ' THEN dtr.dtr_date END) as days_present'),
                                DB::raw("CONVERT(SUM(DISTINCT IF(MONTH(dtr.dtr_date) = $month_of AND YEAR(dtr.dtr_date) = $year_of, dtr.total_working_minutes, 0)), UNSIGNED) as total_working_minutes"),
                                DB::raw("CONVERT(SUM(DISTINCT IF(MONTH(dtr.dtr_date) = $month_of AND YEAR(dtr.dtr_date) = $year_of, dtr.overtime_minutes, 0)), UNSIGNED) as total_overtime_minutes"),
                                DB::raw("CONVERT(SUM(DISTINCT IF(MONTH(dtr.dtr_date) = $month_of AND YEAR(dtr.dtr_date) = $year_of, dtr.undertime_minutes, 0)), UNSIGNED) as total_undertime_minutes"),
                                DB::raw("COUNT(DISTINCT CASE WHEN MONTH(sch.date) = $month_of AND YEAR(sch.date) = $year_of THEN sch.date END) as scheduled_days"),
                                DB::raw('CONVERT(SUM(ts.total_hours), UNSIGNED) as scheduled_total_hours'),
                                DB::raw("(SELECT COUNT(*) FROM cto_applications cto WHERE cto.employee_profile_id = ep.id AND cto.status = 'approved') as total_cto_applications"),
                                DB::raw("(SELECT COUNT(*) FROM official_business_applications oba WHERE oba.employee_profile_id = ep.id AND oba.status = 'approved') as total_official_business_applications"),
                                DB::raw("(SELECT COUNT(*) FROM leave_applications la WHERE la.employee_profile_id = ep.id AND la.status = 'approved') as total_leave_applications"),
                                DB::raw("(SELECT COUNT(*) FROM official_time_applications ota WHERE ota.employee_profile_id = ep.id AND ota.status = 'approved') as total_official_time_applications")
                            )
                            ->groupBy('ep.id', 'ep.employee_id', 'employee_name', 'employee_designation_name', 'employee_designation_code')
                            ->havingRaw('total_undertime_minutes > 0')
                            ->when($sort_order, function ($query, $sort_order) {
                                if ($sort_order === 'asc') {
                                    return $query->orderByRaw('total_undertime_minutes ASC');
                                } elseif ($sort_order === 'desc') {
                                    return $query->orderByRaw('total_undertime_minutes DESC');
                                } else {
                                    return response()->json(['message' => 'Invalid sort order'], 400);
                                }
                            })
                            ->orderBy('employee_designation_name')->orderBy('ep.id')
                            ->when($limit, function ($query, $limit) {
                                return $query->limit($limit);
                            })
                            ->get();
                        break;
                    case 'perfect':
                        $employees = DB::table('assigned_areas as a')
                            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
                            ->when($designation_id, function ($query, $designation_id) {
                                return $query->where('a.designation_id', $designation_id);
                            })
                            ->when($employment_type, function ($query, $employment_type) {
                                return $query->where('ep.employment_type_id', $employment_type);
                            })
                            ->leftJoin('user_management_den.personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
                            ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
                            ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
                            ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
                            ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
                            ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
                            ->leftJoin('daily_time_records as dtr', function ($join) use ($month_of, $year_of) {
                                $join->on('b.biometric_id', '=', 'dtr.biometric_id')
                                    ->whereMonth('dtr.dtr_date', '=', $month_of)
                                    ->whereYear('dtr.dtr_date', '=', $year_of);
                            })
                            ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
                            ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
                            ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
                            ->leftJoin('cto_applications as cto', function ($join) {
                                $join->on('ep.id', '=', 'cto.employee_profile_id')
                                    ->where('cto.status', '=', 'approved')
                                    ->whereRaw('DATE(cto.date) = DATE(sch.date)');
                            })
                            ->leftJoin('official_business_applications as oba', function ($join) {
                                $join->on('ep.id', '=', 'oba.employee_profile_id')
                                    ->where('oba.status', '=', 'approved')
                                    ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
                            })
                            ->leftJoin('leave_applications as la', function ($join) {
                                $join->on('ep.id', '=', 'la.employee_profile_id')
                                    ->where('la.status', '=', 'approved')
                                    ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
                            })
                            ->leftJoin('official_time_applications as ota', function ($join) {
                                $join->on('ep.id', '=', 'ota.employee_profile_id')
                                    ->where('ota.status', '=', 'approved')
                                    ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
                            })
                            ->select(
                                'ep.id as employee_profile_id',
                                'ep.employee_id',
                                DB::raw("CONCAT(
            pi.first_name, ' ',
            IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
            pi.last_name,
            IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
            IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
        ) as employee_name"),
                                DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_designation_name'),
                                DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_designation_code')
                            )
                            ->groupBy('ep.id', 'ep.employee_id', 'employee_name', 'employee_designation_name', 'employee_designation_code')
                            ->havingRaw('
        SUM(CASE WHEN dtr.first_in > ts.first_in OR (dtr.second_in IS NOT NULL AND dtr.second_in > ts.second_in) THEN 1 ELSE 0 END) = 0 AND
        SUM(CASE WHEN dtr.undertime_minutes > 0 THEN 1 ELSE 0 END) = 0 AND
        COUNT(DISTINCT sch.date) = COUNT(DISTINCT dtr.dtr_date)
    ')
                            ->orderBy('employee_designation_name')
                            ->orderBy('ep.id')
                            ->get();

                        break;
                }
            } else {
                switch ($sector) {
                    case 'division':
                        switch ($report_type) {
                            case 'absences':
                                switch ($area_under) {
                                    case 'all':
                                        try {
                                            $employees = DB::table('assigned_areas as a')
                                                ->when($designation_id, function ($query, $designation_id) {
                                                    return $query->where('a.designation_id', $designation_id);
                                                })
                                                ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
                                                ->when($employment_type, function ($query, $employment_type) {
                                                    return $query->where('ep.employment_type_id', $employment_type);
                                                })
                                                ->leftJoin('user_management_den.personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
                                                ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
                                                ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
                                                ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
                                                ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
                                                ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
                                                ->leftJoin('daily_time_records as dtr', function ($join) use ($month_of, $year_of) {
                                                    $join->on('b.biometric_id', '=', 'dtr.biometric_id')
                                                        ->whereMonth('dtr.dtr_date', '=', $month_of)
                                                        ->whereYear('dtr.dtr_date', '=', $year_of);
                                                })
                                                ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
                                                ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
                                                ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
                                                ->leftJoin('cto_applications as cto', function ($join) {
                                                    $join->on('ep.id', '=', 'cto.employee_profile_id')
                                                        ->where('cto.status', '=', 'approved')
                                                        ->whereRaw('DATE(cto.date) = DATE(sch.date)');
                                                })
                                                ->leftJoin('official_business_applications as oba', function ($join) {
                                                    $join->on('ep.id', '=', 'oba.employee_profile_id')
                                                        ->where('oba.status', '=', 'approved')
                                                        ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
                                                })
                                                ->leftJoin('leave_applications as la', function ($join) {
                                                    $join->on('ep.id', '=', 'la.employee_profile_id')
                                                        ->where('la.status', '=', 'approved')
                                                        ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
                                                })
                                                ->leftJoin('official_time_applications as ota', function ($join) {
                                                    $join . on('ep.id', '=', 'ota.employee_profile_id')
                                                        ->where('ota.status', '=', 'approved')
                                                        ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
                                                })
                                                ->where(function ($query) use ($area_id) {
                                                    $query->where('a.division_id', $area_id)
                                                        ->orWhereIn('a.department_id', function ($query) use ($area_id) {
                                                            $query->select('id')
                                                                ->from('departments')
                                                                ->where('division_id', $area_id);
                                                        })
                                                        ->orWhereIn('a.section_id', function ($query) use ($area_id) {
                                                            $query->select('id')
                                                                ->from('sections')
                                                                ->where('division_id', $area_id)
                                                                ->orWhereIn('department_id', function ($query) use ($area_id) {
                                                                    $query->select('id')
                                                                        ->from('departments')
                                                                        ->where('division_id', $area_id);
                                                                });
                                                        })
                                                        ->orWhereIn('a.unit_id', function ($query) use ($area_id) {
                                                            $query->select('id')
                                                                ->from('units')
                                                                ->whereIn('section_id', function ($query) use ($area_id) {
                                                                    $query->select('id')
                                                                        ->from('sections')
                                                                        ->where('division_id', $area_id)
                                                                        ->orWhereIn('department_id', function ($query) use ($area_id) {
                                                                            $query->select('id')
                                                                                ->from('departments')
                                                                                ->where('division_id', $area_id);
                                                                        });
                                                                });
                                                        });
                                                })
                                                ->whereNotNull('ep.biometric_id') // Ensure the employee has biometric data
                                                ->select(
                                                    'ep.id as employee_profile_id',
                                                    'ep.employee_id',
                                                    DB::raw("CONCAT(
                pi.first_name, ' ',
                IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
                pi.last_name,
                IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
                IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
            ) as employee_name"),
                                                    DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_designation_name'),
                                                    DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_designation_code'),
                                                    DB::raw('COUNT(DISTINCT CASE WHEN MONTH(dtr.dtr_date) = ' . $month_of . ' AND YEAR(dtr.dtr_date) = ' . $year_of . ' THEN dtr.dtr_date END) as days_present'),
                                                    DB::raw("CONVERT(SUM(DISTINCT IF(MONTH(dtr.dtr_date) = $month_of AND YEAR(dtr.dtr_date) = $year_of, dtr.total_working_minutes, 0)), UNSIGNED) as total_working_minutes"),
                                                    DB::raw("CONVERT(SUM(DISTINCT IF(MONTH(dtr.dtr_date) = $month_of AND YEAR(dtr.dtr_date) = $year_of, dtr.overtime_minutes, 0)), UNSIGNED) as total_overtime_minutes"),
                                                    DB::raw("CONVERT(SUM(DISTINCT IF(MONTH(dtr.dtr_date) = $month_of AND YEAR(dtr.dtr_date) = $year_of, dtr.undertime_minutes, 0)), UNSIGNED) as total_undertime_minutes"),
                                                    DB::raw("COUNT(DISTINCT CASE WHEN MONTH(sch.date) = $month_of AND YEAR(sch.date) = $year_of THEN sch.date END) as scheduled_days"),
                                                    DB::raw("GREATEST(
                COUNT(DISTINCT CASE
                    WHEN MONTH(sch.date) = $month_of AND YEAR(sch.date) = $year_of
                    AND la.id IS NULL
                    AND cto.id IS NULL
                    AND oba.id IS NULL
                    AND ota.id IS NULL
                    THEN sch.date END) - COUNT(DISTINCT dtr.dtr_date), 0) as days_absent"),
                                                    DB::raw('CONVERT(SUM(ts.total_hours), UNSIGNED) as scheduled_total_hours'),
                                                    DB::raw("(SELECT COUNT(*) FROM cto_applications cto WHERE cto.employee_profile_id = ep.id AND cto.status = 'approved') as total_cto_applications"),
                                                    DB::raw("(SELECT COUNT(*) FROM official_business_applications oba WHERE oba.employee_profile_id = ep.id AND oba.status = 'approved') as total_official_business_applications"),
                                                    DB::raw("(SELECT COUNT(*) FROM leave_applications la WHERE la.employee_profile_id = ep.id AND la.status = 'approved') as total_leave_applications"),
                                                    DB::raw("(SELECT COUNT(*) FROM official_time_applications ota WHERE ota.employee_profile_id = ep.id AND ota.status = 'approved') as total_official_time_applications")
                                                )
                                                ->groupBy('ep.id', 'ep.employee_id', 'employee_name', 'employee_designation_name', 'employee_designation_code')
                                                ->havingRaw('days_absent > 0')
                                                ->when($sort_order, function ($query, $sort_order) {
                                                    if ($sort_order === 'asc') {
                                                        return $query->orderByRaw('days_absent ASC');
                                                    } elseif ($sort_order === 'desc') {
                                                        return $query->orderByRaw('days_absent DESC');
                                                    } else {
                                                        return response()->json(['message' => 'Invalid sort order'], 400);
                                                    }
                                                })
                                                ->orderBy('employee_designation_name')->orderBy('ep.id')
                                                ->when($limit, function ($query, $limit) {
                                                    return $query->limit($limit);
                                                })
                                                ->get();
                                        } catch (\Throwable $e) {
                                            return response()->json(
                                                [
                                                    'error' => 'An error occurred while retrieving employees data', 'message' => $e->getMessage()
                                                ],
                                                ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
                                        }
                                        break;
                                    case 'under':
                                        try {
                                            $employees = DB::table('assigned_areas as a')
                                                ->when($designation_id, function ($query, $designation_id) {
                                                    return $query->where('a.designation_id', $designation_id);
                                                })
                                                ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
                                                ->when($employment_type, function ($query, $employment_type) {
                                                    return $query->where('ep.employment_type_id', $employment_type);
                                                })
                                                ->leftJoin('user_management_den.personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
                                                ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
                                                ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
                                                ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
                                                ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
                                                ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
                                                ->leftJoin('daily_time_records as dtr', function ($join) use ($month_of, $year_of) {
                                                    $join->on('b.biometric_id', '=', 'dtr.biometric_id')
                                                        ->whereMonth('dtr.dtr_date', '=', $month_of)
                                                        ->whereYear('dtr.dtr_date', '=', $year_of);
                                                })
                                                ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
                                                ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
                                                ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
                                                ->leftJoin('cto_applications as cto', function ($join) {
                                                    $join->on('ep.id', '=', 'cto.employee_profile_id')
                                                        ->where('cto.status', '=', 'approved')
                                                        ->whereRaw('DATE(cto.date) = DATE(sch.date)');
                                                })
                                                ->leftJoin('official_business_applications as oba', function ($join) {
                                                    $join->on('ep.id', '=', 'oba.employee_profile_id')
                                                        ->where('oba.status', '=', 'approved')
                                                        ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
                                                })
                                                ->leftJoin('leave_applications as la', function ($join) {
                                                    $join->on('ep.id', '=', 'la.employee_profile_id')
                                                        ->where('la.status', '=', 'approved')
                                                        ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
                                                })
                                                ->leftJoin('official_time_applications as ota', function ($join) {
                                                    $join->on('ep.id', '=', 'ota.employee_profile_id')
                                                        ->where('ota.status', '=', 'approved')
                                                        ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
                                                })
                                                ->where(function ($query) use ($area_id) {
                                                    $query->where('a.division_id', $area_id);
                                                })
                                                ->whereNotNull('ep.biometric_id') // Ensure the employee has biometric data
                                                ->select(
                                                    'ep.id as employee_profile_id',
                                                    'ep.employee_id',
                                                    DB::raw("CONCAT(
            pi.first_name, ' ',
            IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
            pi.last_name,
            IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
            IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
        ) as employee_name"),
                                                    DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_designation_name'),
                                                    DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_designation_code'),
                                                    DB::raw('COUNT(DISTINCT CASE WHEN MONTH(dtr.dtr_date) = ' . $month_of . ' AND YEAR(dtr.dtr_date) = ' . $year_of . ' THEN dtr.dtr_date END) as days_present'),
                                                    DB::raw("CONVERT(SUM(DISTINCT IF(MONTH(dtr.dtr_date) = $month_of AND YEAR(dtr.dtr_date) = $year_of, dtr.total_working_minutes, 0)), UNSIGNED) as total_working_minutes"),
                                                    DB::raw("CONVERT(SUM(DISTINCT IF(MONTH(dtr.dtr_date) = $month_of AND YEAR(dtr.dtr_date) = $year_of, dtr.overtime_minutes, 0)), UNSIGNED) as total_overtime_minutes"),
                                                    DB::raw("CONVERT(SUM(DISTINCT IF(MONTH(dtr.dtr_date) = $month_of AND YEAR(dtr.dtr_date) = $year_of, dtr.undertime_minutes, 0)), UNSIGNED) as total_undertime_minutes"),
                                                    DB::raw("COUNT(DISTINCT CASE WHEN MONTH(sch.date) = $month_of AND YEAR(sch.date) = $year_of THEN sch.date END) as scheduled_days"),
                                                    DB::raw("GREATEST(
            COUNT(DISTINCT CASE
                WHEN MONTH(sch.date) = $month_of AND YEAR(sch.date) = $year_of
                AND la.id IS NULL
                AND cto.id IS NULL
                AND oba.id IS NULL
                AND ota.id IS NULL
                THEN sch.date END) - COUNT(DISTINCT dtr.dtr_date), 0) as days_absent"),
                                                    DB::raw('CONVERT(SUM(ts.total_hours), UNSIGNED) as scheduled_total_hours'),
                                                    DB::raw("(SELECT COUNT(*) FROM cto_applications cto WHERE cto.employee_profile_id = ep.id AND cto.status = 'approved') as total_cto_applications"),
                                                    DB::raw("(SELECT COUNT(*) FROM official_business_applications oba WHERE oba.employee_profile_id = ep.id AND oba.status = 'approved') as total_official_business_applications"),
                                                    DB::raw("(SELECT COUNT(*) FROM leave_applications la WHERE la.employee_profile_id = ep.id AND la.status = 'approved') as total_leave_applications"),
                                                    DB::raw("(SELECT COUNT(*) FROM official_time_applications ota WHERE ota.employee_profile_id = ep.id AND ota.status = 'approved') as total_official_time_applications")
                                                )
                                                ->groupBy('ep.id', 'ep.employee_id', 'employee_name', 'employee_designation_name', 'employee_designation_code')
                                                ->havingRaw('days_absent > 0')
                                                ->when($sort_order, function ($query, $sort_order) {
                                                    if ($sort_order === 'asc') {
                                                        return $query->orderByRaw('days_absent ASC');
                                                    } elseif ($sort_order === 'desc') {
                                                        return $query->orderByRaw('days_absent DESC');
                                                    } else {
                                                        return response()->json(['message' => 'Invalid sort order'], 400);
                                                    }
                                                })
                                                ->orderBy('employee_designation_name')->orderBy('ep.id')
                                                ->when($limit, function ($query, $limit) {
                                                    return $query->limit($limit);
                                                })
                                                ->get();
                                        } catch (\Throwable $e) {
                                            return response()->json(
                                                [
                                                    'error' => 'An error occurred while retrieving employees data', 'message' => $e->getMessage()
                                                ],
                                                ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
                                        }
                                        break;
                                    default:
                                        return response()->json(['message' => 'Invalid Under selected area'], 400);
                                }
                                break;
                            case 'tardiness':
                                break;
                            case 'undertime':
                                break;
                            case 'perfect':
                                break;
                        }
                        break;
                    case 'department':
                        // TODO implement the filtering for department
                            //  TODO change the query conditions that will filter the employees under department
                        break;
                    case 'section':
                        // TODO implement the filtering for section
                        //  TODO change the query conditions that will filter the employees under section
                        break;
                    case 'unit':
                        // TODO implement the filtering for unit
                        //  TODO change the query conditions that will filter the employees under unit
                        break;
                    default:
                        return response()->json(['message' => 'Unknown sector.'], 400);
                }
            }

//            switch ($sector) {
//                case "division":
//                    $divisionId = $area_id;
//                    $employees = DB::table('assigned_areas as a')
//                        ->select(
//                            'ep.id',
//                            'ep.biometric_id as employee_biometric_id',
//                            'ep.employee_id',
//                            DB::raw("
//                                            CONCAT(
//                                                pi.first_name, ' ',
//                                                IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
//                                                pi.last_name,
//                                                IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
//                                                IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
//                                            ) as employee_name
//                                        "),
//                            'ept.name as employment_type',
//                            DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_designation_name'),
//                            DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_designation_code'),
//                        )
//                        ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
//                        ->leftJoin('user_management_den.personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
//                        ->leftJoin('user_management_den.employment_types as ept', 'ep.employment_type_id', '=', 'ept.id')
//                        ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
//                        ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
//                        ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
//                        ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
//                        ->where(function ($query) use ($divisionId) {
//                            $query->where('a.division_id', $divisionId)
//                                ->orWhereIn('a.department_id', function ($query) use ($divisionId) {
//                                    $query->select('id')
//                                        ->from('departments')
//                                        ->where('division_id', $divisionId);
//                                })
//                                ->orWhereIn('a.section_id', function ($query) use ($divisionId) {
//                                    $query->select('id')
//                                        ->from('sections')
//                                        ->where('division_id', $divisionId)
//                                        ->orWhereIn('department_id', function ($query) use ($divisionId) {
//                                            $query->select('id')
//                                                ->from('departments')
//                                                ->where('division_id', $divisionId);
//                                        });
//                                })
//                                ->orWhereIn('a.unit_id', function ($query) use ($divisionId) {
//                                    $query->select('id')
//                                        ->from('units')
//                                        ->whereIn('section_id', function ($query) use ($divisionId) {
//                                            $query->select('id')
//                                                ->from('sections')
//                                                ->where('division_id', $divisionId)
//                                                ->orWhereIn('department_id', function ($query) use ($divisionId) {
//                                                    $query->select('id')
//                                                        ->from('departments')
//                                                        ->where('division_id', $divisionId);
//                                                });
//                                        });
//                                });
//                        })
//                        ->whereNotNull('ep.biometric_id') // Ensure the employee has biometric data
//                        ->where('ep.employee_id', 2024061050)
//                        ->orderBy('employee_designation_name')
//                        ->orderBy('ep.id')
//                        ->get();
//
//
//                    break;
//                case "department":
//                    break;
//                case "section":
//                    break;
//                case "unit":
//                    break;
//                default:
//            }
            return response()->json([
                'count' => COUNT($employees),
                'message' => 'Successfully retrieved data.',
                'data' => $employees

            ]);
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
