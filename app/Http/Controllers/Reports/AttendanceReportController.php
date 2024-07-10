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
    public function filterAttendanceReport(Request $request)
    {
        try {
            $arr_data = [];
            $area_id = $request->area_id;
            $area_under = $request->area_under;
            $sector = ucfirst($request->sector);
            $employment_type = $request->employment_type_id;
            $designation_id = $request->designation_id;
            $absent_without_pay = $request->absent_without_pay ?? false; // new parameter to filter absences without pay
            $absent_without_official_leave = $request->absent_without_official_leave ?? false; // parameter to filter absences without official leave
            $whole_month = $request->whole_month;
            $first_half = $request->first_half;
            $second_half  = $request->second_half;
            $month_of = (int) $request->month_of;
            $year_of = (int) $request->year_of;
            $limit = $request->limit; // default limit is 100

            if ($request->only(['month_of', 'year_of'])) {
                $biometric_ids = DailyTimeRecords::whereYear('dtr_date', $year_of)
                    ->whereMonth('dtr_date', $month_of)
                    ->pluck('biometric_id');
                // Get employee profiles matching the biometric IDs
                $employee_profiles = EmployeeProfile::whereIn('biometric_id', $biometric_ids)->get();

                foreach ($employee_profiles as $employee) {
                    if ($employee) {
                        $arr_data[] = $this->resultAttendanceReport(
                            $employee,
                            $whole_month = true,
                            $first_half = false,
                            $second_half = false,
                            $month_of,
                            $year_of
                        );
                    }
                }
            } else {

                // get biometric IDs
                $biometric_ids = DailyTimeRecords::whereYear('dtr_date', $year_of)
                    ->whereMonth('dtr_date', $month_of)
                    ->pluck('biometric_id');

                // Filter for absences without pay
                if ($absent_without_pay) {
                    $biometric_ids = DB::table('leave_applications')
                        ->join('leave_types', 'leave_applications.leave_type_id', '=', 'leave_types.id')
                        ->join('employee_profiles', 'leave_applications.employee_profile_id', '=', 'employee_profiles.id')
                        ->where('leave_types.without_pay', true)
                        ->where('leave_applications.status', 'approved')
                        ->whereYear('leave_applications.date_from', $year_of)
                        ->whereMonth('leave_applications.date_from', $month_of)
                        ->pluck('employee_profiles.biometric_id');
                }

                // Filter for absences without official leave
                if ($absent_without_official_leave) {
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

                    $biometric_ids = $absent_biometric_ids->diff($leave_biometric_ids);
                }


                switch ($sector) {
                    case 'Division':
                        switch ($area_under) {
                            case 'all':
                                $areas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                                    ->where('division_id', $area_id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when(!empty($designation_id), function ($query) use ($designation_id) {
                                        return $query->where('designation_id', $designation_id);
                                    })
                                    ->whereHas('employeeProfile', function ($q) use ($biometric_ids, $employment_type) {
                                        $q->whereIn('biometric_id', $biometric_ids);
                                        if (!empty($employment_type) || !is_null($employment_type)) {
                                            $q->where('employment_type_id', $employment_type);
                                        }
                                    })
                                    ->limit($limit)->get();

                                foreach ($areas as $area) {
                                    $arr_data[] = $this->resultAttendanceReport(
                                        $area->employeeProfile,
                                        $whole_month,
                                        $first_half,
                                        $second_half,
                                        $month_of,
                                        $year_of,
                                    );
                                }

                                $departments = Department::where('division_id', $area_id)->get();
                                foreach ($departments as $department) {
                                    $areas = AssignArea::with(['employeeProfile', 'department', 'section', 'unit'])
                                        ->where('department_id', $department->id)
                                        ->when(!empty($designation_id), function ($query) use ($designation_id) {
                                            return $query->where('designation_id', $designation_id);
                                        })
                                        ->whereHas('employeeProfile', function ($q) use ($biometric_ids, $employment_type) {
                                            $q->whereIn('biometric_id', $biometric_ids);
                                            if (!empty($employment_type) || !is_null($employment_type)) {
                                                $q->where('employment_type_id', $employment_type);
                                            }
                                        })
                                        ->limit($limit)->get();

                                    foreach ($areas as $area) {
                                        $arr_data[] = $this->resultAttendanceReport(
                                            $area->employeeProfile,
                                            $whole_month,
                                            $first_half,
                                            $second_half,
                                            $month_of,
                                            $year_of,
                                        );
                                    }

                                    $sections = Section::where('department_id', $department->id)->get();
                                    foreach ($sections as $section) {
                                        $areas = AssignArea::with(['employeeProfile', 'section', 'unit'])
                                            ->where('section_id', $section->id)
                                            ->when(!empty($designation_id), function ($query) use ($designation_id) {
                                                return $query->where('designation_id', $designation_id);
                                            })
                                            ->whereHas('employeeProfile', function ($q) use ($biometric_ids, $employment_type) {
                                                $q->whereIn('biometric_id', $biometric_ids);
                                                if (!empty($employment_type) || !is_null($employment_type)) {
                                                    $q->where('employment_type_id', $employment_type);
                                                }
                                            })
                                            ->limit($limit)->get();

                                        foreach ($areas as $area) {
                                            $arr_data[] = $this->resultAttendanceReport(
                                                $area->employeeProfile,
                                                $whole_month,
                                                $first_half,
                                                $second_half,
                                                $month_of,
                                                $year_of,
                                            );
                                        }

                                        $units = Unit::where('section_id', $section->id)->get();
                                        foreach ($units as $unit) {
                                            $areas = AssignArea::with(['employeeProfile', 'section', 'unit'])
                                                ->where('unit_id', $unit->id)
                                                ->when(!empty($designation_id), function ($query) use ($designation_id) {
                                                    return $query->where('designation_id', $designation_id);
                                                })
                                                ->whereHas('employeeProfile', function ($q) use ($biometric_ids, $employment_type) {
                                                    $q->whereIn('biometric_id', $biometric_ids);
                                                    if (!empty($employment_type) || !is_null($employment_type)) {
                                                        $q->where('employment_type_id', $employment_type);
                                                    }
                                                })
                                                ->limit($limit)->get();

                                            foreach ($areas as $area) {
                                                $arr_data[] = $this->resultAttendanceReport(
                                                    $area->employeeProfile,
                                                    $whole_month,
                                                    $first_half,
                                                    $second_half,
                                                    $month_of,
                                                    $year_of,
                                                );
                                            }
                                        }
                                    }
                                }
                                // Get sections directly under the division (if any) that are not under any department
                                $sections = Section::where('division_id', $area_id)->whereNull('department_id')->get();
                                foreach ($sections as $section) {
                                    $areas = AssignArea::with(['employeeProfile', 'section', 'unit'])
                                        ->where('section_id', $section->id)
                                        ->when(!empty($designation_id), function ($query) use ($designation_id) {
                                            return $query->where('designation_id', $designation_id);
                                        })
                                        ->whereHas('employeeProfile', function ($q) use ($biometric_ids, $employment_type) {
                                            $q->whereIn('biometric_id', $biometric_ids);
                                            if (!empty($employment_type) || !is_null($employment_type)) {
                                                $q->where('employment_type_id', $employment_type);
                                            }
                                        })
                                        ->limit($limit)->get();

                                    foreach ($areas as $area) {
                                        $arr_data[] = $this->resultAttendanceReport(
                                            $area->employeeProfile,
                                            $whole_month,
                                            $first_half,
                                            $second_half,
                                            $month_of,
                                            $year_of,
                                        );
                                    }

                                    $units = Unit::where('section_id', $section->id)->get();
                                    foreach ($units as $unit) {
                                        $areas = AssignArea::with(['employeeProfile', 'section', 'unit'])
                                            ->where('unit_id', $unit->id)
                                            ->when(!empty($designation_id), function ($query) use ($designation_id) {
                                                return $query->where('designation_id', $designation_id);
                                            })
                                            ->whereHas('employeeProfile', function ($q) use ($biometric_ids, $employment_type) {
                                                $q->whereIn('biometric_id', $biometric_ids);
                                                if (!empty($employment_type) || !is_null($employment_type)) {
                                                    $q->where('employment_type_id', $employment_type);
                                                }
                                            })
                                            ->limit($limit)->get();

                                        foreach ($areas as $area) {
                                            $arr_data[] = $this->resultAttendanceReport(
                                                $area->employeeProfile,
                                                $whole_month,
                                                $first_half,
                                                $second_half,
                                                $month_of,
                                                $year_of,
                                            );
                                        }
                                    }
                                }
                                break;
                            case 'staff':
                                $areas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                                    ->where('division_id', $area_id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when(!empty($designation_id), function ($query) use ($designation_id) {
                                        return $query->where('designation_id', $designation_id);
                                    })
                                    ->whereHas('employeeProfile', function ($q) use ($biometric_ids, $employment_type) {
                                        $q->whereIn('biometric_id', $biometric_ids);
                                        if (!empty($employment_type) || !is_null($employment_type)) {
                                            $q->where('employment_type_id', $employment_type);
                                        }
                                    })
                                    ->limit($limit)->get();

                                foreach ($areas as $area) {
                                    $arr_data[] = $this->resultAttendanceReport(
                                        $area->employeeProfile,
                                        $whole_month,
                                        $first_half,
                                        $second_half,
                                        $month_of,
                                        $year_of,
                                    );
                                }
                                break;
                        }
                        break;
                    case 'Department':
                        switch ($area_under) {
                            case 'all':
                                $areas = AssignArea::with(['employeeProfile', 'department', 'section', 'unit'])
                                    ->where('department_id', $area_id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when(!empty($designation_id), function ($query) use ($designation_id) {
                                        return $query->where('designation_id', $designation_id);
                                    })
                                    ->whereHas('employeeProfile', function ($q) use ($biometric_ids, $employment_type) {
                                        $q->whereIn('biometric_id', $biometric_ids);
                                        if (!empty($employment_type) || !is_null($employment_type)) {
                                            $q->where('employment_type_id', $employment_type);
                                        }
                                    })
                                    ->limit($limit)->get();

                                foreach ($areas as $area) {
                                    $arr_data[] = $this->resultAttendanceReport(
                                        $area->employeeProfile,
                                        $whole_month,
                                        $first_half,
                                        $second_half,
                                        $month_of,
                                        $year_of,
                                    );
                                }

                                $sections = Section::where('department_id', $area_id)->get();
                                foreach ($sections as $section) {
                                    $areas = AssignArea::with(['employeeProfile', 'section', 'unit'])
                                        ->where('section_id', $section->id)
                                        ->when(!empty($designation_id), function ($query) use ($designation_id) {
                                            return $query->where('designation_id', $designation_id);
                                        })
                                        ->whereHas('employeeProfile', function ($q) use ($biometric_ids, $employment_type) {
                                            $q->whereIn('biometric_id', $biometric_ids);
                                            if (!empty($employment_type) || !is_null($employment_type)) {
                                                $q->where('employment_type_id', $employment_type);
                                            }
                                        })
                                        ->limit($limit)->get();

                                    foreach ($areas as $area) {
                                        $arr_data[] = $this->resultAttendanceReport(
                                            $area->employeeProfile,
                                            $whole_month,
                                            $first_half,
                                            $second_half,
                                            $month_of,
                                            $year_of,
                                        );
                                    }

                                    $units = Unit::where('section_id', $section->id)->get();
                                    foreach ($units as $unit) {
                                        $areas = AssignArea::with(['employeeProfile', 'section', 'unit'])
                                            ->where('unit_id', $unit->id)
                                            ->when(!empty($designation_id), function ($query) use ($designation_id) {
                                                return $query->where('designation_id', $designation_id);
                                            })
                                            ->whereHas('employeeProfile', function ($q) use ($biometric_ids, $employment_type) {
                                                $q->whereIn('biometric_id', $biometric_ids);
                                                if (!empty($employment_type) || !is_null($employment_type)) {
                                                    $q->where('employment_type_id', $employment_type);
                                                }
                                            })
                                            ->limit($limit)->get();

                                        foreach ($areas as $area) {
                                            $arr_data[] = $this->resultAttendanceReport(
                                                $area->employeeProfile,
                                                $whole_month,
                                                $first_half,
                                                $second_half,
                                                $month_of,
                                                $year_of,
                                            );
                                        }
                                    }
                                }

                                break;
                            case 'staff':
                                $areas = AssignArea::with(['employeeProfile', 'department', 'section', 'unit'])
                                    ->where('department_id', $area_id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when(!empty($designation_id), function ($query) use ($designation_id) {
                                        return $query->where('designation_id', $designation_id);
                                    })
                                    ->whereHas('employeeProfile', function ($q) use ($biometric_ids, $employment_type) {
                                        $q->whereIn('biometric_id', $biometric_ids);
                                        if (!empty($employment_type) || !is_null($employment_type)) {
                                            $q->where('employment_type_id', $employment_type);
                                        }
                                    })
                                    ->limit($limit)->get();

                                foreach ($areas as $area) {
                                    $arr_data[] = $this->resultAttendanceReport(
                                        $area->employeeProfile,
                                        $whole_month,
                                        $first_half,
                                        $second_half,
                                        $month_of,
                                        $year_of,
                                    );
                                }
                                break;
                        }
                        break;
                    case 'Section':
                        switch ($area_under) {
                            case 'all':
                                $areas = AssignArea::with(['employeeProfile', 'section', 'unit'])
                                    ->where('section_id', $area_id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when(!empty($designation_id), function ($query) use ($designation_id) {
                                        return $query->where('designation_id', $designation_id);
                                    })
                                    ->whereHas('employeeProfile', function ($q) use ($biometric_ids, $employment_type) {
                                        $q->whereIn('biometric_id', $biometric_ids);
                                        if (!empty($employment_type) || !is_null($employment_type)) {
                                            $q->where('employment_type_id', $employment_type);
                                        }
                                    })
                                    ->limit($limit)->get();

                                foreach ($areas as $area) {
                                    $arr_data[] = $this->resultAttendanceReport(
                                        $area->employeeProfile,
                                        $whole_month,
                                        $first_half,
                                        $second_half,
                                        $month_of,
                                        $year_of,
                                    );
                                }

                                $units = Unit::where('section_id', $area_id)->get();
                                foreach ($units as $unit) {
                                    $areas = AssignArea::with(['employeeProfile', 'section', 'unit'])
                                        ->where('unit_id', $unit->id)
                                        ->when(!empty($designation_id), function ($query) use ($designation_id) {
                                            return $query->where('designation_id', $designation_id);
                                        })
                                        ->whereHas('employeeProfile', function ($q) use ($biometric_ids, $employment_type) {
                                            $q->whereIn('biometric_id', $biometric_ids);
                                            if (!empty($employment_type) || !is_null($employment_type)) {
                                                $q->where('employment_type_id', $employment_type);
                                            }
                                        })
                                        ->limit($limit)->get();

                                    foreach ($areas as $area) {
                                        $arr_data[] = $this->resultAttendanceReport(
                                            $area->employeeProfile,
                                            $whole_month,
                                            $first_half,
                                            $second_half,
                                            $month_of,
                                            $year_of,
                                        );
                                    }
                                }

                                break;
                            case 'staff':
                                $areas = AssignArea::with(['employeeProfile', 'section', 'unit'])
                                    ->where('section_id', $area_id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when(!empty($designation_id), function ($query) use ($designation_id) {
                                        return $query->where('designation_id', $designation_id);
                                    })
                                    ->whereHas('employeeProfile', function ($q) use ($biometric_ids, $employment_type) {
                                        $q->whereIn('biometric_id', $biometric_ids);
                                        if (!empty($employment_type) || !is_null($employment_type)) {
                                            $q->where('employment_type_id', $employment_type);
                                        }
                                    })
                                    ->limit($limit)->get();

                                foreach ($areas as $area) {
                                    $arr_data[] = $this->resultAttendanceReport(
                                        $area->employeeProfile,
                                        $whole_month,
                                        $first_half,
                                        $second_half,
                                        $month_of,
                                        $year_of,
                                    );
                                }
                                break;
                        }
                        break;
                    case 'Unit':
                        $units = Unit::where('unit_id', $area_id)->get();
                        foreach ($units as $unit) {
                            $areas = AssignArea::with(['employeeProfile', 'section', 'unit'])
                                ->where('unit_id', $unit->id)
                                ->when(!empty($designation_id), function ($query) use ($designation_id) {
                                    return $query->where('designation_id', $designation_id);
                                })
                                ->whereHas('employeeProfile', function ($q) use ($biometric_ids, $employment_type) {
                                    $q->whereIn('biometric_id', $biometric_ids);
                                    if (!empty($employment_type) || !is_null($employment_type)) {
                                        $q->where('employment_type_id', $employment_type);
                                    }
                                })
                                ->limit($limit)->get();

                            foreach ($areas as $area) {
                                $arr_data[] = $this->resultAttendanceReport(
                                    $area->employeeProfile,
                                    $whole_month,
                                    $first_half,
                                    $second_half,
                                    $month_of,
                                    $year_of,
                                );
                            }
                        }
                        break;
                }
            }


            return response()->json([
                'count' => empty($arr_data) ? 0 : count($arr_data),
                'data' => $arr_data,
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


    private function resultAttendanceReport($employee_profile, $whole_month, $first_half, $second_half, $month_of, $year_of)
    {
        $report_data = [];
        $employee = EmployeeProfile::find($employee_profile->id);
        $employee_biometric_id = $employee_profile->biometric_id;

        $daily_time_records = [];

        if (empty($month_of) || empty($year_of)) {
            // If either month_of or year_of is empty, fetch all records for the employee
            $daily_time_records = DB::table('daily_time_records')
                ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
                ->where('biometric_id', $employee_biometric_id)
                ->whereNotNull('first_in')
                ->get();
        } else {
            // Fetch records for the specific month and year
            $daily_time_records = DB::table('daily_time_records')
                ->select('*', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
                ->where('biometric_id', $employee_biometric_id)
                ->whereMonth(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $month_of)
                ->whereYear(DB::raw('STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")'), $year_of)
                ->get();
        }

        $employee_schedules = [];
        $total_days_with_tardiness = 0;
        foreach ($daily_time_records as $record) {
            $bio_entry = [
                'first_entry' => $record->first_in ?? $record->second_in,
                'date_time' => $record->first_in ?? $record->second_in,
            ];

            $schedule = ReportHelpers::CurrentSchedule($employee_biometric_id, $bio_entry, false);
            $day_schedule = $schedule['daySchedule'];
            $employee_schedules[] = $day_schedule;



            if (count($day_schedule) >= 1) {
                $recordDate = Carbon::parse($record->dtr_date);
                // Check for morning tardiness (after 8 AM)
                if ($record->first_in && Carbon::parse($record->first_in)->gt($recordDate->copy()->startOfDay()->addHours(8))) {
                    $total_days_with_tardiness++;
                }
                // Check for afternoon tardiness (after 1 PM)
                if ($record->second_in && Carbon::parse($record->second_in)->gt($recordDate->copy()->startOfDay()->addHours(13))) {
                    $total_days_with_tardiness++;
                }

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

        // Filter schedules for the month if month_of and year_of are provided
        if (!empty($month_of) && !empty($year_of)) {
            $employee_schedules = array_map(function ($schedule) {
                return (int)date('d', strtotime($schedule['scheduleDate']));
            }, ReportHelpers::Allschedule($employee_biometric_id, $month_of, $year_of, null, null, null, null)['schedule']);
        }

        $days_in_month = !empty($month_of) && !empty($year_of) ? cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of) : 31;

        $attendance_data = [];
        $leave_without_pay = [];
        $leave_with_pay = [];
        $absences = [];
        $total_month_working_minutes = 0;
        $total_month_undertime_minutes = 0;
        $total_hours_missed = 0;


        // Determine the present and absent days
        $present_days = array_filter(array_map(function ($day) use ($employee_schedules) {
            return in_array($day->day, $employee_schedules) ? $day->day : null;
        }, $daily_time_records->toArray()));

        $absent_days = array_values(array_filter($employee_schedules, function ($day) use ($present_days) {
            return !in_array($day, $present_days) && $day !== null;
        }));

        // Determine the range of days to process
        $start_day = 1;

        if ($first_half) {
            $days_in_month = 15;
        } else if ($second_half) {
            $days_in_month = 16;
        }

        // Iterate through each day of the month
        for ($day = $start_day; $day <= $days_in_month; $day++) {
            // Filter leave dates
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
                $dateToMatch = !empty($month_of) && !empty($year_of) ? date('Y-m-d', strtotime("$year_of-$month_of-$day")) : null;
                return $dateToCompare === $dateToMatch;
            });

            $leave_count = count($leave_application);

            if ($leave_count) {
                if (array_values($leave_application)[0]['status']) {
                    $leave_without_pay[] = [
                        'dateRecord' => date('Y-m-d', strtotime("$year_of-$month_of-$day")),
                    ];
                    $total_hours_missed += 8; // 8 hours missed for leave without pay
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
                    $total_hours_missed += max(0, (480 - $recordDTR[0]->total_working_minutes) / 60); // hours missed for less working minutes
                } else {
                    $invalidEntries[] = $this->Attendance($year_of, $month_of, $day, $recordDTR);
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
                $total_hours_missed += 8; // 8 hours missed for absences
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

        $report_data = [
            'employee_biometric_id' => $employee_biometric_id,
            'employee_id' => $employee->employee_id,
            'employee_name' => $employee->personalInformation->employeeName(),
            'employment_type' => $employee->employmentType->name,
            'employee_designation_name' => $employee->findDesignation()['name'],
            'employee_designation_code' => $employee->findDesignation()['code'],
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
            'total_hours_missed' => $total_hours_missed,
            'total_days_with_tardiness' => $total_days_with_tardiness
        ];
        return $report_data;
    }
}
