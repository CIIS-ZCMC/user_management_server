<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Support\Facades\DB;
use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Helpers\ReportHelpers;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\AssignArea;
use App\Models\DailyTimeRecords;
use App\Models\Division;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\EmployeeSchedule;
use App\Models\LeaveType;
use App\Models\Section;
use App\Models\Unit;
use PhpParser\Node\Expr\Assign;;


/**
 * Class AttendanceReportController
 * @package App\Http\Controllers\Reports
 * 
 * Controller for handling attendance reports.
 */
class AttendanceReportController extends Controller
{
    private $CONTROLLER_NAME = "Attendance Reports";

    private function Attendance($year_of, $month_of, $i, $record_dtr)
    {
        return [
            'dateRecord' => date('Y-m-d', strtotime($year_of . '-' . $month_of . '-' . $i)),
            'firstin' => $record_dtr[0]->first_in,
            'firstout' => $record_dtr[0]->first_out,
            'secondin' => $record_dtr[0]->second_in,
            'secondout' => $record_dtr[0]->second_out,
            'total_working_minutes' => (int)$record_dtr[0]->total_working_minutes,
            'undertime_minutes' => (int)$record_dtr[0]->undertime_minutes,
            'overall_minutes_rendered' => (int) $record_dtr[0]->overall_minutes_rendered,
            'total_minutes_reg' => (int)$record_dtr[0]->total_minutes_reg
        ];
    }

    /**
     * Filters attendance records based on given criteria.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filterAttendanceTardiness(Request $request)
    {
        try {
            $result = [];
            // Get filters from the request
            $area_id = $request->area_id;
            $area_under = $request->area_under;
            $sector = ucfirst($request->sector);
            $employment_type = $request->employment_type_id;
            $month_of = (int) $request->month_of;
            $year_of = (int) $request->year_of;
            $period_type = $request->period_type; // quarterly or monthly
            $limit = $request->limit; // default limit is 100


            $area = collect();

            switch ($sector) {
                case 'Division':
                    $divisions = Division::where('id', $area_id)->get();
                    $area = $area->merge($divisions->map(function ($item) {
                        $item->sectors = 'Division';
                        return $item;
                    }));
                    foreach ($divisions as $div) {
                        $departments = Department::where('division_id', $div->id)->get();
                        $area = $area->merge($departments->map(function ($item) {
                            $item->sectors = 'Department';
                            return $item;
                        }));

                        $sections = Section::where('division_id', $div->id)->get();
                        $area = $area->merge($sections->map(function ($item) {
                            $item->sectors = 'Section';
                            return $item;
                        }));

                        foreach ($sections as $sec) {
                            $units = Unit::where('section_id', $sec->id)->get();
                            $area = $area->merge($units->map(function ($item) {
                                $item->sectors = 'Unit';
                                return $item;
                            }));
                        }
                    }
                    break;
                case 'Department':
                    $departments = Department::where('id', $area_id)->get();
                    foreach ($departments as $dept) {
                        $divisions = Division::where('id', $dept->division_id)->get();
                        $area = $area->merge($divisions->map(function ($item) {
                            $item->sectors = 'Division';
                            return $item;
                        }));

                        $department = Department::where('division_id', $dept->division_id)->get();
                        $area = $area->merge($department->map(function ($item) {
                            $item->sectors = 'Department';
                            return $item;
                        }));

                        $sections = Section::where('department_id', $dept->id)->get();
                        $area = $area->merge($sections->map(function ($item) {
                            $item->sectors = 'Section';
                            return $item;
                        }));

                        foreach ($sections as $sec) {
                            $units = Unit::where('section_id', $sec->id)->get();
                            $area = $area->merge($units->map(function ($item) {
                                $item->sectors = 'Unit';
                                return $item;
                            }));
                        }
                    }
                    break;
                case 'Section':
                    $section = Section::where('id', $area_id)->first();

                    if (!$section) {
                        return response()->json(['data' => []], Response::HTTP_OK);
                    }

                    if ($section->division_id !== null) {
                        $division = Division::where('id', $section->division_id)->get();
                        $area = $area->merge($division->map(function ($item) {
                            $item->sectors = 'Division';
                            return $item;
                        }));

                        $departments = Department::whereIn('division_id', $division->pluck('id'))->get();
                        $area = $area->merge($departments->map(function ($item) {
                            $item->sectors = 'Department';
                            return $item;
                        }));

                        $sections = Section::where('division_id', $section->division_id)->get();
                        $area = $area->merge($sections->map(function ($item) {
                            $item->sectors = 'Section';
                            return $item;
                        }));
                    }

                    if ($section->department_id !== null) {
                        $department = Department::where('id', $section->department_id)->first();

                        $division = Division::where('id', $department->division_id)->get();
                        $area = $area->merge($division->map(function ($item) {
                            $item->sectors = 'Division';
                            return $item;
                        }));

                        $departments = Department::where('division_id', $department->division_id)->get();
                        $area = $area->merge($departments->map(function ($item) {
                            $item->sectors = 'Department';
                            return $item;
                        }));

                        $sections = Section::whereIn('department_id', $departments->pluck('id'))->get();
                        $area = $area->merge($sections->map(function ($item) {
                            $item->sectors = 'Section';
                            return $item;
                        }));
                    }

                    $units = Unit::whereIn('section_id', $sections->pluck('id'))->get();
                    $area = $area->merge($units->map(function ($item) {
                        $item->sectors = 'Unit';
                        return $item;
                    }));
                    break;

                case 'Unit':
                    $unit = Unit::where('id', $area_id)->first();

                    if (!$unit) {
                        return response()->json(['data' => []], Response::HTTP_OK);
                    }

                    $section = Section::where('id', $unit->section_id)->first();
                    if ($section->division_id !== null) {
                        $division = Division::where('id', $section->division_id)->get();
                        $area = $area->merge($division->map(function ($item) {
                            $item->sectors = 'Division';
                            return $item;
                        }));

                        $sections = Section::where('division_id', $section->division_id)->get();
                        $area = $area->merge($sections->map(function ($item) {
                            $item->sectors = 'Section';
                            return $item;
                        }));

                        $units = Unit::whereIn('section_id', $sections->pluck('id'))->get();
                        $area = $area->merge($units->map(function ($item) {
                            $item->sectors = 'Unit';
                            return $item;
                        }));
                    }

                    if ($section->department_id !== null) {
                        $department = Department::where('id', $section->department_id)->first();

                        $division = Division::where('id', $department->division_id)->get();
                        $area = $area->merge($division->map(function ($item) {
                            $item->sectors = 'Division';
                            return $item;
                        }));

                        $departments = Department::where('division_id', $department->division_id)->get();
                        $area = $area->merge($departments->map(function ($item) {
                            $item->sectors = 'Department';
                            return $item;
                        }));

                        $sections = Section::whereIn('department_id', $departments->pluck('id'))->get();
                        $area = $area->merge($sections->map(function ($item) {
                            $item->sectors = 'Section';
                            return $item;
                        }));

                        $units = Unit::whereIn('section_id', $sections->pluck('id'))->get();
                        $area = $area->merge($units->map(function ($item) {
                            $item->sectors = 'Unit';
                            return $item;
                        }));
                    }
                    break;
            }
            return $area;
            // get biometric IDs
            $biometric_ids = DailyTimeRecords::whereYear('dtr_date', $year_of)->whereMonth('dtr_date', $month_of)->pluck('biometric_id');

            // Get employee profiles matching the biometric IDs
            $employee_profiles = EmployeeProfile::whereIn('biometric_id', $biometric_ids)->get();
            $report_data = [];

            foreach ($employee_profiles as $employee_profile) {
                $employee = EmployeeProfile::find($employee_profile->id);
                $employee_biometric_id = $employee_profile->biometric_id;

                $daily_time_records = DB::table('daily_time_records')
                    ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
                    ->where(function ($query) use ($employee_biometric_id, $month_of, $year_of) {
                        $query->where('biometric_id', $employee_biometric_id)
                            ->whereMonth(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                            ->whereYear(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
                    })
                    ->orWhere(function ($query) use ($employee_biometric_id, $month_of, $year_of) {
                        $query->where('biometric_id', $employee_biometric_id)
                            ->whereMonth(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                            ->whereYear(DB::raw('STR_TO_DATE(second_in, "%Y-%m-%d %H:%i:%s")'), $year_of);
                    })->get();

                $employee_schedules = [];

                foreach ($daily_time_records as $record) {
                    $bio_entry = [
                        'first_entry' => $record->first_in ?? $record->second_in,
                        'date_time' => $record->first_in ?? $record->second_in,
                    ];

                    $schedule = ReportHelpers::CurrentSchedule($employee_biometric_id, $bio_entry, false);
                    $day_schedule = $schedule['daySchedule'];
                    $employee_schedules[] = $day_schedule;

                    if (count($day_schedule) >= 1) {
                        $validate = [
                            (object) [
                                'id' => $record->id,
                                'first_in' => $record->first_in,
                                'first_out' => $record->first_out,
                                'second_in' => $record->second_in,
                                'second_out' => $record->second_out
                            ]
                        ];
                        ReportHelpers::saveTotalWorkingHours(
                            $validate,
                            $record,
                            $record,
                            $day_schedule,
                            true
                        );
                    }
                }

                $employee = EmployeeProfile::where('biometric_id', $employee_biometric_id)->first();

                // Process leave applications
                $leave_data = [];
                if ($employee->leaveApplications) {
                    $leave_applications = $employee->leaveApplications->filter(function ($application) {
                        return $application['status'] == "received";
                    });

                    foreach ($leave_applications as $leave) {
                        $leave_data[] = [
                            'country' => $leave['country'],
                            'city' => $leave['city'],
                            'from' => $leave['date_from'],
                            'to' => $leave['date_to'],
                            'leaveType' => LeaveType::find($leave['leave_type_id'])->name ?? "",
                            'without_pay' => $leave['without_pay'],
                            'datesCovered' => ReportHelpers::getDateIntervals($leave['date_from'], $leave['date_to'])
                        ];
                    }
                }

                // Filter schedules for the month
                if (count($employee_schedules) >= 1) {
                    $employee_schedules = array_map(function ($schedule) {
                        return (int)date('d', strtotime($schedule['scheduleDate']));
                    }, ReportHelpers::Allschedule($employee_biometric_id, $month_of, $year_of, null, null, null, null)['schedule']);
                }

                $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of);

                $attendance_data = [];
                $leave_without_pay = [];
                $leave_with_pay = [];
                $absences = [];
                $total_month_working_minutes = 0;
                $total_month_undertime_minutes = 0;

                // determine the present and absent days
                $present_days = array_filter(array_map(function ($day) use ($employee_schedules) {
                    return in_array($day->day, $employee_schedules) ? $day->day : null;
                }, $daily_time_records->toArray()));

                $absent_days = array_values(array_filter($employee_schedules, function ($day) use ($present_days) {
                    return !in_array($day, $present_days) && $day !== null;
                }));

                // determine the range of days to process
                $whole_month = $request->whole_month;
                $first_half = $request->first_half;
                $second_half = $request->second_half;
                $start_day = 1;

                if ($first_half) {
                    $days_in_month = 15;
                } else if ($second_half) {
                    $days_in_month = 16;
                }

                // Iterate through each day of the month
                for ($day = $start_day; $day <= $days_in_month; $day++) {
                    // filter leave dates
                    $filtered_leave_dates = [];
                    foreach ($leave_data as $leave) {
                        foreach ($leave['datesCovered'] as $date) {
                            $filtered_leave_dates[] = [
                                'dateReg' => strtotime($date),
                                'status' => $leave['without_pay']
                            ];
                        }
                    }

                    $leave_application = array_filter($filtered_leave_dates, function ($timestamp) use ($year_of, $month_of, $day) {
                        $dateToCompare = date('Y-m-d', $timestamp['dateReg']);
                        $dateToMatch = date('Y-m-d', strtotime("$year_of-$month_of-$day"));
                        return $dateToCompare === $dateToMatch;
                    });

                    $leave_count = count($leave_application);

                    if ($leave_count) {
                        if (array_values($leave_application)[0]['status']) {
                            $leave_without_pay[] = [
                                'dateRecord' => date('Y-m-d', strtotime("$year_of-$month_of-$day")),
                            ];
                        } else {
                            $leave_with_pay[] = [
                                'dateRecord' => date('Y-m-d', strtotime("$year_of-$month_of-$day")),
                            ];
                            $total_month_working_minutes += 480;
                        }
                    } else if (in_array($day, $present_days) && in_array($day, $employee_schedules)) {
                        $recordDTR = array_values(array_filter($daily_time_records->toArray(), function ($d) use ($year_of, $month_of, $day) {
                            return $d->dtr_date == date('Y-m-d', strtotime("$year_of-$month_of-$day"));
                        }));

                        if (
                            ($recordDTR[0]->first_in && $recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out) || // all entries
                            (!$recordDTR[0]->first_in && !$recordDTR[0]->first_out && $recordDTR[0]->second_in && $recordDTR[0]->second_out) || // 3-4
                            ($recordDTR[0]->first_in && $recordDTR[0]->first_out && !$recordDTR[0]->second_in && !$recordDTR[0]->second_out) || // 1-2
                            ($recordDTR[0]->first_in && $recordDTR[0]->first_out && $recordDTR[0]->second_in && !$recordDTR[0]->second_out) // 1-2-3
                        ) {
                            $attendance_data[] = $this->Attendance($year_of, $month_of, $day, $recordDTR);
                            $total_month_working_minutes += $recordDTR[0]->total_working_minutes;
                            $total_month_undertime_minutes += $recordDTR[0]->undertime_minutes;
                        } else {
                            $invalidEntries[] =  $this->Attendance($year_of, $month_of, $day, $recordDTR);
                        }
                    }
                    // Process absences
                    else if (
                        in_array($day, $absent_days) &&
                        in_array($day, $employee_schedules) &&
                        strtotime(date('Y-m-d', strtotime("$year_of-$month_of-$day"))) < strtotime(date('Y-m-d'))
                    ) {
                        $absences[] = [
                            'dateRecord' => date('Y-m-d', strtotime("$year_of-$month_of-$day")),
                        ];
                    }
                }

                // Calculate attendance statistics
                $present_count = count(array_filter($attendance_data, function ($d) {
                    return $d['total_working_minutes'] !== 0;
                }));
                $number_of_absences = count($absences) - count($leave_without_pay);
                $employee_schedules = ReportHelpers::Allschedule($employee_biometric_id, $month_of, $year_of, null, null, null, null)['schedule'];

                $scheduledDays = array_map(function ($schedule) {
                    return (int)date('d', strtotime($schedule['scheduleDate']));
                }, $employee_schedules);

                $filtered_schedules = array_values(array_filter($scheduledDays, function ($value) use ($start_day, $days_in_month) {
                    return $value >= $start_day && $value <= $days_in_month;
                }));

                $employee_assigned_areas = $employee->assignedAreas->first();

                $report_data[] = [
                    'employee_biometric_id' => $employee_biometric_id,
                    'employee_id' => $employee->employee_id,
                    'employee_name' => $employee->personalInformation->employeeName(),
                    'sector' => $employee->assignedArea->findDetails()['sector'],
                    'area_name' => $employee->assignedArea->findDetails()['details']['name'],
                    'area_code' => $employee->assignedArea->findDetails()['details']['code'],
                    'from' => $start_day,
                    'to' => $days_in_month,
                    'month' => $month_of,
                    'year' => $year_of,
                    'total_working_minutes' => $total_month_working_minutes,
                    'total_working_hours' => ReportHelpers::ToHours($total_month_working_minutes),
                    'total_undertime_minutes' => $total_month_undertime_minutes,
                    'total_present_days' => $present_count,
                    'total_absent_days' => $number_of_absences,
                    'total_leave_without_pay' => count($leave_without_pay),
                    'total_leave_with_pay' => count($leave_with_pay),
                    'schedule' => count($filtered_schedules),
                ];
            }

            return response()->json([
                'count' => empty($report_data) ? 0 : count($report_data),
                'data' => $report_data,
                'message' => 'Successfully retrieved data.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            // Log the error and return an internal server error response
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterAttendanceTardiness', $th->getMessage());
            return response()->json(
                [
                    'message' => $th->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
