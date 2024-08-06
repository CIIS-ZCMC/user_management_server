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
use App\Models\Schedule;
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

    private function retrieveEmployees($key, $params)
    {
        $current_date = Carbon::now()->toDateString(); // Get current date in YYYY-MM-DD format
        $area_id = $params['area_id'];
        $month_of = $params['month_of'];
        $year_of = $params['year_of'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $employment_type = $params['employment_type'];
        $designation_id = $params['designation_id'];
        $absent_leave_without_pay = $params['absent_leave_without_pay'];
        $absent_without_official_leave = $params['absent_without_official_leave'];
        $first_half = $params['first_half'];
        $second_half = $params['second_half'];
        $limit = $params['limit'];
        $sort_order = $params['sort_order'];

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

    private function AbsencesByPeriod($first_half, $second_half, $month_of, $year_of, $employees)
    {

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
                        $total_Month_WorkingMinutes += $recordDTR[0]->total_working_minutes;
                        $total_Month_Overtime += $recordDTR[0]->overtime_minutes;
                        $total_Month_Undertime += $recordDTR[0]->undertime_minutes;
                        $missedHours = round((480 - $recordDTR[0]->total_working_minutes) / 60);
                        $total_Month_Hour_Missed += $missedHours;
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
                'total_hours_missed' =>       $total_Month_Hour_Missed,
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

    public function report(Request $request)
    {
        try {
            $area_id = $request->area_id;
            $sector = ucfirst($request->sector);
            $area_under = strtolower($request->area_under);
            $month_of = (int) $request->month_of;
            $year_of = (int) $request->year_of;
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $employment_type = $request->employment_type_id;
            $designation_id = $request->designation_id;
            $absent_leave_without_pay = $request->absent_leave_without_pay;
            $absent_without_official_leave = $request->absent_without_official_leave;
            $first_half = (bool) $request->first_half;
            $second_half = (bool) $request->second_half;
            $limit = $request->limit;
            $sort_order = $request->sort_order;
            $report_type = $request->report_type;

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
                'first_half' => $first_half,
                'second_half' => $second_half,
                'limit' => $limit,
                'sort_order' => $sort_order,
            ];

            $data = collect();
            $employees = collect();
            $division_employees = collect();
            $department_employees = collect();
            $section_employees = collect();
            $unit_employees = collect();

            switch ($sector) {
                case 'Division':
                    switch ($area_under) {
                        case 'all':
                            $division_employees = $this->retrieveEmployees('division_id', $area_id);
                            $employees = $division_employees;
                            $departments = Department::where('division_id', $area_id)->get();
                            foreach ($departments as $department) {
                                $department_employees = $this->retrieveEmployees('department_id', $department->id);
                                $employees = $employees->merge($department_employees);
                                $sections = Section::where('department_id', $department->id);
                                foreach ($sections as $section) {
                                    $section_employees = $this->retrieveEmployees('section_id', $section->id);
                                    $employees = $employees->merge($section_employees);
                                    $units = Unit::where('section_id', $section->id)->get();
                                    foreach ($units as $unit) {
                                        $unit_employees = $this->retrieveEmployees('unit_id', $unit->id);
                                        $employees = $employees->merge($unit_employees);
                                    }
                                }
                            }
                            $sections = Section::where('division_id', $area_id)->whereNull('department_id')->get();
                            foreach ($sections as $section) {
                                $section_employees = $this->retrieveEmployees('section_id', $section->id);
                                $employees = $employees->merge($section_employees);
                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $unit_employees = $this->retrieveEmployees('unit_id', $unit->id);
                                    $employees = $employees->merge($unit_employees);
                                }
                            }
                            break;
                        case 'staff':
                            $division_employees = $this->retrieveEmployees('division_id', $area_id);
                            $employees = $division_employees;
                            break;
                    }
                    break;
                case 'Department':
                    switch ($area_under) {
                        case 'all':
                            $department_employees = $this->retrieveEmployees('department_id', $area_id);
                            $employees = $department_employees;
                            $sections = Section::where('department_id', $area_id)->get();
                            foreach ($sections as $section) {
                                $section_employees = $this->retrieveEmployees('section_id', $section->id);
                                $employees = $employees->merge($section_employees);
                                $units = Unit::where('unit_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $unit_employees = $this->retrieveEmployees('unit_id', $unit->id);
                                    $employees = $employees->merge($unit_employees);
                                }
                            }
                            break;
                        case 'staff':
                            $department_employees = $this->retrieveEmployees('department_id', $area_id);
                            $employees = $department_employees;
                            break;
                    }
                    break;
                case 'Section':
                    switch ($area_under) {
                        case 'all':
                            $section_employees = $this->retrieveEmployees('section_id', $area_id);
                            $employees = $section_employees;
                            $units = Unit::where('section_id', $area_id)->get();
                            foreach ($units as $unit) {
                                $unit_employees = $this->retrieveEmployees('unit_id', $unit->id);
                                $employees = $employees->merge($unit_employees);
                            }
                            $data = $this->AbsencesByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                            break;
                        case 'staff':
                            $section_employees = $this->retrieveEmployees('section_id', $area_id);
                            $employees = $section_employees;
                            $data = $this->AbsencesByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                            break;
                    }
                    break;
                case 'Unit':
                    $unit_employees = $this->retrieveEmployees('unit_id', $params);
                    $employees = $unit_employees;
                    switch ($report_type) {
                        case 'absences':
                            $data = $this->AbsencesByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                            break;
                        case 'tardiness':
                            break;
                        case 'undertime':
                            break;
                        default:
                            return response()->json(['message' => 'Invalid report type.']);
                    }
                    break;
                default:
                    $employees = $this->retrieveAllEmployees();
                    switch ($report_type) {
                        case 'absences':
                            $data = $this->AbsencesByPeriod($first_half, $second_half, $month_of, $year_of, $employees);
                            break;
                        case 'tardiness':
                            break;
                        case 'undertime':
                            break;
                        default:
                            return response()->json(['message' => 'Invalid report type.']);
                    }
            }

            return response()->json([
                'count' => count($data),
                'data' => $data,
                'message' => 'Successfully retrieved data.'
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
