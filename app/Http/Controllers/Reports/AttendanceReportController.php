<?php

namespace App\Http\Controllers\Reports;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceReportResource;
use App\Models\DailyTimeRecords;
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

class AttendanceReportController extends Controller
{
    private $CONTROLLER_NAME = "Attendance Reports";

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

            $result = $this->getEmployeeFilter(
                $area_id,
                $sector,
                $employment_type,
                $start_date,
                $end_date,
                $period_type
            );

            return response()->json([
                'count' => count($result),
                'data' => $result,
                'message' => 'Successfully retrieved data.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterLeave', $th->getMessage());
            return response()->json(
                [
                    'message' => $th->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    private function getEmployeeFilter($area_id, $sector, $employment_type, $start_date, $end_date, $period_type)
    {
        $arr_data = [];

        switch ($sector) {
            case 'division':
                // $division = Division::find($area_id);
                $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                    ->where('division_id', $area_id)
                    ->where('employee_profile_id', '<>', 1);

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
                        ->where('department_id', $department->id);

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
                            ->where('section_id', $section->id);

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
                                ->where('unit_id', $unit->id);

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
                        ->where('section_id', $section->id);

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
                            ->where('unit_id', $unit->id);
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
                    ->where('employee_profile_id', '<>', 1);

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
                        ->where('section_id', $section->id);

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
                            ->where('unit_id', $unit->id);

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
                    ->where('employee_profile_id', '!=', 1);

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
                        ->where('unit_id', $unit->id);

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
                    ->where('employee_profile_id', '<>', 1);

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

                $assignAreas = $assignAreas->get();;

                foreach ($assignAreas as $assignArea) {
                    $arr_data[] = $this->resultFilter(
                        $assignArea->employeeProfile,
                        'unit',
                    );
                }
                break;
        }

        return $arr_data;
    }

    private function resultFilter($employee, $sector)
    {
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
        ];

        return $arr_data;
    }
}
