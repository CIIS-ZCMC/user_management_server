<?php

namespace App\Http\Controllers\Reports;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\DesignationReportResource;
use App\Http\Resources\EmployeesDetailsReport;
use App\Models\AssignArea;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\PersonalInformation;
use App\Models\Section;
use App\Models\Unit;
use App\Models\WorkExperience;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class EmployeeReportController extends Controller
{
    private $CONTROLLER_NAME = 'Employee Reports';

    public function filterEmployeesByBloodType(Request $request)
    {
        try {
            $employees = collect();
            $sector =  $request->sector;
            $area_id = $request->area_id;
            $blood_type = $request->blood_type;

            if (!$sector && !$area_id) {
                $employees = AssignArea::with(['employeeProfile.personalInformation'])
                    ->where('employee_profile_id', '<>', 1)
                    ->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                        if (!empty($blood_type)) {
                            $q->where('blood_type', $blood_type);
                        }
                    })
                    ->get();
            } else {
                switch ($sector) {
                    case 'division':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation'])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                    if (!empty($blood_type)) {
                                        $q->where('blood_type', $blood_type);
                                    }
                                })
                                ->get()
                        );

                        $departments = Department::where('division_id', $area_id)->get();
                        foreach ($departments as $department) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation'])
                                    ->where('department_id', $department->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                        if (!empty($blood_type)) {
                                            $q->where('blood_type', $blood_type);
                                        }
                                    })
                                    ->get()
                            );

                            $sections = Section::where('department_id', $department->id)->get();
                            foreach ($sections as $section) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation'])
                                        ->where('section_id', $section->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                            if (!empty($blood_type)) {
                                                $q->where('blood_type', $blood_type);
                                            }
                                        })
                                        ->get()
                                );

                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $employees = $employees->merge(
                                        AssignArea::with(['employeeProfile.personalInformation'])
                                            ->where('unit_id', $unit->id)
                                            ->where('employee_profile_id', '<>', 1)
                                            ->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                                if (!empty($blood_type)) {
                                                    $q->where('blood_type', $blood_type);
                                                }
                                            })
                                            ->get()
                                    );
                                }
                            }
                        }

                        // Get sections directly under the division (if any) that are not under any department
                        $sections = Section::where('division_id', $area_id)->whereNull('department_id')->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation'])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                        if (!empty($blood_type)) {
                                            $q->where('blood_type', $blood_type);
                                        }
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation'])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                            if (!empty($blood_type)) {
                                                $q->where('blood_type', $blood_type);
                                            }
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'department':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation'])
                                ->where('department_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                    if (!empty($blood_type)) {
                                        $q->where('blood_type', $blood_type);
                                    }
                                })
                                ->get()
                        );

                        $sections = Section::where('department_id', $area_id)->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation'])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                        if (!empty($blood_type)) {
                                            $q->where('blood_type', $blood_type);
                                        }
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation'])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                            if (!empty($blood_type)) {
                                                $q->where('blood_type', $blood_type);
                                            }
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'section':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation'])
                                ->where('section_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                    if (!empty($blood_type)) {
                                        $q->where('blood_type', $blood_type);
                                    }
                                })
                                ->get()
                        );

                        $units = Unit::where('section_id', $area_id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation'])
                                    ->where('unit_id', $unit->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                        if (!empty($blood_type)) {
                                            $q->where('blood_type', $blood_type);
                                        }
                                    })
                                    ->get()
                            );
                        }
                        break;

                    case 'unit':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation'])
                                ->where('unit_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                    if (!empty($blood_type)) {
                                        $q->where('blood_type', $blood_type);
                                    }
                                })
                                ->get()
                        );
                        break;
                }
            }

            return response()->json([
                'count' => COUNT($employees),
                'data' => EmployeesDetailsReport::collection($employees),
                'message' => 'List of employee blood types retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterEmployyeByBloodType', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function filterEmployeesByCivilStatus(Request $request)
    {
        try {
            $employees = collect();
            $sector =  $request->sector;
            $area_id = $request->area_id;
            $civil_status = $request->civil_status;

            if (!$sector && !$area_id) {
                $employees = AssignArea::with(['employeeProfile.personalInformation'])
                    ->where('employee_profile_id', '<>', 1)
                    ->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                        if (!empty($civil_status)) {
                            $q->where('civil_status', $civil_status);
                        }
                    })
                    ->get();
            } else {
                switch ($sector) {
                    case 'division':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation'])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                    if (!empty($civil_status)) {
                                        $q->where('civil_status', $civil_status);
                                    }
                                })
                                ->get()
                        );

                        $departments = Department::where('division_id', $area_id)->get();
                        foreach ($departments as $department) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation'])
                                    ->where('department_id', $department->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                        if (!empty($civil_status)) {
                                            $q->where('civil_status', $civil_status);
                                        }
                                    })
                                    ->get()
                            );

                            $sections = Section::where('department_id', $department->id)->get();
                            foreach ($sections as $section) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation'])
                                        ->where('section_id', $section->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                            if (!empty($civil_status)) {
                                                $q->where('civil_status', $civil_status);
                                            }
                                        })
                                        ->get()
                                );

                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $employees = $employees->merge(
                                        AssignArea::with(['employeeProfile.personalInformation'])
                                            ->where('unit_id', $unit->id)
                                            ->where('employee_profile_id', '<>', 1)
                                            ->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                                if (!empty($civil_status)) {
                                                    $q->where('civil_status', $civil_status);
                                                }
                                            })
                                            ->get()
                                    );
                                }
                            }
                        }

                        // Get sections directly under the division (if any) that are not under any department
                        $sections = Section::where('division_id', $area_id)->whereNull('department_id')->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation'])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                        if (!empty($civil_status)) {
                                            $q->where('civil_status', $civil_status);
                                        }
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation'])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                            if (!empty($civil_status)) {
                                                $q->where('civil_status', $civil_status);
                                            }
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'department':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation'])
                                ->where('department_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                    if (!empty($civil_status)) {
                                        $q->where('civil_status', $civil_status);
                                    }
                                })
                                ->get()
                        );

                        $sections = Section::where('department_id', $area_id)->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation'])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                        if (!empty($civil_status)) {
                                            $q->where('civil_status', $civil_status);
                                        }
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation'])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                            if (!empty($civil_status)) {
                                                $q->where('civil_status', $civil_status);
                                            }
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'section':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation'])
                                ->where('section_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                    if (!empty($civil_status)) {
                                        $q->where('civil_status', $civil_status);
                                    }
                                })
                                ->get()
                        );

                        $units = Unit::where('section_id', $area_id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation'])
                                    ->where('unit_id', $unit->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                        if (!empty($civil_status)) {
                                            $q->where('civil_status', $civil_status);
                                        }
                                    })
                                    ->get()
                            );
                        }
                        break;

                    case 'unit':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation'])
                                ->where('unit_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                    if (!empty($civil_status)) {
                                        $q->where('civil_status', $civil_status);
                                    }
                                })
                                ->get()
                        );
                        break;
                }
            }

            return response()->json([
                'count' => COUNT($employees),
                'data' => EmployeesDetailsReport::collection($employees),
                'message' => 'List of employee blood types retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterEmployyeByBloodType', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function filterEmployeesByJobStatus(Request $request)
    {
        try {
            $employees = collect();
            $sector =  $request->sector;
            $area_id = $request->area_id;
            $employment_type_id = $request->employment_type_id;

            if (!$sector && !$area_id) {
                $employees = AssignArea::with(['employeeProfile'])
                    ->where('employee_profile_id', '<>', 1)
                    ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                        if (!empty($employment_type_id)) {
                            $q->where('employment_type_id', $employment_type_id);
                        }
                    })
                    ->get();
            } else {
                switch ($sector) {
                    case 'division':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile'])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                    if (!empty($employment_type_id)) {
                                        $q->where('employment_type_id', $employment_type_id);
                                    }
                                })
                                ->get()
                        );

                        $departments = Department::where('division_id', $area_id)->get();
                        foreach ($departments as $department) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile'])
                                    ->where('department_id', $department->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                        if (!empty($employment_type_id)) {
                                            $q->where('employment_type_id', $employment_type_id);
                                        }
                                    })
                                    ->get()
                            );

                            $sections = Section::where('department_id', $department->id)->get();
                            foreach ($sections as $section) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile'])
                                        ->where('section_id', $section->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                            if (!empty($employment_type_id)) {
                                                $q->where('employment_type_id', $employment_type_id);
                                            }
                                        })
                                        ->get()
                                );

                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $employees = $employees->merge(
                                        AssignArea::with(['employeeProfile'])
                                            ->where('unit_id', $unit->id)
                                            ->where('employee_profile_id', '<>', 1)
                                            ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                                if (!empty($employment_type_id)) {
                                                    $q->where('employment_type_id', $employment_type_id);
                                                }
                                            })
                                            ->get()
                                    );
                                }
                            }
                        }

                        // Get sections directly under the division (if any) that are not under any department
                        $sections = Section::where('division_id', $area_id)->whereNull('department_id')->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile'])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                        if (!empty($employment_type_id)) {
                                            $q->where('employment_type_id', $employment_type_id);
                                        }
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile'])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                            if (!empty($employment_type_id)) {
                                                $q->where('employment_type_id', $employment_type_id);
                                            }
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'department':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile'])
                                ->where('department_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                    if (!empty($employment_type_id)) {
                                        $q->where('employment_type_id', $employment_type_id);
                                    }
                                })
                                ->get()
                        );

                        $sections = Section::where('department_id', $area_id)->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile'])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                        if (!empty($employment_type_id)) {
                                            $q->where('employment_type_id', $employment_type_id);
                                        }
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile'])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                            if (!empty($employment_type_id)) {
                                                $q->where('employment_type_id', $employment_type_id);
                                            }
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'section':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile'])
                                ->where('section_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                    if (!empty($employment_type_id)) {
                                        $q->where('employment_type_id', $employment_type_id);
                                    }
                                })
                                ->get()
                        );

                        $units = Unit::where('section_id', $area_id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile'])
                                    ->where('unit_id', $unit->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                        if (!empty($employment_type_id)) {
                                            $q->where('employment_type_id', $employment_type_id);
                                        }
                                    })
                                    ->get()
                            );
                        }
                        break;

                    case 'unit':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile'])
                                ->where('unit_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                    if (!empty($employment_type_id)) {
                                        $q->where('employment_type_id', $employment_type_id);
                                    }
                                })
                                ->get()
                        );
                        break;
                }
            }


            $regular = $employees->filter(function ($row) {
                return $row->employeeProfile->employment_type_id !== 4 && $row->employeeProfile->employment_type_id !== 5;
            });
            $permanent =  $employees->filter(function ($row) {
                return $row->employeeProfile->employment_type_id === 4;
            });
            $job_order = $employees->filter(function ($row) {
                return $row->employeeProfile->employment_type_id === 5;
            });

            return response()->json([
                'count' => [
                    'regular' => COUNT($regular),
                    'permanent' => COUNT($permanent),
                    'job_order' => COUNT($job_order),
                ],
                'data' =>  EmployeesDetailsReport::collection($employees),
                'message' => 'List of employees retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterEmployyeByBloodType', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function filterEmployeesPerPosition(Request $request)
    {
        try {
            $employees = collect();
            $sector =  $request->sector;
            $area_id = $request->area_id;
            $designation_id = $request->designation_id;

            switch ($sector) {
                case 'division':
                    $employees = $employees->merge(
                        AssignArea::with(['employeeProfile'])
                            ->where('division_id', $area_id)
                            ->where('employee_profile_id', '<>', 1)
                            ->whereHas('employeeProfile', function ($q) use ($designation_id) {
                                if (!empty($designation_id)) {
                                    $q->where('designation_id', $designation_id);
                                }
                            })
                            ->get()
                    );

                    $departments = Department::where('division_id', $area_id)->get();
                    foreach ($departments as $department) {
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile'])
                                ->where('department_id', $department->id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile', function ($q) use ($designation_id) {
                                    if (!empty($designation_id)) {
                                        $q->where('designation_id', $designation_id);
                                    }
                                })
                                ->get()
                        );

                        $sections = Section::where('department_id', $department->id)->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile'])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile', function ($q) use ($designation_id) {
                                        if (!empty($designation_id)) {
                                            $q->where('designation_id', $designation_id);
                                        }
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile'])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->whereHas('employeeProfile', function ($q) use ($designation_id) {
                                            if (!empty($designation_id)) {
                                                $q->where('designation_id', $designation_id);
                                            }
                                        })
                                        ->get()
                                );
                            }
                        }
                    }

                    // Get sections directly under the division (if any) that are not under any department
                    $sections = Section::where('division_id', $area_id)->whereNull('department_id')->get();
                    foreach ($sections as $section) {
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile'])
                                ->where('section_id', $section->id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile', function ($q) use ($designation_id) {
                                    if (!empty($designation_id)) {
                                        $q->where('designation_id', $designation_id);
                                    }
                                })
                                ->get()
                        );

                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile'])
                                    ->where('unit_id', $unit->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile', function ($q) use ($designation_id) {
                                        if (!empty($designation_id)) {
                                            $q->where('designation_id', $designation_id);
                                        }
                                    })
                                    ->get()
                            );
                        }
                    }
                    break;

                case 'department':
                    $employees = $employees->merge(
                        AssignArea::with(['employeeProfile'])
                            ->where('department_id', $area_id)
                            ->where('employee_profile_id', '<>', 1)
                            ->whereHas('employeeProfile', function ($q) use ($designation_id) {
                                if (!empty($designation_id)) {
                                    $q->where('designation_id', $designation_id);
                                }
                            })
                            ->get()
                    );

                    $sections = Section::where('department_id', $area_id)->get();
                    foreach ($sections as $section) {
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile'])
                                ->where('section_id', $section->id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile', function ($q) use ($designation_id) {
                                    if (!empty($designation_id)) {
                                        $q->where('designation_id', $designation_id);
                                    }
                                })
                                ->get()
                        );

                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile'])
                                    ->where('unit_id', $unit->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile', function ($q) use ($designation_id) {
                                        if (!empty($designation_id)) {
                                            $q->where('designation_id', $designation_id);
                                        }
                                    })
                                    ->get()
                            );
                        }
                    }
                    break;

                case 'section':
                    $employees = $employees->merge(
                        AssignArea::with(['employeeProfile'])
                            ->where('section_id', $area_id)
                            ->where('employee_profile_id', '<>', 1)
                            ->whereHas('employeeProfile', function ($q) use ($designation_id) {
                                if (!empty($designation_id)) {
                                    $q->where('designation_id', $designation_id);
                                }
                            })
                            ->get()
                    );

                    $units = Unit::where('section_id', $area_id)->get();
                    foreach ($units as $unit) {
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile'])
                                ->where('unit_id', $unit->id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile', function ($q) use ($designation_id) {
                                    if (!empty($designation_id)) {
                                        $q->where('designation_id', $designation_id);
                                    }
                                })
                                ->get()
                        );
                    }
                    break;

                case 'unit':
                    $employees = $employees->merge(
                        AssignArea::with(['employeeProfile'])
                            ->where('unit_id', $area_id)
                            ->where('employee_profile_id', '<>', 1)
                            ->whereHas('employeeProfile', function ($q) use ($designation_id) {
                                if (!empty($designation_id)) {
                                    $q->where('designation_id', $designation_id);
                                }
                            })
                            ->get()
                    );
                    break;
            }

            foreach ($employees as $employee) {
                $designationName = $employee->employeeProfile->findDesignation()['name'];
                if (!isset($designationCounts[$designationName])) {
                    $designationCounts[$designationName] = 0;
                }
                $designationCounts[$designationName]++;
            }

            return response()->json([
                'count' => [
                    'per_designation' => $designationCounts,
                ],
                'data' =>  EmployeesDetailsReport::collection($employees),
                'message' => 'List of employees retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterEmployyeByBloodType', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    ////////////////////////////

    public function allEmployeesBloodType(Request $request)
    {
        try {
            $employee_profiles = EmployeeProfile::whereNotIn('id', [1])->whereNot('employee_id', NULL)->get();

            return response()->json([
                'data' => EmployeesDetailsReport::collection($employee_profiles),
                'count' => COUNT($employee_profiles),
                'message' => 'List of employee blood types retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeesBloodType', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesByBloodType($type, $area_id, $sector, Request $request)
    {
        try {
            $area = strip_tags($area_id);
            $sector = Str::lower(strip_tags($sector));
            $employees = [];

            if ($type == 'null' || $type == null) {
                $employees = EmployeeProfile::select('employee_profiles.*')
                    ->JOIN('assigned_areas as aa', 'aa.employee_profile_id', 'employee_profiles.id')
                    ->JOIN('personal_informations as pi', 'pi.id', 'employee_profiles.personal_information_id')
                    ->where('aa.' . $sector . "_id", $area)
                    ->whereNotIn('employee_profiles.id', [1])
                    ->get();
            } else if ($area_id == 'null' || $area_id == null) {
                $employees = EmployeeProfile::select('employee_profiles.*')
                    ->JOIN('assigned_areas as aa', 'aa.employee_profile_id', 'employee_profiles.id')
                    ->JOIN('personal_informations as pi', 'pi.id', 'employee_profiles.personal_information_id')
                    ->where('pi.blood_type', $type)
                    ->whereNotIn('employee_profiles.id', [1])
                    ->get();
            } else {
                $employees = EmployeeProfile::select('employee_profiles.*')
                    ->JOIN('assigned_areas as aa', 'aa.employee_profile_id', 'employee_profiles.id')
                    ->JOIN('personal_informations as pi', 'pi.id', 'employee_profiles.personal_information_id')
                    ->where('aa.' . $sector . "_id", $area)
                    ->where('pi.blood_type', $type)
                    ->whereNotIn('employee_profiles.id', [1])
                    ->get();
            }

            return response()->json([
                'data' => EmployeesDetailsReport::collection($employees),
                'count' => COUNT($employees),
                'message' => 'List of employee blood types retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeesByBloodType', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function allEmployeesCivilStatus(Request $request)
    {
        try {

            $employee_profiles = EmployeeProfile::whereNotIn('id', [1])->whereNot('employee_id', NULL)->get();

            return response()->json([
                'data' => EmployeesDetailsReport::collection($employee_profiles),
                'count' => COUNT($employee_profiles),
                'message' => 'List of employee blood types retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeesCivilStatus', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesByCivilStatus($civilStatus, $area_id, $sector, Request $request)
    {
        try {
            $area = strip_tags($area_id);
            $sector = Str::lower(strip_tags($sector));
            $employees = [];


            if ($area_id == 'null') {
                $employees = EmployeeProfile::select('employee_profiles.*')
                    ->JOIN('assigned_areas as aa', 'aa.employee_profile_id', 'employee_profiles.id')
                    ->JOIN('personal_informations as pi', 'pi.id', 'employee_profiles.personal_information_id')
                    ->where('pi.civil_status', $civilStatus)
                    ->whereNotIn('employee_profiles.id', [1])
                    ->get();
            } else if ($civilStatus == 'null') {
                $employees = EmployeeProfile::select('employee_profiles.*')
                    ->JOIN('assigned_areas as aa', 'aa.employee_profile_id', 'employee_profiles.id')
                    ->JOIN('personal_informations as pi', 'pi.id', 'employee_profiles.personal_information_id')
                    ->where('aa.' . $sector . "_id", $area)
                    ->whereNotIn('employee_profiles.id', [1])
                    ->get();
            } else {
                $employees = EmployeeProfile::select('employee_profiles.*')
                    ->JOIN('assigned_areas as aa', 'aa.employee_profile_id', 'employee_profiles.id')
                    ->JOIN('personal_informations as pi', 'pi.id', 'employee_profiles.personal_information_id')
                    ->where('aa.' . $sector . "_id", $area)
                    ->where('pi.civil_status', $civilStatus)
                    ->whereNotIn('employee_profiles.id', [1])
                    ->get();
            }

            return response()->json([
                'data' => EmployeesDetailsReport::collection($employees),
                'count' => COUNT($employees),
                'message' => 'List of employee blood types retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeesByBloodType', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function allEmployeesServiceLength(Request $request)
    {
        try {

            // CHECK SHOW IN EMPLOYEE PROFILE
            $personalInformation = PersonalInformation::with('workExperience')->with('employeeProfile')->whereNotIn('id', [1])
                // ->whereHas('employeeProfile', function ($query) {
                //     $query->where('employment_type_id', '!=', 5);
                // })
                ->get();

            // $totalMonths = 0; // Initialize total months variable
            // $totalYears = 0; // Initialize total months variable
            // $totalZcmc = 0;

            $data = [];

            foreach ($personalInformation as $personalInfo) {
                $totalMonths = 0;
                $totalZcmc = 0;
                $total_job_order_service_months = 0;
                $total_job_order_current_service_months = 0;

                if ($personalInfo->workExperience) {
                    foreach ($personalInfo->workExperience as $experience) {
                        $dateFrom = Carbon::parse($experience->date_from);
                        $dateTo = Carbon::parse($experience->date_to);
                        $months = $dateFrom->diffInMonths($dateTo);

                        // Check if the experience is with Zamboanga City Medical Center and is a government office
                        if ($experience->company == "Zamboanga City Medical Center") {

                            // CHECK IF REGULAR
                            if ($experience->government_office === 'Yes') {
                                $totalZcmcMonths = $dateFrom->diffInMonths($dateTo);
                                $totalZcmc += $totalZcmcMonths;
                            }

                            if ($experience->government_office === 'No') {

                                $job_order_service_months = $dateFrom->diffInMonths($dateTo);
                                $total_job_order_service_months += $job_order_service_months;
                            }
                        }

                        $totalMonths += $months;
                    }
                }

                // Calculate current service months
                $current_service_months = 0;
                $employee_profile = $personalInfo->employeeProfile;

                if ($employee_profile->employmentType->id !== 5) {
                    $date_hired = Carbon::parse($employee_profile->date_hired);
                    $current_service_months = $date_hired->diffInMonths(Carbon::now());
                }

                if ($employee_profile->employmentType->id === 5) {
                    $date_hired_jo = Carbon::parse($employee_profile->date_hired);
                    $total_job_order_current_service_months = $date_hired_jo->diffInMonths(Carbon::now());
                }

                // Calculate total months and years
                $total = $current_service_months + $totalMonths;
                $totalYears = floor($total / 12);

                // Calculate total service in ZCMC
                $totalMonthsInZcmc = $totalZcmc + $current_service_months;
                $totalYearsInZcmc = floor($totalMonthsInZcmc / 12);

                // Total years in govt including zcmc
                $total_with_zcmc = $totalMonths + $totalMonthsInZcmc;
                $total_years_with_zcmc = floor($total_with_zcmc / 12);

                // Total years in zcmc as JO / current (id JO)
                $total_jo_months =  $total_job_order_service_months + $total_job_order_current_service_months;
                $total_jo_years =   floor($total_jo_months / 12);

                // Prepare data for output
                $data[] = [
                    'id' => $personalInfo->id,
                    'name' => $personalInfo->name(),
                    'date_hired' => $employee_profile->date_hired,
                    'total_govt_months' => $totalMonthsInZcmc,
                    'total_govt_years' => $totalYears,
                    'total_govt_months_with_zcmc' => $totalMonthsInZcmc,
                    'total_govt_years_with_zcmc' => $total_years_with_zcmc,
                    'total_months_zcmc_regular' => $totalMonthsInZcmc,
                    'total_years_zcmc_regular' => $totalYearsInZcmc,
                    'total_months_zcmc_as_jo' => $total_jo_months,
                    'total_years_zcmc_as_jo' => $total_jo_years,

                ];
            }

            // Now $data array contains the aggregated data for each personal 



            // $display[] = array_filter($data, function ($row) {
            //     return $row['id'] == 520;
            // });

            return response()->json([
                'data' => $data
            ], 401);

            // $currentServiceMonths = 0;
            // if ($employee_profile->employmentType->id !== 5) {
            //     $dateHired = Carbon::parse($employee_profile->date_hired);
            //     $currentServiceMonths = $dateHired->diffInMonths(Carbon::now());
            // }

            // $total = $currentServiceMonths +  $totalMonths;
            // $totalYears = floor($total  / 12);

            // // Total service in ZCMC
            // $totalMonthsInZcmc = $totalZcmc + $currentServiceMonths;
            // $totalYearsInZcmc = floor($totalMonthsInZcmc  / 12);

        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeesServiceLength', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesEmploymentType($employment_type_id, Request $request)
    {
        try {

            $employees = EmployeeProfile::whereNotIn('id', [1])->whereNot('employee_id', NULL)->orWhere('employment_type_id', $employment_type_id)->get();


            $regular = $employees->filter(function ($row) {
                return $row->employment_type_id !== 4 && $row->employment_type_id !== 5;
            });
            $temporary =  $employees->filter(function ($row) {
                return $row->employment_type_id === 4;
            });
            $job_order = $employees->filter(function ($row) {
                return $row->employment_type_id === 5;
            });

            return response()->json([
                'data' =>  EmployeesDetailsReport::collection($employees),
                'count' => [
                    'regular' => COUNT($regular),
                    'permanent' => COUNT($temporary),
                    'job_order' => COUNT($job_order),
                ],
                'message' => 'List of employees retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeesServiceLength', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesByEmploymentType($employment_type_id, $area_id, $sector, Request $request)
    {
        try {

            $area = strip_tags($area_id);
            $sector = Str::lower(strip_tags($sector));
            $employees = [];

            $regular = [];
            $temporary = [];
            $job_order = [];

            if ($area_id == 'null') {
                $employees = EmployeeProfile::select('employee_profiles.*')
                    ->JOIN('assigned_areas as aa', 'aa.employee_profile_id', 'employee_profiles.id')
                    ->where('employee_profiles.employment_type_id', $employment_type_id)
                    ->whereNotIn('employee_profiles.id', [1])
                    ->where('employee_id', '!=', null)
                    ->get();

                $regular = $employees->filter(function ($row) {
                    return $row->employment_type_id !== 4 && $row->employment_type_id !== 5;
                });
                $temporary =  $employees->filter(function ($row) {
                    return $row->employment_type_id === 4;
                });
                $job_order = $employees->filter(function ($row) {
                    return $row->employment_type_id === 5;
                });
            } else if ($employment_type_id == 'null') {
                $employees = EmployeeProfile::select('employee_profiles.*')
                    ->JOIN('assigned_areas as aa', 'aa.employee_profile_id', 'employee_profiles.id')
                    ->where('aa.' . $sector . "_id", $area)
                    ->whereNotIn('employee_profiles.id', [1])
                    ->where('employee_id', '!=', null)
                    ->get();

                $regular = $employees->filter(function ($row) {
                    return $row->employment_type_id !== 4 && $row->employment_type_id !== 5;
                });
                $temporary =  $employees->filter(function ($row) {
                    return $row->employment_type_id === 4;
                });
                $job_order = $employees->filter(function ($row) {
                    return $row->employment_type_id === 5;
                });
            } else {
                $employees = EmployeeProfile::select('employee_profiles.*')
                    ->JOIN('assigned_areas as aa', 'aa.employee_profile_id', 'employee_profiles.id')
                    ->where('aa.' . $sector . "_id", $area)
                    ->where('employee_profiles.employment_type_id', $employment_type_id)
                    ->whereNotIn('employee_profiles.id', [1])
                    ->where('employee_id', '!=', null)
                    ->get();

                $regular = $employees->filter(function ($row) {
                    return $row->employment_type_id !== 4 && $row->employment_type_id !== 5;
                });
                $temporary =  $employees->filter(function ($row) {
                    return $row->employment_type_id === 4;
                });
                $job_order = $employees->filter(function ($row) {
                    return $row->employment_type_id === 5;
                });
            }

            return response()->json([
                'data' => EmployeesDetailsReport::collection($employees),
                'count' => [
                    'regular' => COUNT($regular),
                    'permanent' => COUNT($temporary),
                    'job_order' => COUNT($job_order),
                ],
                'message' => 'List of employees retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeesServiceLength', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesPerJobPosition($designation_id, Request $request)
    {
        try {


            if ($designation_id == 0) {
                $employees = AssignArea::with('employeeProfile')->get();
            } else {
                $employees = AssignArea::with('employeeProfile')->orWhere('designation_id', $designation_id)->get();
            }


            return response()->json([
                'data' => DesignationReportResource::collection($employees),
                'count' => COUNT($employees),
                'message' => 'List of employees job position retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeesPerJobPosition', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesPerJobPositionAndArea($designation_id, $area_id, $sector, Request $request)
    {
        try {

            $key = Str::lower(strip_tags($sector));

            $employees = AssignArea::with('employeeProfile')->where('designation_id', $designation_id)->where($key . "_id", $area_id)->get();

            return response()->json([
                'data' => DesignationReportResource::collection($employees),
                'count' => COUNT($employees),
                'message' => 'List of employees job position retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeesPerJobPosition', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
