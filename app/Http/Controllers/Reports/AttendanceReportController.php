<?php

namespace App\Http\Controllers\Reports;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\AssignArea;
use App\Models\Division;
use App\Models\Department;
use App\Models\Section;
use App\Models\Unit;
use App\Models\LeaveApplication;
use App\Models\LeaveType;

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
     * Filters attendance records based on given criteria.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filterAttendance(Request $request)
    {
        try {
            // Get filters from the request
            $area_id = $request->area_id;
            $sector = $request->sector;
            $employment_type = $request->employment_type_id;
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $period_type = $request->period_type; // quarterly or monthly
            $limit = $request->limit ?? 100; // default limit is 100

            $result = $this->getEmployeeFilter(
                $area_id,
                $sector,
                $employment_type,
                $start_date,
                $end_date,
                $period_type,
                $limit
            );

            return response()->json([
                'count' => count($result),
                'data' => $result,
                'message' => 'Successfully retrieved data.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            // Log the error and return an internal server error response
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterAttendance', $th->getMessage());
            return response()->json(
                [
                    'message' => $th->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Retrieves filtered employee data based on the given criteria.
     *
     * @param int $area_id
     * @param string $sector
     * @param int|null $employment_type
     * @param string|null $start_date
     * @param string|null $end_date
     * @param string $period_type
     * @param int $limit
     * @return array
     */
    private function getEmployeeFilter($area_id, $sector, $employment_type, $start_date, $end_date, $period_type, $limit)
    {
        $arr_data = [];

        try {
            switch ($sector) {
                case 'division':
                    $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                        ->where('division_id', $area_id)
                        ->where('employee_profile_id', '<>', 1)
                        ->limit($limit);

                    if ($employment_type) {
                        $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                            $q->where('employment_type_id', $employment_type);
                        });
                    }

                    if ($start_date && $end_date) {
                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                            switch ($period_type) {
                                case 'monthly':
                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                    break;
                                case 'quarterly':
                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                    break;
                            }
                        });
                    }

                    $assignAreas = $assignAreas->get();

                    foreach ($assignAreas as $assignArea) {
                        $arr_data[] = $this->resultFilter(
                            $assignArea->employeeProfile,
                            'division',
                        );
                    }

                    $departments = Department::where('division_id', $area_id)->get();
                    foreach ($departments as $department) {
                        $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                            ->where('department_id', $department->id)
                            ->limit($limit);

                        if ($employment_type) {
                            $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                $q->where('employment_type_id', $employment_type);
                            });
                        }

                        if ($start_date && $end_date) {
                            $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                switch ($period_type) {
                                    case 'monthly':
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                        break;
                                    case 'quarterly':
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                        break;
                                }
                            });
                        }

                        $assignAreas = $assignAreas->get();

                        foreach ($assignAreas as $assignArea) {
                            $arr_data[] = $this->resultFilter(
                                $assignArea->employeeProfile,
                                'department',
                            );
                        }

                        $sections = Section::where('department_id', $department->id)->get();
                        foreach ($sections as $section) {
                            $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                                ->where('section_id', $section->id)
                                ->limit($limit);

                            if ($employment_type) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                    $q->where('employment_type_id', $employment_type);
                                });
                            }

                            if ($start_date && $end_date) {
                                $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                    switch ($period_type) {
                                        case 'monthly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                            break;
                                        case 'quarterly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                            break;
                                    }
                                });
                            }

                            $assignAreas = $assignAreas->get();

                            foreach ($assignAreas as $assignArea) {
                                $arr_data[] = $this->resultFilter(
                                    $assignArea->employeeProfile,
                                    'section',
                                );
                            }

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                                    ->where('unit_id', $unit->id)
                                    ->limit($limit);

                                if ($employment_type) {
                                    $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                        $q->where('employment_type_id', $employment_type);
                                    });
                                }

                                if ($start_date && $end_date) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                        switch ($period_type) {
                                            case 'monthly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                break;
                                            case 'quarterly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                break;
                                        }
                                    });
                                }

                                $assignAreas = $assignAreas->get();

                                foreach ($assignAreas as $assignArea) {
                                    $arr_data[] = $this->resultFilter(
                                        $assignArea->employeeProfile,
                                        'unit',
                                    );
                                }
                            }
                        }
                    }

                    // Get sections directly under the division (if any) that are not under any department
                    $sections = Section::where('division_id', $area_id)->whereNull('department_id')->get();
                    foreach ($sections as $section) {
                        $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                            ->where('section_id', $section->id)
                            ->limit($limit);

                        if ($employment_type) {
                            $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                $q->where('employment_type_id', $employment_type);
                            });
                        }

                        if ($start_date && $end_date) {
                            $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                switch ($period_type) {
                                    case 'monthly':
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                        break;
                                    case 'quarterly':
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                        break;
                                }
                            });
                        }

                        $assignAreas = $assignAreas->get();

                        foreach ($assignAreas as $assignArea) {
                            $arr_data[] = $this->resultFilter(
                                $assignArea->employeeProfile,
                                'section',
                            );
                        }

                        // Get all units directly under the section
                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                                ->where('unit_id', $unit->id)
                                ->limit($limit);

                            if ($employment_type) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                    $q->where('employment_type_id', $employment_type);
                                });
                            }

                            if ($start_date && $end_date) {
                                $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                    switch ($period_type) {
                                        case 'monthly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                            break;
                                        case 'quarterly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                            break;
                                    }
                                });
                            }

                            $assignAreas = $assignAreas->get();

                            foreach ($assignAreas as $assignArea) {
                                $arr_data[] = $this->resultFilter(
                                    $assignArea->employeeProfile,
                                    'unit',
                                );
                            }
                        }
                    }
                    break;
                case 'department':
                    $assignAreas = AssignArea::with(['employeeProfile', 'department', 'section', 'unit'])
                        ->where('department_id', $area_id)
                        ->where('employee_profile_id', '<>', 1)
                        ->limit($limit);

                    if ($employment_type) {
                        $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                            $q->where('employment_type_id', $employment_type);
                        });
                    }

                    if ($start_date && $end_date) {
                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                            switch ($period_type) {
                                case 'monthly':
                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                    break;
                                case 'quarterly':
                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                    break;
                            }
                        });
                    }

                    $assignAreas = $assignAreas->get();

                    foreach ($assignAreas as $assignedArea) {
                        $arr_data[] = $this->resultFilter(
                            $assignedArea->employeeProfile,
                            'department',
                        );
                    }
                    $sections = Section::where('department_id', $area_id)->get();
                    foreach ($sections as $section) {
                        $assignAreas = AssignArea::with(['employeeProfile', 'department', 'section', 'unit'])
                            ->where('section_id', $section->id)
                            ->limit($limit);

                        if ($employment_type) {
                            $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                $q->where('employment_type_id', $employment_type);
                            });
                        }

                        if ($start_date && $end_date) {
                            $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                switch ($period_type) {
                                    case 'monthly':
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                        break;
                                    case 'quarterly':
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                        break;
                                }
                            });
                        }

                        $assignAreas = $assignAreas->get();

                        foreach ($assignAreas as $assignArea) {
                            $arr_data[] = $this->resultFilter(
                                $assignArea->employeeProfile,
                                'section',
                            );
                        }

                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $assignAreas = AssignArea::with(['employeeProfile', 'department', 'section', 'unit'])
                                ->where('unit_id', $unit->id)
                                ->limit($limit);

                            if ($employment_type) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                    $q->where('employment_type_id', $employment_type);
                                });
                            }

                            if ($start_date && $end_date) {
                                $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                    switch ($period_type) {
                                        case 'monthly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                            break;
                                        case 'quarterly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                            break;
                                    }
                                });
                            }

                            $assignAreas = $assignAreas->get();

                            foreach ($assignAreas as $assignArea) {
                                $arr_data[] = $this->resultFilter(
                                    $assignArea->employeeProfile,
                                    'unit',
                                );
                            }
                        }
                    }
                    break;
                case 'section':
                    $assignAreas = AssignArea::with(['employeeProfile', 'section', 'unit'])
                        ->where('section_id', $area_id)
                        ->where('employee_profile_id', '!=', 1)
                        ->limit($limit);

                    if ($employment_type) {
                        $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                            $q->where('employment_type_id', $employment_type);
                        });
                    }

                    if ($start_date && $end_date) {
                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                            switch ($period_type) {
                                case 'monthly':
                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                    break;
                                case 'quarterly':
                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                    break;
                            }
                        });
                    }

                    $assignAreas = $assignAreas->get();

                    foreach ($assignAreas as $assignArea) {
                        $arr_data[] = $this->resultFilter(
                            $assignArea->employeeProfile,
                            'section',
                        );
                    }
                    $units = Unit::where('section_id', $area_id)->get();
                    foreach ($units as $unit) {
                        $assignAreas = AssignArea::with(['employeeProfile', 'department', 'section', 'unit'])
                            ->where('unit_id', $unit->id)
                            ->limit($limit);

                        if ($employment_type) {
                            $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                $q->where('employment_type_id', $employment_type);
                            });
                        }

                        if ($start_date && $end_date) {
                            $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                switch ($period_type) {
                                    case 'monthly':
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                        break;
                                    case 'quarterly':
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                        break;
                                }
                            });
                        }

                        $assignAreas = $assignAreas->get();

                        foreach ($assignAreas as $assignArea) {
                            $arr_data[] = $this->resultFilter(
                                $assignArea->employeeProfile,
                                'unit',
                            );
                        }
                    }
                    break;
                case 'unit':
                    $assignAreas = AssignArea::with(['employeeProfile', 'unit'])
                        ->where('unit_id', $area_id)
                        ->where('employee_profile_id', '<>', 1)
                        ->limit($limit);

                    if ($employment_type) {
                        $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                            $q->where('employment_type_id', $employment_type);
                        });
                    }

                    if ($start_date && $end_date) {
                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                            switch ($period_type) {
                                case 'monthly':
                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                    break;
                                case 'quarterly':
                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                    break;
                            }
                        });
                    }

                    $assignAreas = $assignAreas->get();

                    foreach ($assignAreas as $assignArea) {
                        $arr_data[] = $this->resultFilter(
                            $assignArea->employeeProfile,
                            'unit',
                        );
                    }
                    break;
            }
        } catch (\Exception $e) {
            // Log error and return empty array in case of exception
            Helpers::errorLog($this->CONTROLLER_NAME, 'getEmployeeFilter', $e->getMessage());
            return [];
        }

        return $arr_data;
    }

    /**
     * Counts the days of tardiness based on late check-ins and undertime.
     *
     * @param \App\Models\EmployeeProfile $employee
     * @return array
     */
    private function countTardinessDays($employee)
    {
        $tardinessDays = 0;
        $undertimeDays = 0;

        try {
            foreach ($employee->dailyTimeRecords as $record) {
                if ($record->first_in && Carbon::parse($record->first_in)->gt(Carbon::parse($record->dtr_date)->startOfDay()->addHours(8))) {
                    $tardinessDays++;
                }

                if ($record->undertime_minutes > 0) {
                    $undertimeDays++;
                }
            }
        } catch (\Exception $e) {
            // Log error and continue processing
            Helpers::errorLog($this->CONTROLLER_NAME, 'countTardinessDays', $e->getMessage());
        }

        return [
            'tardiness_days' => $tardinessDays,
            'undertime_days' => $undertimeDays
        ];
    }

    /**
     * Formats the employee data for the report.
     *
     * @param \App\Models\EmployeeProfile $employee
     * @param string $sector
     * @return array
     */
    private function resultFilter($employee, $sector)
    {
        $dailyTimeRecords = $employee->dailyTimeRecords ?? [];
        $total_undertime_minutes = $dailyTimeRecords->sum('undertime_minutes');
        $tardinessAndUndertime = $this->countTardinessDays($employee);

        $arr_data = [
            'id' => $employee->id,
            'employee_id' => $employee->employee_id,
            'employee_name' => $employee->personalInformation->employeeName(),
            'employment_type' => $employee->employment_type_id,
            'designation_name' => $employee->findDesignation()['name'],
            'designation_code' => $employee->findDesignation()['code'],
            'sector' => $sector,
            'area_name' => $employee->assignedArea->findDetails()['details']['name'],
            'area_code' => $employee->assignedArea->findDetails()['details']['code'],
            'total_undertime_minutes' => $total_undertime_minutes,
            'tardiness_days' => $tardinessAndUndertime['tardiness_days'],
            'undertime_days' => $tardinessAndUndertime['undertime_days']
        ];

        return $arr_data;
    }
}
