<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Support\Facades\DB;
use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\AssignArea;
use App\Models\Division;
use App\Models\Department;
use App\Models\EmployeeSchedule;
use App\Models\Section;
use App\Models\Unit;;


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
    public function filterAttendanceTardiness(Request $request)
    {
        try {
            // Get filters from the request
            $area_id = $request->area_id;
            $area_under = $request->area_under;
            $sector = $request->sector;
            $employment_type = $request->employment_type_id;
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $period_type = $request->period_type; // quarterly or monthly
            $limit = $request->limit ?? 100; // default limit is 100

            $result = $this->getEmployeesTardinessFilter(
                $area_id,
                $area_under,
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
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterAttendanceTardiness', $th->getMessage());
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
    private function getEmployeesTardinessFilter($area_id, $area_under, $sector, $employment_type, $start_date, $end_date, $period_type, $limit)
    {
        $arr_data = [];

        try {
            if (is_null($area_id) && is_null($area_under) && is_null($sector) && is_null($employment_type) && is_null($start_date) && is_null($end_date) && is_null($period_type)) {
                // If no filters are provided, return all employees with tardiness records
                $assignAreas = AssignArea::with(['employeeProfile'])
                    ->whereHas('employeeProfile.dailyTimeRecords', function ($q) {
                        $q->whereNotNull('first_in')
                            ->orWhere('undertime_minutes', '>', 0);
                    })
                    ->get();

                foreach ($assignAreas as $assignArea) {
                    $arr_data[] = $this->resultTardinessFilter($assignArea->employeeProfile, $sector, $period_type, $start_date, $end_date);
                }
            } else {
                switch ($sector) {
                    case 'division':
                        switch ($area_under) {
                            case 'all':
                                $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                                    ->where('division_id', $area_id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->limit($limit);

                                if ($employment_type) {
                                    $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                        $q->where('employment_type_id', $employment_type);
                                    });
                                }

                                if ($period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                        switch ($period_type) {
                                            case 'monthly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                break;
                                            case 'quarterly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                break;
                                            case 'yearly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                break;
                                        }
                                    });
                                }

                                // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                if ($start_date && $end_date && !$period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                    });
                                }

                                $assignAreas = $assignAreas->get();

                                foreach ($assignAreas as $assignArea) {
                                    $arr_data[] = $this->resultTardinessFilter(
                                        $assignArea->employeeProfile,
                                        'division',
                                        $period_type,
                                        $start_date,
                                        $end_date
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


                                    if ($period_type) {
                                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                            switch ($period_type) {
                                                case 'monthly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                    break;
                                                case 'quarterly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                    break;
                                                case 'yearly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                    break;
                                            }
                                        });
                                    }

                                    // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                    if ($start_date && $end_date && !$period_type) {
                                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                        });
                                    }

                                    $assignAreas = $assignAreas->get();

                                    foreach ($assignAreas as $assignArea) {
                                        $arr_data[] = $this->resultTardinessFilter(
                                            $assignArea->employeeProfile,
                                            'department',
                                            $period_type,
                                            $start_date,
                                            $end_date
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


                                        if ($period_type) {
                                            $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                                switch ($period_type) {
                                                    case 'monthly':
                                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                        break;
                                                    case 'quarterly':
                                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                        break;
                                                    case 'yearly':
                                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                        break;
                                                }
                                            });
                                        }

                                        // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                        if ($start_date && $end_date && !$period_type) {
                                            $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                            });
                                        }

                                        $assignAreas = $assignAreas->get();

                                        foreach ($assignAreas as $assignArea) {
                                            $arr_data[] = $this->resultTardinessFilter(
                                                $assignArea->employeeProfile,
                                                'section',
                                                $period_type,
                                                $start_date,
                                                $end_date
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


                                            if ($period_type) {
                                                $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                                    switch ($period_type) {
                                                        case 'monthly':
                                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                            break;
                                                        case 'quarterly':
                                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                            break;
                                                        case 'yearly':
                                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                            break;
                                                    }
                                                });
                                            }

                                            // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                            if ($start_date && $end_date && !$period_type) {
                                                $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                                });
                                            }

                                            $assignAreas = $assignAreas->get();

                                            foreach ($assignAreas as $assignArea) {
                                                $arr_data[] = $this->resultTardinessFilter(
                                                    $assignArea->employeeProfile,
                                                    'unit',
                                                    $period_type,
                                                    $start_date,
                                                    $end_date
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

                                    if ($period_type) {
                                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                            switch ($period_type) {
                                                case 'monthly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                    break;
                                                case 'quarterly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                    break;
                                                case 'yearly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                    break;
                                            }
                                        });
                                    }

                                    // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                    if ($start_date && $end_date && !$period_type) {
                                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                        });
                                    }

                                    $assignAreas = $assignAreas->get();

                                    foreach ($assignAreas as $assignArea) {
                                        $arr_data[] = $this->resultTardinessFilter(
                                            $assignArea->employeeProfile,
                                            'section',
                                            $period_type,
                                            $start_date,
                                            $end_date
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


                                        if ($period_type) {
                                            $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                                switch ($period_type) {
                                                    case 'monthly':
                                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                        break;
                                                    case 'quarterly':
                                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                        break;
                                                    case 'yearly':
                                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                        break;
                                                }
                                            });
                                        }

                                        // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                        if ($start_date && $end_date && !$period_type) {
                                            $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                            });
                                        }

                                        $assignAreas = $assignAreas->get();

                                        foreach ($assignAreas as $assignArea) {
                                            $arr_data[] = $this->resultTardinessFilter(
                                                $assignArea->employeeProfile,
                                                'unit',
                                                $period_type,
                                                $start_date,
                                                $end_date
                                            );
                                        }
                                    }
                                }
                                break;
                            case 'staff':
                                $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                                    ->where('division_id', $area_id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->limit($limit);

                                if ($employment_type) {
                                    $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                        $q->where('employment_type_id', $employment_type);
                                    });
                                }
                                if ($period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                        switch ($period_type) {
                                            case 'monthly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                break;
                                            case 'quarterly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                break;
                                            case 'yearly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                break;
                                        }
                                    });
                                }

                                // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                if ($start_date && $end_date && !$period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                    });
                                }

                                $assignAreas = $assignAreas->get();

                                foreach ($assignAreas as $assignArea) {
                                    $arr_data[] = $this->resultTardinessFilter(
                                        $assignArea->employeeProfile,
                                        'division',
                                        $period_type,
                                        $start_date,
                                        $end_date
                                    );
                                }
                                break;
                        }
                        break;
                    case 'department':
                        switch ($area_under) {
                            case 'all':
                                $assignAreas = AssignArea::with(['employeeProfile', 'department', 'section', 'unit'])
                                    ->where('department_id', $area_id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->limit($limit);

                                if ($employment_type) {
                                    $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                        $q->where('employment_type_id', $employment_type);
                                    });
                                }


                                if ($period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                        switch ($period_type) {
                                            case 'monthly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                break;
                                            case 'quarterly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                break;
                                            case 'yearly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                break;
                                        }
                                    });
                                }

                                // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                if ($start_date && $end_date && !$period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                    });
                                }

                                $assignAreas = $assignAreas->get();

                                foreach ($assignAreas as $assignedArea) {
                                    $arr_data[] = $this->resultTardinessFilter(
                                        $assignedArea->employeeProfile,
                                        'department',
                                        $period_type,
                                        $start_date,
                                        $end_date
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


                                    if ($period_type) {
                                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                            switch ($period_type) {
                                                case 'monthly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                    break;
                                                case 'quarterly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                    break;
                                                case 'yearly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                    break;
                                            }
                                        });
                                    }

                                    // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                    if ($start_date && $end_date && !$period_type) {
                                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                        });
                                    }

                                    $assignAreas = $assignAreas->get();

                                    foreach ($assignAreas as $assignArea) {
                                        $arr_data[] = $this->resultTardinessFilter(
                                            $assignArea->employeeProfile,
                                            'section',
                                            $period_type,
                                            $start_date,
                                            $end_date
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

                                        if ($period_type) {
                                            $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                                switch ($period_type) {
                                                    case 'monthly':
                                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                        break;
                                                    case 'quarterly':
                                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                        break;
                                                    case 'yearly':
                                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                        break;
                                                }
                                            });
                                        }

                                        // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                        if ($start_date && $end_date && !$period_type) {
                                            $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                            });
                                        }

                                        $assignAreas = $assignAreas->get();

                                        foreach ($assignAreas as $assignArea) {
                                            $arr_data[] = $this->resultTardinessFilter(
                                                $assignArea->employeeProfile,
                                                'unit',
                                                $period_type,
                                                $start_date,
                                                $end_date
                                            );
                                        }
                                    }
                                }
                                break;
                            case 'staff':
                                $assignAreas = AssignArea::with(['employeeProfile', 'department', 'section', 'unit'])
                                    ->where('department_id', $area_id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->limit($limit);

                                if ($employment_type) {
                                    $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                        $q->where('employment_type_id', $employment_type);
                                    });
                                }

                                if ($period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                        switch ($period_type) {
                                            case 'monthly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                break;
                                            case 'quarterly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                break;
                                            case 'yearly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                break;
                                        }
                                    });
                                }

                                // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                if ($start_date && $end_date && !$period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                    });
                                }

                                $assignAreas = $assignAreas->get();

                                foreach ($assignAreas as $assignedArea) {
                                    $arr_data[] = $this->resultTardinessFilter(
                                        $assignedArea->employeeProfile,
                                        'department',
                                        $period_type,
                                        $start_date,
                                        $end_date
                                    );
                                }
                                break;
                        }
                        break;
                    case 'section':
                        switch ($area_under) {
                            case 'all':
                                $assignAreas = AssignArea::with(['employeeProfile', 'section', 'unit'])
                                    ->where('section_id', $area_id)
                                    ->where('employee_profile_id', '!=', 1)
                                    ->limit($limit);

                                if ($employment_type) {
                                    $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                        $q->where('employment_type_id', $employment_type);
                                    });
                                }

                                if ($period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                        switch ($period_type) {
                                            case 'monthly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                break;
                                            case 'quarterly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                break;
                                            case 'yearly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                break;
                                        }
                                    });
                                }

                                // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                if ($start_date && $end_date && !$period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                    });
                                }

                                $assignAreas = $assignAreas->get();

                                foreach ($assignAreas as $assignArea) {
                                    $arr_data[] = $this->resultTardinessFilter(
                                        $assignArea->employeeProfile,
                                        'section',
                                        $period_type,
                                        $start_date,
                                        $end_date
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

                                    if ($period_type) {
                                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                            switch ($period_type) {
                                                case 'monthly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                    break;
                                                case 'quarterly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                    break;
                                                case 'yearly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                    break;
                                            }
                                        });
                                    }

                                    // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                    if ($start_date && $end_date && !$period_type) {
                                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                        });
                                    }

                                    $assignAreas = $assignAreas->get();

                                    foreach ($assignAreas as $assignArea) {
                                        $arr_data[] = $this->resultTardinessFilter(
                                            $assignArea->employeeProfile,
                                            'unit',
                                            $period_type,
                                            $start_date,
                                            $end_date
                                        );
                                    }
                                }
                                break;
                            case 'staff':
                                $assignAreas = AssignArea::with(['employeeProfile', 'section', 'unit'])
                                    ->where('section_id', $area_id)
                                    ->where('employee_profile_id', '!=', 1)
                                    ->limit($limit);

                                if ($employment_type) {
                                    $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                        $q->where('employment_type_id', $employment_type);
                                    });
                                }

                                if ($period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                        switch ($period_type) {
                                            case 'monthly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                break;
                                            case 'quarterly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                break;
                                            case 'yearly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                break;
                                        }
                                    });
                                }

                                // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                if ($start_date && $end_date && !$period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                    });
                                }

                                $assignAreas = $assignAreas->get();

                                foreach ($assignAreas as $assignArea) {
                                    $arr_data[] = $this->resultTardinessFilter(
                                        $assignArea->employeeProfile,
                                        'section',
                                        $period_type,
                                        $start_date,
                                        $end_date
                                    );
                                }
                                break;
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

                        if ($period_type) {
                            $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                switch ($period_type) {
                                    case 'monthly':
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                        break;
                                    case 'quarterly':
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                        break;
                                    case 'yearly':
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                        break;
                                }
                            });
                        }

                        // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                        if ($start_date && $end_date && !$period_type) {
                            $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                            });
                        }

                        $assignAreas = $assignAreas->get();

                        foreach ($assignAreas as $assignArea) {
                            $arr_data[] = $this->resultTardinessFilter(
                                $assignArea->employeeProfile,
                                'unit',
                                $period_type,
                                $start_date,
                                $end_date
                            );
                        }
                        break;
                }
            }


            // Sort by highest tardiness days by default
            usort($arr_data, function ($a, $b) {
                return $b['tardiness_days'] <=> $a['tardiness_days'];
            });

            // Limit the results
            $arr_data = array_slice($arr_data, 0, $limit);
        } catch (\Exception $e) {
            // Log error and return empty array in case of exception
            Helpers::errorLog($this->CONTROLLER_NAME, 'getEmployeesTardinessFilter', $e->getMessage());
            return response()->json(
                ['message'  => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $arr_data;
    }

    /**
     * Counts the days of tardiness based on late check-ins and undertime.
     *
     * @param \App\Models\EmployeeProfile $employee
     * @return array
     */
    private function countTardinessDays($employee, $start_date = null, $end_date = null)
    {
        $tardinessDays = 0;
        $undertimeDays = 0;

        try {
            foreach ($employee->dailyTimeRecords as $record) {
                $has_schedule = $start_date && $end_date ? Helpers::hasSchedule($start_date, $end_date, $employee->id) : true;

                if ($start_date && $end_date) {
                    if (!$has_schedule) {
                        return response()->json(['message' => "You don't have a schedule within the specified date range."], Response::HTTP_FORBIDDEN);
                    }

                    if (Carbon::parse($record->dtr_date)->between($start_date, $end_date)) {
                        if ($record->first_in && Carbon::parse($record->first_in)->gt(Carbon::parse($record->dtr_date)->startOfDay()->addHours(8))) {
                            $tardinessDays++;
                        }
                        if ($record->undertime_minutes > 0 && (Carbon::parse($record->dtr_date)->between($start_date, $end_date))) {
                            $undertimeDays++;
                        }
                    }
                } else {
                    if ($record->first_in && Carbon::parse($record->first_in)->gt(Carbon::parse($record->dtr_date)->startOfDay()->addHours(8))) {
                        $tardinessDays++;
                    }
                    if ($record->undertime_minutes > 0) {
                        $undertimeDays++;
                    }
                }
            }
        } catch (\Exception $e) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'countTardinessDays', $e->getMessage());
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
    private function resultTardinessFilter($employee, $sector, $period_type, $start_date, $end_date)
    {
        $date_range =  [Carbon::parse($start_date), Carbon::parse($end_date)];

        $dailyTimeRecords = $employee->dailyTimeRecords ?? [];
        $total_undertime_minutes = $dailyTimeRecords->whereBetween('dtr_date', $date_range)->sum('undertime_minutes');
        $tardinessAndUndertime = $this->countTardinessDays($employee, $start_date, $end_date);

        $arr_data = [
            'id' => $employee->id,
            'employee_id' => $employee->employee_id,
            'employee_name' => $employee->personalInformation->employeeName(),
            'employment_type_id' => $employee->employmentType->id,
            'employment_type_name' => $employee->employmentType->name,
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

    /**
     *  ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
     *  ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
     *  ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
     */

    /**
     * Filters attendance records based on given criteria.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filterAttendanceAbsenteeism(Request $request)
    {
        try {
            // Get filters from the request
            $area_id = $request->area_id;
            $area_under = $request->area_under;
            $sector = $request->sector;
            $employment_type = $request->employment_type_id;
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $period_type = $request->period_type; // quarterly or monthly
            $without_official_leave = $request->without_official_leave;
            $without_pay = $request->without_pay;
            $limit = $request->limit ?? 100; // default limit is 100

            $result = $this->getEmployeesAbsenteeismFilter(
                $area_id,
                $area_under,
                $sector,
                $employment_type,
                $start_date,
                $end_date,
                $period_type,
                $without_official_leave,
                $without_pay,
                $limit
            );

            return response()->json([
                'count' => count($result),
                'data' => $result,
                'message' => 'Successfully retrieved data.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            // Log the error and return an internal server error response
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterAttendanceAbsenteeism', $th->getMessage());
            return response()->json(
                [
                    'message' => $th->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    private function getEmployeesAbsenteeismFilter($area_id, $area_under, $sector, $employment_type, $start_date, $end_date, $period_type, $without_official_leave, $without_pay, $limit)
    {
        $arr_data = [];
        try {
            switch ($sector) {
                case 'division':
                    switch ($area_under) {
                        case 'all':
                            $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->limit($limit);

                            if ($employment_type) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                    $q->where('employment_type_id', $employment_type);
                                });
                            }

                            if ($period_type) {
                                $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                    switch ($period_type) {
                                        case 'monthly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                            break;
                                        case 'quarterly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                            break;
                                        case 'yearly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                            break;
                                    }
                                });
                            }

                            // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                            if ($start_date && $end_date && !$period_type) {
                                $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                });
                            }

                            if ($without_official_leave) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                    $q->whereDoesntHave('leaveApplications', function ($q) use ($start_date, $end_date) {
                                        $q->whereBetween('date_from', [$start_date, $end_date])
                                            ->orWhereBetween('date_to', [$start_date, $end_date]);
                                    });
                                });
                            }

                            if ($without_pay) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile.leaveApplications', function ($q) use ($start_date, $end_date) {
                                    $q->where('without_pay', 1)
                                        ->whereBetween('date_from', [$start_date, $end_date])
                                        ->orWhereBetween('date_to', [$start_date, $end_date]);
                                });
                            }

                            $assignAreas = $assignAreas->get();

                            foreach ($assignAreas as $assignArea) {
                                $arr_data[] = $this->resultAbsenteeismFilter(
                                    $assignArea->employeeProfile,
                                    'division',
                                    $period_type,
                                    $start_date,
                                    $end_date
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

                                if ($period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                        switch ($period_type) {
                                            case 'monthly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                break;
                                            case 'quarterly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                break;
                                            case 'yearly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                break;
                                        }
                                    });
                                }

                                // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                if ($start_date && $end_date && !$period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                    });
                                }

                                if ($without_official_leave) {
                                    $assignAreas = $assignAreas->whereHas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                        $q->whereDoesntHave('leaveApplications', function ($q) use ($start_date, $end_date) {
                                            $q->whereBetween('date_from', [$start_date, $end_date])
                                                ->orWhereBetween('date_to', [$start_date, $end_date]);
                                        });
                                    });
                                }

                                if ($without_pay) {
                                    $assignAreas = $assignAreas->whereHas('employeeProfile.leaveApplications', function ($q) use ($start_date, $end_date) {
                                        $q->where('without_pay', 1)
                                            ->whereBetween('date_from', [$start_date, $end_date])
                                            ->orWhereBetween('date_to', [$start_date, $end_date]);
                                    });
                                }

                                $assignAreas = $assignAreas->get();

                                foreach ($assignAreas as $assignArea) {
                                    $arr_data[] = $this->resultAbsenteeismFilter(
                                        $assignArea->employeeProfile,
                                        'department',
                                        $period_type,
                                        $start_date,
                                        $end_date
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

                                    if ($period_type) {
                                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                            switch ($period_type) {
                                                case 'monthly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                    break;
                                                case 'quarterly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                    break;
                                                case 'yearly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                    break;
                                            }
                                        });
                                    }

                                    // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                    if ($start_date && $end_date && !$period_type) {
                                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                        });
                                    }

                                    if ($without_official_leave) {
                                        $assignAreas = $assignAreas->whereHas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                            $q->whereDoesntHave('leaveApplications', function ($q) use ($start_date, $end_date) {
                                                $q->whereBetween('date_from', [$start_date, $end_date])
                                                    ->orWhereBetween('date_to', [$start_date, $end_date]);
                                            });
                                        });
                                    }

                                    if ($without_pay) {
                                        $assignAreas = $assignAreas->whereHas('employeeProfile.leaveApplications', function ($q) use ($start_date, $end_date) {
                                            $q->where('without_pay', 1)
                                                ->whereBetween('date_from', [$start_date, $end_date])
                                                ->orWhereBetween('date_to', [$start_date, $end_date]);
                                        });
                                    }

                                    $assignAreas = $assignAreas->get();

                                    foreach ($assignAreas as $assignArea) {
                                        $arr_data[] = $this->resultAbsenteeismFilter(
                                            $assignArea->employeeProfile,
                                            'section',
                                            $period_type,
                                            $start_date,
                                            $end_date
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

                                        if ($period_type) {
                                            $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                                switch ($period_type) {
                                                    case 'monthly':
                                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                        break;
                                                    case 'quarterly':
                                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                        break;
                                                    case 'yearly':
                                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                        break;
                                                }
                                            });
                                        }

                                        // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                        if ($start_date && $end_date && !$period_type) {
                                            $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                            });
                                        }

                                        if ($without_official_leave) {
                                            $assignAreas = $assignAreas->whereHas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                                $q->whereDoesntHave('leaveApplications', function ($q) use ($start_date, $end_date) {
                                                    $q->whereBetween('date_from', [$start_date, $end_date])
                                                        ->orWhereBetween('date_to', [$start_date, $end_date]);
                                                });
                                            });
                                        }

                                        if ($without_pay) {
                                            $assignAreas = $assignAreas->whereHas('employeeProfile.leaveApplications', function ($q) use ($start_date, $end_date) {
                                                $q->where('without_pay', 1)
                                                    ->whereBetween('date_from', [$start_date, $end_date])
                                                    ->orWhereBetween('date_to', [$start_date, $end_date]);
                                            });
                                        }

                                        $assignAreas = $assignAreas->get();

                                        foreach ($assignAreas as $assignArea) {
                                            $arr_data[] = $this->resultAbsenteeismFilter(
                                                $assignArea->employeeProfile,
                                                'unit',
                                                $period_type,
                                                $start_date,
                                                $end_date
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

                                if ($period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                        switch ($period_type) {
                                            case 'monthly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                break;
                                            case 'quarterly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                break;
                                            case 'yearly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                break;
                                        }
                                    });
                                }

                                // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                if ($start_date && $end_date && !$period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                    });
                                }

                                if ($without_official_leave) {
                                    $assignAreas = $assignAreas->whereHas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                        $q->whereDoesntHave('leaveApplications', function ($q) use ($start_date, $end_date) {
                                            $q->whereBetween('date_from', [$start_date, $end_date])
                                                ->orWhereBetween('date_to', [$start_date, $end_date]);
                                        });
                                    });
                                }

                                if ($without_pay) {
                                    $assignAreas = $assignAreas->whereHas('employeeProfile.leaveApplications', function ($q) use ($start_date, $end_date) {
                                        $q->where('without_pay', 1)
                                            ->whereBetween('date_from', [$start_date, $end_date])
                                            ->orWhereBetween('date_to', [$start_date, $end_date]);
                                    });
                                }

                                $assignAreas = $assignAreas->get();

                                foreach ($assignAreas as $assignArea) {
                                    $arr_data[] = $this->resultAbsenteeismFilter(
                                        $assignArea->employeeProfile,
                                        'section',
                                        $period_type,
                                        $start_date,
                                        $end_date
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

                                    if ($period_type) {
                                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                            switch ($period_type) {
                                                case 'monthly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                    break;
                                                case 'quarterly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                    break;
                                                case 'yearly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                    break;
                                            }
                                        });
                                    }

                                    // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                    if ($start_date && $end_date && !$period_type) {
                                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                        });
                                    }

                                    if ($without_official_leave) {
                                        $assignAreas = $assignAreas->whereHas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                            $q->whereDoesntHave('leaveApplications', function ($q) use ($start_date, $end_date) {
                                                $q->whereBetween('date_from', [$start_date, $end_date])
                                                    ->orWhereBetween('date_to', [$start_date, $end_date]);
                                            });
                                        });
                                    }

                                    if ($without_pay) {
                                        $assignAreas = $assignAreas->whereHas('employeeProfile.leaveApplications', function ($q) use ($start_date, $end_date) {
                                            $q->where('without_pay', 1)
                                                ->whereBetween('date_from', [$start_date, $end_date])
                                                ->orWhereBetween('date_to', [$start_date, $end_date]);
                                        });
                                    }

                                    $assignAreas = $assignAreas->get();

                                    foreach ($assignAreas as $assignArea) {
                                        $arr_data[] = $this->resultAbsenteeismFilter(
                                            $assignArea->employeeProfile,
                                            'unit',
                                            $period_type,
                                            $start_date,
                                            $end_date
                                        );
                                    }
                                }
                            }
                            break;
                        case 'staff':
                            $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->limit($limit);

                            if ($employment_type) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                    $q->where('employment_type_id', $employment_type);
                                });
                            }

                            if ($period_type) {
                                $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                    switch ($period_type) {
                                        case 'monthly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                            break;
                                        case 'quarterly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                            break;
                                        case 'yearly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                            break;
                                    }
                                });
                            }

                            // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                            if ($start_date && $end_date && !$period_type) {
                                $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                });
                            }

                            if ($without_official_leave) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                    $q->whereDoesntHave('leaveApplications', function ($q) use ($start_date, $end_date) {
                                        $q->whereBetween('date_from', [$start_date, $end_date])
                                            ->orWhereBetween('date_to', [$start_date, $end_date]);
                                    });
                                });
                            }

                            if ($without_pay) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile.leaveApplications', function ($q) use ($start_date, $end_date) {
                                    $q->where('without_pay', 1)
                                        ->whereBetween('date_from', [$start_date, $end_date])
                                        ->orWhereBetween('date_to', [$start_date, $end_date]);
                                });
                            }

                            $assignAreas = $assignAreas->get();

                            foreach ($assignAreas as $assignArea) {
                                $arr_data[] = $this->resultAbsenteeismFilter(
                                    $assignArea->employeeProfile,
                                    'division',
                                    $period_type,
                                    $start_date,
                                    $end_date
                                );
                            }
                            break;
                    }
                    break;
                case 'department':
                    switch ($area_under) {
                        case 'all':
                            $assignAreas = AssignArea::with(['employeeProfile', 'department', 'section', 'unit'])
                                ->where('department_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->limit($limit);

                            if ($employment_type) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                    $q->where('employment_type_id', $employment_type);
                                });
                            }

                            if ($period_type) {
                                $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                    switch ($period_type) {
                                        case 'monthly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                            break;
                                        case 'quarterly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                            break;
                                        case 'yearly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                            break;
                                    }
                                });
                            }

                            // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                            if ($start_date && $end_date && !$period_type) {
                                $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                });
                            }

                            if ($without_official_leave) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                    $q->whereDoesntHave('leaveApplications', function ($q) use ($start_date, $end_date) {
                                        $q->whereBetween('date_from', [$start_date, $end_date])
                                            ->orWhereBetween('date_to', [$start_date, $end_date]);
                                    });
                                });
                            }

                            if ($without_pay) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile.leaveApplications', function ($q) use ($start_date, $end_date) {
                                    $q->where('without_pay', 1)
                                        ->whereBetween('date_from', [$start_date, $end_date])
                                        ->orWhereBetween('date_to', [$start_date, $end_date]);
                                });
                            }

                            $assignAreas = $assignAreas->get();

                            foreach ($assignAreas as $assignedArea) {
                                $arr_data[] = $this->resultAbsenteeismFilter(
                                    $assignedArea->employeeProfile,
                                    'department',
                                    $period_type,
                                    $start_date,
                                    $end_date
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

                                if ($period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                        switch ($period_type) {
                                            case 'monthly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                break;
                                            case 'quarterly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                break;
                                            case 'yearly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                break;
                                        }
                                    });
                                }

                                // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                if ($start_date && $end_date && !$period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                    });
                                }

                                if ($without_official_leave) {
                                    $assignAreas = $assignAreas->whereHas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                        $q->whereDoesntHave('leaveApplications', function ($q) use ($start_date, $end_date) {
                                            $q->whereBetween('date_from', [$start_date, $end_date])
                                                ->orWhereBetween('date_to', [$start_date, $end_date]);
                                        });
                                    });
                                }

                                if ($without_pay) {
                                    $assignAreas = $assignAreas->whereHas('employeeProfile.leaveApplications', function ($q) use ($start_date, $end_date) {
                                        $q->where('without_pay', 1)
                                            ->whereBetween('date_from', [$start_date, $end_date])
                                            ->orWhereBetween('date_to', [$start_date, $end_date]);
                                    });
                                }

                                $assignAreas = $assignAreas->get();

                                foreach ($assignAreas as $assignArea) {
                                    $arr_data[] = $this->resultAbsenteeismFilter(
                                        $assignArea->employeeProfile,
                                        'section',
                                        $period_type,
                                        $start_date,
                                        $end_date
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

                                    if ($period_type) {
                                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                            switch ($period_type) {
                                                case 'monthly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                    break;
                                                case 'quarterly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                    break;
                                                case 'yearly':
                                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                    break;
                                            }
                                        });
                                    }

                                    // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                    if ($start_date && $end_date && !$period_type) {
                                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                        });
                                    }

                                    if ($without_official_leave) {
                                        $assignAreas = $assignAreas->whereHas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                            $q->whereDoesntHave('leaveApplications', function ($q) use ($start_date, $end_date) {
                                                $q->whereBetween('date_from', [$start_date, $end_date])
                                                    ->orWhereBetween('date_to', [$start_date, $end_date]);
                                            });
                                        });
                                    }

                                    if ($without_pay) {
                                        $assignAreas = $assignAreas->whereHas('employeeProfile.leaveApplications', function ($q) use ($start_date, $end_date) {
                                            $q->where('without_pay', 1)
                                                ->whereBetween('date_from', [$start_date, $end_date])
                                                ->orWhereBetween('date_to', [$start_date, $end_date]);
                                        });
                                    }

                                    $assignAreas = $assignAreas->get();

                                    foreach ($assignAreas as $assignArea) {
                                        $arr_data[] = $this->resultAbsenteeismFilter(
                                            $assignArea->employeeProfile,
                                            'unit',
                                            $period_type,
                                            $start_date,
                                            $end_date
                                        );
                                    }
                                }
                            }
                            break;
                        case 'staff':
                            $assignAreas = AssignArea::with(['employeeProfile', 'department', 'section', 'unit'])
                                ->where('department_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->limit($limit);

                            if ($employment_type) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                    $q->where('employment_type_id', $employment_type);
                                });
                            }

                            if ($period_type) {
                                $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                    switch ($period_type) {
                                        case 'monthly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                            break;
                                        case 'quarterly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                            break;
                                        case 'yearly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                            break;
                                    }
                                });
                            }

                            // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                            if ($start_date && $end_date && !$period_type) {
                                $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                });
                            }

                            if ($without_official_leave) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                    $q->whereDoesntHave('leaveApplications', function ($q) use ($start_date, $end_date) {
                                        $q->whereBetween('date_from', [$start_date, $end_date])
                                            ->orWhereBetween('date_to', [$start_date, $end_date]);
                                    });
                                });
                            }

                            if ($without_pay) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile.leaveApplications', function ($q) use ($start_date, $end_date) {
                                    $q->where('without_pay', 1)
                                        ->whereBetween('date_from', [$start_date, $end_date])
                                        ->orWhereBetween('date_to', [$start_date, $end_date]);
                                });
                            }

                            $assignAreas = $assignAreas->get();

                            foreach ($assignAreas as $assignedArea) {
                                $arr_data[] = $this->resultAbsenteeismFilter(
                                    $assignedArea->employeeProfile,
                                    'department',
                                    $period_type,
                                    $start_date,
                                    $end_date
                                );
                            }
                            break;
                    }
                    break;
                case 'section':
                    switch ($area_under) {
                        case 'all':
                            $assignAreas = AssignArea::with(['employeeProfile', 'section', 'unit'])
                                ->where('section_id', $area_id)
                                ->where('employee_profile_id', '!=', 1)
                                ->limit($limit);

                            if ($employment_type) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                    $q->where('employment_type_id', $employment_type);
                                });
                            }

                            if ($period_type) {
                                $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                    switch ($period_type) {
                                        case 'monthly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                            break;
                                        case 'quarterly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                            break;
                                        case 'yearly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                            break;
                                    }
                                });
                            }

                            // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                            if ($start_date && $end_date && !$period_type) {
                                $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                });
                            }

                            if ($without_official_leave) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                    $q->whereDoesntHave('leaveApplications', function ($q) use ($start_date, $end_date) {
                                        $q->whereBetween('date_from', [$start_date, $end_date])
                                            ->orWhereBetween('date_to', [$start_date, $end_date]);
                                    });
                                });
                            }

                            if ($without_pay) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile.leaveApplications', function ($q) use ($start_date, $end_date) {
                                    $q->where('without_pay', 1)
                                        ->whereBetween('date_from', [$start_date, $end_date])
                                        ->orWhereBetween('date_to', [$start_date, $end_date]);
                                });
                            }

                            $assignAreas = $assignAreas->get();

                            foreach ($assignAreas as $assignArea) {
                                $arr_data[] = $this->resultAbsenteeismFilter(
                                    $assignArea->employeeProfile,
                                    'section',
                                    $period_type,
                                    $start_date,
                                    $end_date
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

                                if ($period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                        switch ($period_type) {
                                            case 'monthly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                                break;
                                            case 'quarterly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                                break;
                                            case 'yearly':
                                                $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                                break;
                                        }
                                    });
                                }

                                if ($without_official_leave) {
                                    $assignAreas = $assignAreas->whereHas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                        $q->whereDoesntHave('leaveApplications', function ($q) use ($start_date, $end_date) {
                                            $q->whereBetween('date_from', [$start_date, $end_date])
                                                ->orWhereBetween('date_to', [$start_date, $end_date]);
                                        });
                                    });
                                }

                                if ($without_pay) {
                                    $assignAreas = $assignAreas->whereHas('employeeProfile.leaveApplications', function ($q) use ($start_date, $end_date) {
                                        $q->where('without_pay', 1)
                                            ->whereBetween('date_from', [$start_date, $end_date])
                                            ->orWhereBetween('date_to', [$start_date, $end_date]);
                                    });
                                }

                                // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                                if ($start_date && $end_date && !$period_type) {
                                    $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                        $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                    });
                                }

                                $assignAreas = $assignAreas->get();

                                foreach ($assignAreas as $assignArea) {
                                    $arr_data[] = $this->resultAbsenteeismFilter(
                                        $assignArea->employeeProfile,
                                        'unit',
                                        $period_type,
                                        $start_date,
                                        $end_date
                                    );
                                }
                            }
                            break;
                        case 'staff':
                            $assignAreas = AssignArea::with(['employeeProfile', 'section', 'unit'])
                                ->where('section_id', $area_id)
                                ->where('employee_profile_id', '!=', 1)
                                ->limit($limit);

                            if ($employment_type) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                    $q->where('employment_type_id', $employment_type);
                                });
                            }

                            if ($period_type) {
                                $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                                    switch ($period_type) {
                                        case 'monthly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                            break;
                                        case 'quarterly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                            break;
                                        case 'yearly':
                                            $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                            break;
                                    }
                                });
                            }

                            // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                            if ($start_date && $end_date && !$period_type) {
                                $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                                });
                            }

                            if ($without_official_leave) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                                    $q->whereDoesntHave('leaveApplications', function ($q) use ($start_date, $end_date) {
                                        $q->whereBetween('date_from', [$start_date, $end_date])
                                            ->orWhereBetween('date_to', [$start_date, $end_date]);
                                    });
                                });
                            }

                            if ($without_pay) {
                                $assignAreas = $assignAreas->whereHas('employeeProfile.leaveApplications', function ($q) use ($start_date, $end_date) {
                                    $q->where('without_pay', 1)
                                        ->whereBetween('date_from', [$start_date, $end_date])
                                        ->orWhereBetween('date_to', [$start_date, $end_date]);
                                });
                            }

                            $assignAreas = $assignAreas->get();

                            foreach ($assignAreas as $assignArea) {
                                $arr_data[] = $this->resultAbsenteeismFilter(
                                    $assignArea->employeeProfile,
                                    'section',
                                    $period_type,
                                    $start_date,
                                    $end_date
                                );
                            }
                            break;
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

                    if ($period_type) {
                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date, $period_type) {
                            switch ($period_type) {
                                case 'monthly':
                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfMonth(), Carbon::parse($end_date)->endOfMonth()]);
                                    break;
                                case 'quarterly':
                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->firstOfQuarter(), Carbon::parse($end_date)->lastOfQuarter()]);
                                    break;
                                case 'yearly':
                                    $q->whereBetween('dtr_date', [Carbon::parse($start_date)->startOfYear(), Carbon::parse($end_date)->endOfYear()]);
                                    break;
                            }
                        });
                    }

                    // Optionally, you can handle the case where $start_date and $end_date are set but $period_type is not.
                    if ($start_date && $end_date && !$period_type) {
                        $assignAreas = $assignAreas->wherehas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                            $q->whereBetween('dtr_date', [Carbon::parse($start_date), Carbon::parse($end_date)]);
                        });
                    }

                    if ($without_official_leave) {
                        $assignAreas = $assignAreas->whereHas('employeeProfile.dailyTimeRecords', function ($q) use ($start_date, $end_date) {
                            $q->whereDoesntHave('leaveApplications', function ($q) use ($start_date, $end_date) {
                                $q->whereBetween('date_from', [$start_date, $end_date])
                                    ->orWhereBetween('date_to', [$start_date, $end_date]);
                            });
                        });
                    }

                    if ($without_pay) {
                        $assignAreas = $assignAreas->whereHas('employeeProfile.leaveApplications', function ($q) use ($start_date, $end_date) {
                            $q->where('without_pay', 1)
                                ->whereBetween('date_from', [$start_date, $end_date])
                                ->orWhereBetween('date_to', [$start_date, $end_date]);
                        });
                    }

                    $assignAreas = $assignAreas->get();

                    foreach ($assignAreas as $assignArea) {
                        $arr_data[] = $this->resultAbsenteeismFilter(
                            $assignArea->employeeProfile,
                            'unit',
                            $period_type,
                            $start_date,
                            $end_date
                        );
                    }
                    break;
            }
        } catch (\Exception $e) {
            // Log error and return empty array in case of exception
            Helpers::errorLog($this->CONTROLLER_NAME, 'getEmployeesTardinessFilter', $e->getMessage());
            return response()->json(
                ['message'  => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $arr_data;
    }

    private function calculateTotalHoursMissed($employee, $start_date = null, $end_date = null)
    {
        $total_hours_missed = 0;
        $remaining_minutes = 0;
        try {
            $query = $employee->dailyTimeRecords();

            if ($start_date && $end_date) {
                $query->whereBetween('dtr_date', [$start_date, $end_date]);
            }

            $dailyTimeRecords = $query->get();

            foreach ($dailyTimeRecords as $record) {
                $has_schedule = $start_date && $end_date ? Helpers::hasSchedule($start_date, $end_date, $employee->id) : true;

                if ($has_schedule && ($record->undertime_minutes > 0)) {
                    $total_hours_missed = floor($record->undertime_minutes / 60);
                    $remaining_minutes = $record->undertime_minutes % 60;
                }
            }
        } catch (\Exception $e) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'calculateTotalHoursMissed', $e->getMessage());
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return [
            'total_hours_missed' => $total_hours_missed,
            'remaining_minutes' => $remaining_minutes
        ];
    }

    private function calculateDaysAbsent($employee, $start_date = null, $end_date = null)
    {
        $days_absent = 0;

        try {
            $query = $employee->dailyTimeRecords();

            if ($start_date && $end_date) {
                $query->whereBetween('dtr_date', [$start_date, $end_date]);
            }

            $dailyTimeRecords = $query->get();

            foreach ($dailyTimeRecords as $record) {
                $has_schedule = $start_date && $end_date ? Helpers::hasSchedule($start_date, $end_date, $employee->id) : true;

                if ($has_schedule) {
                    if ($record->total_working_minutes === 0) {
                        $days_absent++;
                    }
                }
            }
        } catch (\Exception $e) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'calculateDaysAbsent', $e->getMessage());
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $days_absent;
    }


    /**
     * Formats the employee data for the report.
     *
     * @param \App\Models\EmployeeProfile $employee
     * @param string $sector
     * @return array
     */
    private function resultAbsenteeismFilter($employee, $sector,  $period_type, $start_date = null, $end_date = null)
    {
        $total_hours_missed = $this->calculateTotalHoursMissed($employee, $start_date, $end_date);
        $days_absent = $this->calculateDaysAbsent($employee, $start_date, $end_date);
        $arr_data = [
            'id' => $employee->id,
            'employee_id' => $employee->employee_id,
            'employee_name' => $employee->personalInformation->employeeName(),
            'employment_type' => $employee->employmentType->id,
            'employment_type_name' => $employee->employmentType->name,
            'designation_name' => $employee->findDesignation()['name'],
            'designation_code' => $employee->findDesignation()['code'],
            'sector' => $sector,
            'area_name' => $employee->assignedArea->findDetails()['details']['name'],
            'area_code' => $employee->assignedArea->findDetails()['details']['code'],
            'total_hours_missed' => $total_hours_missed['total_hours_missed'],
            'days_absent' => $days_absent,
        ];

        return $arr_data;
    }
}
