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

    /**
     * Sorts an array of employees based on a specified field and order.
     *
     * @param array $employees The array of employees to be sorted.
     * @param string $sort_by The field to sort by (e.g., 'leave_count').
     * @param string $order The order to sort by ('asc' or 'desc').
     * @return void
     */
    private function sortData(&$employees, $sort_by, $order)
    {
        usort($employees, function ($a, $b) use ($sort_by, $order) {
            if ($order === 'asc') {
                return $a[$sort_by] <=> $b[$sort_by];
            }
            return $b[$sort_by] <=> $a[$sort_by];
        });
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
            $first_half = (bool) $request->first_half;
            $second_half  = (bool) $request->second_half;
            $month_of = (int) $request->month_of;
            $year_of = (int) $request->year_of;
            $limit = $request->limit; // default limit is 100
            $report_type = $request->report_type; // new parameter for report type [tardiness/absences]
            $sort_order = $request->sort_order; // new parameter for sort order [asc/desc]
            if (
                ($month_of && $year_of) && !$first_half && !$second_half && !$sector && !$employment_type && !$designation_id && !$absent_without_pay &&
                !$absent_without_official_leave
            ) {
                // Get biometric IDs for the given month and year
                $biometric_ids = DailyTimeRecords::whereYear('dtr_date', $year_of)
                    ->whereMonth('dtr_date', $month_of)
                    ->pluck('biometric_id');

                // Get employee profiles matching the biometric IDs
                $employee_profiles = EmployeeProfile::whereIn('biometric_id', $biometric_ids)
                    ->with(['personalInformation', 'employmentType', 'assignedArea'])
                    ->limit($limit)
                    ->get();

                $arr_data = $employee_profiles->map(function ($employee) use ($month_of, $year_of) {
                    return $this->resultAttendanceReport(
                        $employee,
                        $whole_month = true,
                        $first_half = false,
                        $second_half = false,
                        $month_of,
                        $year_of
                    );
                })->toArray();
            } else if (
                ($month_of && $year_of) && ($first_half || $second_half) && !$sector && !$employment_type && !$designation_id && !$absent_without_pay &&
                !$absent_without_official_leave
            ) {
                // Get biometric IDs for the given month and year
                $biometric_ids = DailyTimeRecords::whereYear('dtr_date', $year_of)
                    ->whereMonth('dtr_date', $month_of)
                    ->pluck('biometric_id');

                // Get employee profiles matching the biometric IDs
                $employee_profiles = EmployeeProfile::whereIn('biometric_id', $biometric_ids)
                    ->with(['personalInformation', 'employmentType', 'assignedArea'])
                    ->limit($limit)
                    ->get();

                $arr_data = $employee_profiles->map(function ($employee) use ($month_of, $year_of, $first_half, $second_half) {
                    return $this->resultAttendanceReport(
                        $employee,
                        $whole_month = true,
                        $first_half,
                        $second_half,
                        $month_of,
                        $year_of
                    );
                })->toArray();
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

            if (!empty($report_type) && !empty($report_type)) {
                $this->sortData($arr_data, $report_type === 'tardiness' ? 'total_days_with_tardiness' : 'total_absent_days', $sort_order);
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

        // Use Eloquent's with() method to eager load related models
        $employee = EmployeeProfile::with(['personalInformation', 'employmentType', 'assignedArea', 'leaveApplications'])->find($employee_profile->id);
        $employee_biometric_id = $employee_profile->biometric_id;

        $daily_time_records = DailyTimeRecords::select('id', 'biometric_id', 'first_in', 'second_in', 'first_out', 'second_out', 'dtr_date', 'total_working_minutes', 'undertime_minutes', DB::raw('DAY(STR_TO_DATE(first_in, "%Y-%m-%d %H:%i:%s")) AS day'))
            ->where('biometric_id', $employee_biometric_id)
            ->whereNotNull('first_in');

        // Conditional query based on month_of and year_of
        if (!empty($month_of) && !empty($year_of)) {
            $daily_time_records = $daily_time_records->whereMonth('first_in', $month_of)->whereYear('first_in', $year_of);
        }

        $daily_time_records = $daily_time_records->get();

        $employee_schedules = [];
        $total_days_with_tardiness = 0;

        foreach ($daily_time_records as $record) {
            $first_in = $record->first_in;
            $second_in = $record->second_in;
            $recordDate = Carbon::parse($record->dtr_date);

            $bio_entry = [
                'first_entry' => $first_in ?? $second_in,
                'date_time' => $first_in ?? $second_in,
            ];

            // Use ReportHelpers to get the current schedule
            $schedule = ReportHelpers::CurrentSchedule($employee_biometric_id, $bio_entry, false);
            $day_schedule = $schedule['daySchedule'];
            $employee_schedules[] = $day_schedule;

            if (count($day_schedule) >= 1) {
                $startOfDay8 = $recordDate->copy()->startOfDay()->addHours(8);
                $startOfDay13 = $recordDate->copy()->startOfDay()->addHours(13);

                if ($first_in && Carbon::parse($first_in)->gt($startOfDay8)) {
                    $total_days_with_tardiness++;
                }
                if ($second_in && Carbon::parse($second_in)->gt($startOfDay13)) {
                    $total_days_with_tardiness++;
                }

                $validate = [
                    (object)[
                        'id' => $record->id,
                        'first_in' => $record->first_in,
                        'first_out' => $record->first_out,
                        'second_in' => $record->second_in,
                        'second_out' => $record->second_out,
                    ],
                ];
                ReportHelpers::saveTotalWorkingHours($validate, $record, $record, $day_schedule, true);
            }
        }

        // Use collections and map() for processing leave data
        $leave_data = $employee->leaveApplications
            ->filter(fn ($application) => $application['status'] == "received")
            ->map(function ($leave) {
                $leaveType = LeaveType::find($leave['leave_type_id']);
                return [
                    'country' => $leave['country'],
                    'city' => $leave['city'],
                    'from' => $leave['date_from'],
                    'to' => $leave['date_to'],
                    'leaveType' => $leaveType->name ?? "",
                    'without_pay' => $leave['without_pay'],
                    'datesCovered' => ReportHelpers::getDateIntervals($leave['date_from'], $leave['date_to'])
                ];
            })
            ->toArray();

        if (!empty($month_of) && !empty($year_of)) {
            $employee_schedules = collect(ReportHelpers::Allschedule($employee_biometric_id, $month_of, $year_of, null, null, null, null)['schedule'])
                ->map(fn ($schedule) => (int)date('d', strtotime($schedule['scheduleDate'])))
                ->toArray();
        }

        $days_in_month = !empty($month_of) && !empty($year_of) ? cal_days_in_month(CAL_GREGORIAN, $month_of, $year_of) : 31;

        // Determine the range of days to process
        $start_day = 1;
        $end_day = $days_in_month;

        if ($first_half) {
            $end_day = 15;
        } else if ($second_half) {
            $start_day = 16;
        }

        $leave_without_pay = [];
        $leave_with_pay = [];
        $absences = [];
        $total_month_working_minutes = 0;
        $total_month_undertime_minutes = 0;
        $total_hours_missed = 0;

        // Use collections to filter present and absent days
        $present_days = collect($daily_time_records)->filter(fn ($day) => in_array($day->day, $employee_schedules))->pluck('day')->toArray();
        $absent_days = array_values(array_diff($employee_schedules, $present_days));

        // Optimize leave date filtering using collections
        $filtered_leave_dates = collect($leave_data)->flatMap(fn ($leave) => collect($leave['datesCovered'])->map(fn ($date) => [
            'dateReg' => strtotime($date),
            'status' => $leave['without_pay']
        ]))->toArray();

        for ($day = $start_day; $day <= $end_day; $day++) {
            $current_date_str = date('Y-m-d', strtotime("$year_of-$month_of-$day"));
            $current_date_ts = strtotime($current_date_str);

            // Filter leave applications for the specific day
            $leave_application = collect($filtered_leave_dates)->first(fn ($timestamp) => date('Y-m-d', $timestamp['dateReg']) === $current_date_str);

            if ($leave_application) {
                if ($leave_application['status']) {
                    $leave_without_pay[] = ['dateRecord' => $current_date_str];
                    $total_hours_missed += 8;
                } else {
                    $leave_with_pay[] = ['dateRecord' => $current_date_str];
                    $total_month_working_minutes += 480;
                }
            } else if (in_array($day, $present_days) && in_array($day, $employee_schedules)) {
                // Use collections to find DTR for the day
                $recordDTR = collect($daily_time_records)->firstWhere('day', $day);

                if ($recordDTR && (
                    ($recordDTR->first_in && $recordDTR->first_out && $recordDTR->second_in && $recordDTR->second_out) ||
                    (!$recordDTR->first_in && !$recordDTR->first_out && $recordDTR->second_in && $recordDTR->second_out) ||
                    ($recordDTR->first_in && $recordDTR->first_out && !$recordDTR->second_in && !$recordDTR->second_out) ||
                    ($recordDTR->first_in && $recordDTR->first_out && $recordDTR->second_in && !$recordDTR->second_out)
                )) {
                    $total_month_working_minutes += $recordDTR->total_working_minutes;
                    $total_month_undertime_minutes += $recordDTR->undertime_minutes;
                    $total_hours_missed += max(0, (480 - $recordDTR->total_working_minutes) / 60);
                }
            } else if (in_array($day, $absent_days) && in_array($day, $employee_schedules) && $current_date_ts < strtotime(date('Y-m-d'))) {
                $absences[] = ['dateRecord' => $current_date_str];
                $total_hours_missed += 8;
            }
        }

        // Calculate the number of absences
        $number_of_absences = count($absences) - count($leave_without_pay);

        // Get all schedules for the employee
        $employee_schedules = ReportHelpers::Allschedule($employee_biometric_id, $month_of, $year_of, null, null, null, null)['schedule'];

        // Map and filter scheduled days within the date range
        $scheduledDays = collect($employee_schedules)
            ->map(fn ($schedule) => (int)date('d', strtotime($schedule['scheduleDate'])))
            ->filter(fn ($value) => $value >= $start_day && $value <= $end_day)
            ->values()
            ->toArray();

        // Prepare report data
        $report_data = [
            'id' => $employee->id,
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
            'to' => $end_day,
            'month' => $month_of,
            'year' => $year_of,
            'total_working_minutes' => $total_month_working_minutes,
            'total_working_hours' => ReportHelpers::ToHours($total_month_working_minutes),
            'total_undertime_minutes' => $total_month_undertime_minutes,
            'total_absent_days' => $number_of_absences,
            'total_leave_without_pay' => count($leave_without_pay),
            'total_leave_with_pay' => count($leave_with_pay),
            'schedule' => count($scheduledDays),
            'total_hours_missed' => $total_hours_missed,
            'total_days_with_tardiness' => $total_days_with_tardiness
        ];



        return $report_data;
    }
}
