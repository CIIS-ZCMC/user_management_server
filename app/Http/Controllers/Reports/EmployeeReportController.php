<?php

namespace App\Http\Controllers\Reports;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\DesignationReportResource;
use App\Http\Resources\EmployeesAddressReport;
use App\Http\Resources\EmployeesDetailsReport;
use App\Http\Resources\EmployeesDetailsReportByAddress;
use App\Http\Resources\EmployeesDetailsReportByReligion;
use App\Http\Resources\EmployeesDetailsReportBySex;
use App\Models\AssignArea;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\PersonalInformation;
use App\Models\Section;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Dompdf\Dompdf;
use Dompdf\Options;
use Psy\Util\Json;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class EmployeeReportController extends Controller
{
    private $CONTROLLER_NAME = 'Employee Reports';

    /**
     * Filter employees by their blood type.
     *
     * This function retrieves employees based on the provided sector, area, and blood type.
     * The retrieved employees are sorted by their first name and returned as a JSON response.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function filterEmployeesByBloodType(Request $request): JsonResponse
    {
        try {
            $employees = collect();
            $report_name = 'Employee Blood Type Report';
            $sector = $request->query('sector');
            $area_id = $request->query('area_id');
            $blood_type = $request->query('blood_type'); // Input with + sign %2B, - sign %2D | Example: B+ => params: B%2B
            $columns = json_decode($request->query('columns'), true) ?? [];
            $search = $request->query('search');
            $isPrint = (bool)$request->query('isPrint');

            // count number of columns
            $column_count = count($columns);
            // print page orientation
            $orientation = $column_count <= 3 ? 'portrait' : 'landscape';

            if ((!$sector && $area_id) || ($sector && !$area_id)) {
                return response()->json(['message' => 'Invalid sector or area id input'], 400);
            }

            if (!$sector && !$area_id) {
                $employees = AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                    $query->whereNull('deactivated_at');
                }])
                    ->where('employee_profile_id', '<>', 1)
                    ->when($blood_type, function ($query) use ($blood_type) {
                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                            if (!empty($blood_type)) {
                                $q->where('blood_type', $blood_type);
                            }
                        });
                    })
                    ->when($search, function ($query) use ($search) {
                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                            if (!empty($search)) {
                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                            }
                        });
                    })
                    ->get();
            } else {
                switch ($sector) {
                    case 'division':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($blood_type, function ($query) use ($blood_type) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                        if (!empty($blood_type)) {
                                            $q->where('blood_type', $blood_type);
                                        }
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $departments = Department::where('division_id', $area_id)->get();
                        foreach ($departments as $department) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('department_id', $department->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($blood_type, function ($query) use ($blood_type) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                            if (!empty($blood_type)) {
                                                $q->where('blood_type', $blood_type);
                                            }
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $sections = Section::where('department_id', $department->id)->get();
                            foreach ($sections as $section) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('section_id', $section->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($blood_type, function ($query) use ($blood_type) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                                if (!empty($blood_type)) {
                                                    $q->where('blood_type', $blood_type);
                                                }
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );

                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $employees = $employees->merge(
                                        AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                            $query->whereNull('deactivated_at');
                                        }])
                                            ->where('unit_id', $unit->id)
                                            ->where('employee_profile_id', '<>', 1)
                                            ->when($blood_type, function ($query) use ($blood_type) {
                                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                                    if (!empty($blood_type)) {
                                                        $q->where('blood_type', $blood_type);
                                                    }
                                                });
                                            })
                                            ->when($search, function ($query) use ($search) {
                                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                    if (!empty($search)) {
                                                        $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                            ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                    }
                                                });
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
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($blood_type, function ($query) use ($blood_type) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                            if (!empty($blood_type)) {
                                                $q->where('blood_type', $blood_type);
                                            }
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($blood_type, function ($query) use ($blood_type) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                                if (!empty($blood_type)) {
                                                    $q->where('blood_type', $blood_type);
                                                }
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'department':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('department_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($blood_type, function ($query) use ($blood_type) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                        if (!empty($blood_type)) {
                                            $q->where('blood_type', $blood_type);
                                        }
                                    });
                                })->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $sections = Section::where('department_id', $area_id)->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($blood_type, function ($query) use ($blood_type) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                            if (!empty($blood_type)) {
                                                $q->where('blood_type', $blood_type);
                                            }
                                        });
                                    })->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($blood_type, function ($query) use ($blood_type) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                                if (!empty($blood_type)) {
                                                    $q->where('blood_type', $blood_type);
                                                }
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'section':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('section_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($blood_type, function ($query) use ($blood_type) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                        if (!empty($blood_type)) {
                                            $q->where('blood_type', $blood_type);
                                        }
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $units = Unit::where('section_id', $area_id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('unit_id', $unit->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($blood_type, function ($query) use ($blood_type) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type) {
                                            if (!empty($blood_type)) {
                                                $q->where('blood_type', $blood_type);
                                            }
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );
                        }
                        break;

                    case 'unit':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('unit_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($blood_type, function ($query) use ($blood_type, $search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($blood_type, $search) {
                                        if (!empty($blood_type)) {
                                            $q->where('blood_type', $blood_type);
                                        }
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );
                        break;
                    default:
                        return response()->json(['message' => 'Invalid input. Please input a valid sector'], 400);
                }
            }

            // After all merge operations
            $employees = $employees->unique('employee_profile_id');
            // Sort employees by first name
            $employees = $employees->sortBy(function ($employee) {
                return $employee->employeeProfile->personalInformation->first_name;
            });

            // Paginate the results
            $current_page = LengthAwarePaginator::resolveCurrentPage();
            $paginated_employees = new LengthAwarePaginator(
                $employees->forPage($current_page, 10),
                $employees->count(),
                10,
                $current_page,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );

            // Transform and paginate employee data
            $data = EmployeesDetailsReport::collection($paginated_employees);
            $print_employees = EmployeesDetailsReport::collection($employees);

            if ($isPrint) {
                return Helpers::generatePdf($print_employees, $columns, $report_name, $orientation);
            }

            return response()->json([
                'pagination' => [
                    'current_page' => $paginated_employees->currentPage(),
                    'per_page' => $paginated_employees->perPage(),
                    'total' => $paginated_employees->total(),
                    'last_page' => $paginated_employees->lastPage(),
                    'has_more_pages' => $paginated_employees->hasMorePages(),
                ],
                'count' => $paginated_employees->count(),
                'data' => $data,
                'message' => 'List of employee blood types retrieved'
            ], ResponseAlias::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterEmployeeByBloodType', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Filter employees by their civil status.
     *
     * This function retrieves employees based on the provided sector, area, and civil status.
     * The retrieved employees are sorted by their first name and returned as a JSON response.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function filterEmployeesByCivilStatus(Request $request): JsonResponse
    {
        try {
            $employees = collect();
            $report_name = 'Employee Civil Status Report';
            $sector = $request->query('sector');
            $area_id = $request->query('area_id');
            $civil_status = $request->query('civil_status');
            $columns = json_decode($request->query('columns'), true) ?? [];
            $search = $request->query('search');
            $isPrint = (bool)$request->query('isPrint');

            // count number of columns
            $column_count = count($columns);
            // print page orientation
            $orientation = $column_count <= 3 ? 'portrait' : 'landscape';

            if ((!$sector && $area_id) || ($sector && !$area_id)) {
                return response()->json(['message' => 'Invalid sector or area id input'], 400);
            }

            if (!$sector && !$area_id) {
                $employees = AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                    $query->whereNull('deactivated_at');
                }])
                    ->where('division_id', $area_id)
                    ->where('employee_profile_id', '<>', 1)
                    ->when($civil_status, function ($query) use ($civil_status) {
                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                            $q->where('civil_status', $civil_status);
                        });
                    })
                    ->when($search, function ($query) use ($search) {
                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                            if (!empty($search)) {
                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                            }
                        });
                    })
                    ->get();
            } else {
                switch ($sector) {
                    case 'division':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($civil_status, function ($query) use ($civil_status) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                        $q->where('civil_status', $civil_status);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $departments = Department::where('division_id', $area_id)->get();
                        foreach ($departments as $department) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('department_id', $department->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($civil_status, function ($query) use ($civil_status) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                            $q->where('civil_status', $civil_status);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $sections = Section::where('department_id', $department->id)->get();
                            foreach ($sections as $section) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('section_id', $section->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($civil_status, function ($query) use ($civil_status) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                                $q->where('civil_status', $civil_status);
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );

                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $employees = $employees->merge(
                                        AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                            $query->whereNull('deactivated_at');
                                        }])
                                            ->where('unit_id', $unit->id)
                                            ->where('employee_profile_id', '<>', 1)
                                            ->when($civil_status, function ($query) use ($civil_status) {
                                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                                    $q->where('civil_status', $civil_status);
                                                });
                                            })
                                            ->when($search, function ($query) use ($search) {
                                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                    if (!empty($search)) {
                                                        $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                            ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                    }
                                                });
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
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($civil_status, function ($query) use ($civil_status) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                            $q->where('civil_status', $civil_status);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($civil_status, function ($query) use ($civil_status) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                                $q->where('civil_status', $civil_status);
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'department':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('department_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($civil_status, function ($query) use ($civil_status) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                        $q->where('civil_status', $civil_status);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $sections = Section::where('department_id', $area_id)->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($civil_status, function ($query) use ($civil_status) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                            $q->where('civil_status', $civil_status);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($civil_status, function ($query) use ($civil_status) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                                $q->where('civil_status', $civil_status);
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'section':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('section_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($civil_status, function ($query) use ($civil_status) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                        $q->where('civil_status', $civil_status);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $units = Unit::where('section_id', $area_id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('unit_id', $unit->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($civil_status, function ($query) use ($civil_status) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                            $q->where('civil_status', $civil_status);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );
                        }
                        break;

                    case 'unit':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('unit_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($civil_status, function ($query) use ($civil_status) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($civil_status) {
                                        $q->where('civil_status', $civil_status);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );
                        break;
                    default:
                        return response()->json(['message', 'Invalid input. Please input a valid sector'], 400);
                }
            }

            // After all merge operations
            $employees = $employees->unique('employee_profile_id');
            // Sort employees by first name
            $employees = $employees->sortBy(function ($employee) {
                return $employee->employeeProfile->personalInformation->first_name;
            });

            // Paginate the results
            $current_page = LengthAwarePaginator::resolveCurrentPage();
            $paginated_employees = new LengthAwarePaginator(
                $employees->forPage($current_page, 10),
                $employees->count(),
                10,
                $current_page,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );

            // Transform and paginate employee data
            $data = EmployeesDetailsReport::collection($paginated_employees);
            $print_employees = EmployeesDetailsReport::collection($employees);

            if ($isPrint) {
                return Helpers::generatePdf($print_employees, $columns, $report_name, $orientation);
            }

            return response()->json([
                'pagination' => [
                    'current_page' => $paginated_employees->currentPage(),
                    'per_page' => $paginated_employees->perPage(),
                    'total' => $paginated_employees->total(),
                    'last_page' => $paginated_employees->lastPage(),
                    'has_more_pages' => $paginated_employees->hasMorePages(),
                ],
                'count' => $paginated_employees->count(),
                'data' => $data,
                'message' => 'List of employee blood types retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterEmployyeByBloodType', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Filter employees by their job status.
     *
     * This function retrieves employees based on the provided sector, area, and employment type.
     * Employees are categorized into regular, permanent, and job order types, sorted by first name,
     * and returned as a JSON response.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function filterEmployeesByJobStatus(Request $request): JsonResponse
    {
        try {
            $employees = collect();
            $report_name = 'Employees Job Status Report';
            $sector = $request->query('sector');
            $area_id = $request->query('area_id');
            $employment_type_id = $request->query('employment_type_id');
            $columns = json_decode($request->query('columns'), true) ?? [];
            $search = $request->query('search');
            $isPrint = (bool)$request->query('isPrint');

            // count number of columns
            $column_count = count($columns);
            // print page orientation
            $orientation = $column_count <= 3 ? 'portrait' : 'landscape';

            if ((!$sector && $area_id) || ($sector && !$area_id)) {
                return response()->json(['message' => 'Invalid sector or area id input'], 400);
            }

            if (!$sector && !$area_id) {
                $employees = AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                    ->where('employee_profile_id', '<>', 1)
                    ->when($employment_type_id, function ($query) use ($employment_type_id) {
                        $query->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                            $q->where('employment_type_id', $employment_type_id);
                        });
                    })
                    ->when($search, function ($query) use ($search) {
                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                            if (!empty($search)) {
                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                            }
                        });
                    })
                    ->get();
            } else {
                switch ($sector) {
                    case 'division':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($employment_type_id, function ($query) use ($employment_type_id) {
                                    $query->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                        $q->where('employment_type_id', $employment_type_id);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $departments = Department::where('division_id', $area_id)->get();
                        foreach ($departments as $department) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                    ->where('department_id', $department->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($employment_type_id, function ($query) use ($employment_type_id) {
                                        $query->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                            $q->where('employment_type_id', $employment_type_id);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $sections = Section::where('department_id', $department->id)->get();
                            foreach ($sections as $section) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                        ->where('section_id', $section->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($employment_type_id, function ($query) use ($employment_type_id) {
                                            $query->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                                $q->where('employment_type_id', $employment_type_id);
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );

                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $employees = $employees->merge(
                                        AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                            ->where('unit_id', $unit->id)
                                            ->where('employee_profile_id', '<>', 1)
                                            ->when($employment_type_id, function ($query) use ($employment_type_id) {
                                                $query->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                                    $q->where('employment_type_id', $employment_type_id);
                                                });
                                            })
                                            ->when($search, function ($query) use ($search) {
                                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                    if (!empty($search)) {
                                                        $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                            ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                    }
                                                });
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
                                AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($employment_type_id, function ($query) use ($employment_type_id) {
                                        $query->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                            $q->where('employment_type_id', $employment_type_id);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($employment_type_id, function ($query) use ($employment_type_id) {
                                            $query->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                                $q->where('employment_type_id', $employment_type_id);
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'department':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                ->where('department_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($employment_type_id, function ($query) use ($employment_type_id) {
                                    $query->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                        $q->where('employment_type_id', $employment_type_id);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $sections = Section::where('department_id', $area_id)->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($employment_type_id, function ($query) use ($employment_type_id) {
                                        $query->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                            $q->where('employment_type_id', $employment_type_id);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($employment_type_id, function ($query) use ($employment_type_id) {
                                            $query->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                                $q->where('employment_type_id', $employment_type_id);
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'section':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                ->where('section_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($employment_type_id, function ($query) use ($employment_type_id) {
                                    $query->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                        $q->where('employment_type_id', $employment_type_id);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $units = Unit::where('section_id', $area_id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                    ->where('unit_id', $unit->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($employment_type_id, function ($query) use ($employment_type_id) {
                                        $query->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                            $q->where('employment_type_id', $employment_type_id);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );
                        }
                        break;

                    case 'unit':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                ->where('unit_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($employment_type_id, function ($query) use ($employment_type_id) {
                                    $query->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                        $q->where('employment_type_id', $employment_type_id);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );
                        break;
                    default:
                        $employees = AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                            ->where('employee_profile_id', '<>', 1)
                            ->when($employment_type_id, function ($query) use ($employment_type_id) {
                                $query->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                    $q->where('employment_type_id', $employment_type_id);
                                });
                            })
                            ->when($search, function ($query) use ($search) {
                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                    if (!empty($search)) {
                                        $q->where('first_name', 'LIKE', '%' . $search . '%')
                                            ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                    }
                                });
                            })
                            ->get();
                }
            }

            // Separate employees into categories based on their employment types
            $permanent = $employees->filter(function ($row) {
                return in_array($row->employeeProfile->employmentType->id, [1, 2]);
            });
            $permanent_cti = $employees->filter(function ($row) {
                return $row->employeeProfile->employmentType->id === 3;
            });
            $part_time = $employees->filter(function ($row) {
                return $row->employeeProfile->employmentType->id === 2;
            });
            $full_time = $employees->filter(function ($row) {
                return $row->employeeProfile->employmentType->id === 1;
            });
            $temporary = $employees->filter(function ($row) {
                return $row->employeeProfile->employmentType->id === 4;
            });
            $job_order = $employees->filter(function ($row) {
                return $row->employeeProfile->employmentType->id === 5;
            });

            // After all merge operations
            $employees = $employees->unique('employee_profile_id');

            // Sort employees by first name
            $employees = $employees->sortBy(function ($employee) {
                return $employee->employeeProfile->personalInformation->first_name;
            });

            // Paginate the results
            $current_page = LengthAwarePaginator::resolveCurrentPage();
            $paginated_employees = new LengthAwarePaginator(
                $employees->forPage($current_page, 10),
                $employees->count(),
                10,
                $current_page,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );

            // Transform and paginate employee data
            $data = EmployeesDetailsReport::collection($paginated_employees);
            $print_employees = EmployeesDetailsReport::collection($employees);

            if ($isPrint) {
                return Helpers::generatePdf($print_employees, $columns, $report_name, $orientation);
            }

            return response()->json([
                'pagination' => [
                    'current_page' => $paginated_employees->currentPage(),
                    'per_page' => $paginated_employees->perPage(),
                    'total' => $paginated_employees->total(),
                    'last_page' => $paginated_employees->lastPage(),
                    'has_more_pages' => $paginated_employees->hasMorePages(),
                ],
                'total_job_statuses' => [
                    'total_permanent' => COUNT($permanent),
                    'permanent_cti' => COUNT($permanent_cti),
                    'total_part_time' => COUNT($part_time),
                    'total_full_time' => COUNT($full_time),
                    'total_temporary' => COUNT($temporary),
                    'total_job_order' => COUNT($job_order)
                ],
                'count' => COUNT($paginated_employees),
                'data' => EmployeesDetailsReport::collection($data),
                'message' => 'List of employees retrieved'
            ], ResponseAlias::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterEmployyeByBloodType', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Filter employees and count them per designation.
     *
     * This function retrieves employees based on the provided sector, area, and counts them
     * per designation. Employees are sorted by first name and returned as a JSON response.
     *
     * @param Request $request
     * @return JsonResponse
     */

    public function filterEmployeesPerPosition(Request $request): JsonResponse
    {
        try {
            $employees = collect();
            $report_name = 'Employee Per Position Report';
            $sector = $request->query('sector');
            $area_id = $request->query('area_id');
            $columns = json_decode($request->query('columns'), true) ?? [];
            $search = $request->query('search');
            $isPrint = (bool)$request->query('isPrint');
            $perPage = $request->input('per_page', 10);  // Default to 10 items per page if not provided

            // count number of columns
            $column_count = count($columns);
            // print page orientation
            $orientation = $column_count <= 3 ? 'portrait' : 'landscape';

            if ((!$sector && $area_id) || ($sector && !$area_id)) {
                return response()->json(['message' => 'Invalid sector or area id input'], 400);
            }

            if (!$sector && !$area_id) {
                $employees = AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                    $query->whereNull('deactivated_at');
                }])
                    ->where('employee_profile_id', '<>', 1)
                    ->when($search, function ($query) use ($search) {
                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                            if (!empty($search)) {
                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                            }
                        });
                    })
                    ->get();
            } else {
                // Fetch employees based on sector and area_id
                switch ($sector) {
                    case 'division':
                        // Similar logic as before with search filter applied
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $departments = Department::where('division_id', $area_id)->get();
                        foreach ($departments as $department) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('department_id', $department->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $sections = Section::where('department_id', $department->id)->get();
                            foreach ($sections as $section) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('section_id', $section->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );

                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $employees = $employees->merge(
                                        AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                            $query->whereNull('deactivated_at');
                                        }])
                                            ->where('unit_id', $unit->id)
                                            ->where('employee_profile_id', '<>', 1)
                                            ->when($search, function ($query) use ($search) {
                                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                    if (!empty($search)) {
                                                        $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                            ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                    }
                                                });
                                            })
                                            ->get()
                                    );
                                }
                            }
                        }

                        $sections = Section::where('division_id', $area_id)->whereNull('department_id')->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'department':
                        // Similar logic as before with search filter applied
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('department_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $sections = Section::where('department_id', $area_id)->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'section':
                        // Similar logic as before with search filter applied
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('section_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $units = Unit::where('section_id', $area_id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('unit_id', $unit->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );
                        }
                        break;

                    case 'unit':
                        // Similar logic as before with search filter applied
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('unit_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );
                        break;
                    default:
                        return response()->json(['message' => 'Invalid input. Please input a valid sector'], 400);
                }
            }

            // Apply search filter if provided
            if ($search) {
                $employees = $employees->filter(function ($employee) use ($search) {
                    $employeeName = $employee->employeeProfile->personalInformation->fullName();
                    $designationName = $employee->employeeProfile->findDesignation()['name'];

                    // Check if search term matches either the name or designation
                    return stripos($employeeName, $search) !== false || stripos($designationName, $search) !== false;
                });
            }

            // Count employees per designation
            $designationCounts = [];
            $uniqueDesignations = [];

            foreach ($employees as $employee) {
                $designationName = $employee->employeeProfile->findDesignation()['name'];
                if (!isset($designationCounts[$designationName])) {
                    $designationCounts[$designationName] = 0;
                    $uniqueDesignations[$designationName] = $employee;
                }
                $designationCounts[$designationName]++;
            }

            foreach ($uniqueDesignations as $designationName => $employee) {
                $employee->employee_count = $designationCounts[$designationName];
            }

            $uniqueDesignationsCollection = collect($uniqueDesignations)->values();

            // Sort unique designations by employee_count (descending order)
            $uniqueDesignationsCollection = $uniqueDesignationsCollection->sortByDesc(function ($employee) {
                return $employee->employee_count;
            });

            // Paginate the results
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $paginatedDesignations = new LengthAwarePaginator(
                $uniqueDesignationsCollection->forPage($currentPage, $perPage),
                $uniqueDesignationsCollection->count(),
                $perPage,
                $currentPage,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );

            // Transform and paginate employee data
            $data = DesignationReportResource::collection($paginatedDesignations);
            $print_data = DesignationReportResource::collection($uniqueDesignations);

            if ($isPrint) {
                return Helpers::generatePdf($print_data, $columns, $report_name, $orientation);
            }

            return response()->json([
                'count' => [
                    'per_designation' => $designationCounts,
                ],
                'data' => DesignationReportResource::collection($paginatedDesignations),
                'pagination' => [
                    'current_page' => $paginatedDesignations->currentPage(),
                    'per_page' => $paginatedDesignations->perPage(),
                    'total' => $paginatedDesignations->total(),
                    'last_page' => $paginatedDesignations->lastPage(),
                    'has_more_pages' => $paginatedDesignations->hasMorePages(),
                ],
                'message' => 'List of employees retrieved'
            ], ResponseAlias::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterEmployeesPerPosition', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function filterEmployeesByAddress(Request $request): JsonResponse
    {
        try {
            $employees = collect();
            $report_name = 'Employees Address Report';
            $sector = $request->query('sector');
            $area_id = $request->query('area_id');
            $barangay = $request->query('barangay');
            $columns = json_decode($request->query('columns'), true) ?? [];
            $search = $request->query('search');
            $isPrint = (bool)$request->query('isPrint');

            // count number of columns
            $column_count = count($columns);
            // print page orientation
            $orientation = $column_count <= 3 ? 'portrait' : 'landscape';

            if ((!$sector && $area_id) || ($sector && !$area_id)) {
                return response()->json(['message' => 'Invalid sector or area id input'], 400);
            }

            if (!$sector && !$area_id) {
                $employees = AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                    $query->whereNull('deactivated_at');
                }])
                    ->where('employee_profile_id', '<>', 1)
                    ->when($barangay, function ($query) use ($barangay) {
                        $query->whereHas('employeeProfile.personalInformation.addresses', function ($q) use ($barangay) {
                            $q->where('address', 'LIKE', '%' . $barangay . '%');
                        });
                    })
                    ->when($search, function ($query) use ($search) {
                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                            if (!empty($search)) {
                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                            }
                        });
                    })
                    ->get();
            } else {
                switch ($sector) {
                    case 'division':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.personalInformation.addresses'])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($barangay, function ($query) use ($barangay) {
                                    $query->whereHas('employeeProfile.personalInformation.addresses', function ($q) use ($barangay) {
                                        $q->where('address', 'LIKE', '%' . $barangay . '%');
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $departments = Department::where('division_id', $area_id)->get();
                        foreach ($departments as $department) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.personalInformation.addresses'])
                                    ->where('department_id', $department->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($barangay, function ($query) use ($barangay) {
                                        $query->whereHas('employeeProfile.personalInformation.addresses', function ($q) use ($barangay) {
                                            $q->where('address', 'LIKE', '%' . $barangay . '%');
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $sections = Section::where('department_id', $department->id)->get();
                            foreach ($sections as $section) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.personalInformation.addresses'])
                                        ->where('section_id', $section->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($barangay, function ($query) use ($barangay) {
                                            $query->whereHas('employeeProfile.personalInformation.addresses', function ($q) use ($barangay) {
                                                $q->where('address', 'LIKE', '%' . $barangay . '%');
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );

                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $employees = $employees->merge(
                                        AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.personalInformation.addresses'])
                                            ->where('unit_id', $unit->id)
                                            ->where('employee_profile_id', '<>', 1)
                                            ->when($barangay, function ($query) use ($barangay) {
                                                $query->whereHas('employeeProfile.personalInformation.addresses', function ($q) use ($barangay) {
                                                    $q->where('address', 'LIKE', '%' . $barangay . '%');
                                                });
                                            })
                                            ->when($search, function ($query) use ($search) {
                                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                    if (!empty($search)) {
                                                        $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                            ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                    }
                                                });
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
                                AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.personalInformation.addresses'])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($barangay, function ($query) use ($barangay) {
                                        $query->whereHas('employeeProfile.personalInformation.addresses', function ($q) use ($barangay) {
                                            $q->where('address', 'LIKE', '%' . $barangay . '%');
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.personalInformation.addresses'])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($barangay, function ($query) use ($barangay) {
                                            $query->whereHas('employeeProfile.personalInformation.addresses', function ($q) use ($barangay) {
                                                $q->where('address', 'LIKE', '%' . $barangay . '%');
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'department':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.personalInformation.addresses'])
                                ->where('department_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($barangay, function ($query) use ($barangay) {
                                    $query->whereHas('employeeProfile.personalInformation.addresses', function ($q) use ($barangay) {
                                        $q->where('address', 'LIKE', '%' . $barangay . '%');
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $sections = Section::where('department_id', $area_id)->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.personalInformation.addresses'])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($barangay, function ($query) use ($barangay) {
                                        $query->whereHas('employeeProfile.personalInformation.addresses', function ($q) use ($barangay) {
                                            $q->where('address', 'LIKE', '%' . $barangay . '%');
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.personalInformation.addresses'])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($barangay, function ($query) use ($barangay) {
                                            $query->whereHas('employeeProfile.personalInformation.addresses', function ($q) use ($barangay) {
                                                $q->where('address', 'LIKE', '%' . $barangay . '%');
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'section':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.personalInformation.addresses'])
                                ->where('section_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($barangay, function ($query) use ($barangay) {
                                    $query->whereHas('employeeProfile.personalInformation.addresses', function ($q) use ($barangay) {
                                        $q->where('address', 'LIKE', '%' . $barangay . '%');
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $units = Unit::where('section_id', $area_id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.personalInformation.addresses'])
                                    ->where('unit_id', $unit->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($barangay, function ($query) use ($barangay) {
                                        $query->whereHas('employeeProfile.personalInformation.addresses', function ($q) use ($barangay) {
                                            $q->where('address', 'LIKE', '%' . $barangay . '%');
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );
                        }
                        break;

                    case 'unit':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.personalInformation.addresses'])
                                ->where('unit_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($barangay, function ($query) use ($barangay) {
                                    $query->whereHas('employeeProfile.personalInformation.addresses', function ($q) use ($barangay) {
                                        $q->where('address', 'LIKE', '%' . $barangay . '%');
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );
                        break;
                    default:
                        $employees = AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.personalInformation.addresses'])
                            ->where('employee_profile_id', '<>', 1)
                            ->when($barangay, function ($query) use ($barangay) {
                                $query->whereHas('employeeProfile.personalInformation.addresses', function ($q) use ($barangay) {
                                    $q->where('address', 'LIKE', '%' . $barangay . '%');
                                });
                            })
                            ->when($search, function ($query) use ($search) {
                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                    if (!empty($search)) {
                                        $q->where('first_name', 'LIKE', '%' . $search . '%')
                                            ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                    }
                                });
                            })
                            ->get();
                }
            }

            // After all merge operations
            $employees = $employees->unique('employee_profile_id');
            // Sort employees by first name
            $employees = $employees->sortBy(function ($employee) {
                return $employee->employeeProfile->personalInformation->first_name;
            });

            // Paginate the results
            $current_page = LengthAwarePaginator::resolveCurrentPage();
            $paginated_employees = new LengthAwarePaginator(
                $employees->forPage($current_page, 10),
                $employees->count(),
                10,
                $current_page,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );

            // Transform and paginate employee data
            $data = EmployeesDetailsReportByAddress::collection($paginated_employees);
            $print_employees = EmployeesDetailsReportByAddress::collection($employees);

            if ($isPrint) {
                return Helpers::generatePdf($print_employees, $columns, $report_name, $orientation);
            }

            return response()->json([
                'pagination' => [
                    'current_page' => $paginated_employees->currentPage(),
                    'per_page' => $paginated_employees->perPage(),
                    'total' => $paginated_employees->total(),
                    'last_page' => $paginated_employees->lastPage(),
                    'has_more_pages' => $paginated_employees->hasMorePages(),
                ],
                'count' => $paginated_employees->count(),
                'data' => $data,
                'message' => 'List of employees retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterEmployyeByBloodType', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function filterEmployeesBySex(Request $request): JsonResponse
    {
        try {
            $employees = collect();
            $report_name = 'Employee Sex Report';
            $sector = $request->query('sector');
            $area_id = $request->query('area_id');
            $sex = $request->query('sex');
            $columns = json_decode($request->query('columns'), true) ?? [];
            $search = $request->query('search');
            $isPrint = (bool)$request->query('isPrint');

            $male_count_by_area = 0;
            $female_count_by_area = 0;

            // count number of columns
            $column_count = count($columns);
            // print page orientation
            $orientation = $column_count <= 3 ? 'portrait' : 'landscape';

            if ((!$sector && $area_id) || ($sector && !$area_id)) {
                return response()->json(['message' => 'Invalid sector or area id input'], 400);
            }

            if (!$sector && !$area_id) {
                $employees = AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                    $query->whereNull('deactivated_at');
                }])
                    ->where('employee_profile_id', '<>', 1)
                    ->when($sex, function ($query) use ($sex) {
                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($sex) {
                            $q->where('sex', $sex);
                        });
                    })
                    ->when($search, function ($query) use ($search) {
                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                            if (!empty($search)) {
                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                            }
                        });
                    })
                    ->get();
            } else {
                switch ($sector) {
                    case 'division':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($sex, function ($query) use ($sex) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($sex) {
                                        $q->where('sex', $sex);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        // Count male and female by section
                        $male_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                            $query->whereNull('deactivated_at');
                        }])
                            ->where('division_id', $area_id)
                            ->where('employee_profile_id', '<>', 1)
                            ->whereHas('employeeProfile.personalInformation', function ($query) {
                                $query->where('sex', 'Male');
                            })->count();

                        $female_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                            $query->whereNull('deactivated_at');
                        }])
                            ->where('division_id', $area_id)
                            ->where('employee_profile_id', '<>', 1)
                            ->whereHas('employeeProfile.personalInformation', function ($query) {
                                $query->where('sex', 'Female');
                            })->count();


                        $departments = Department::where('division_id', $area_id)->get();
                        foreach ($departments as $department) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('department_id', $department->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($sex, function ($query) use ($sex) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($sex) {
                                            $q->where('sex', $sex);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            // Count male and female by section
                            $male_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile.personalInformation', function ($query) {
                                    $query->where('sex', 'Male');
                                })->count();

                            $female_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile.personalInformation', function ($query) {
                                    $query->where('sex', 'Female');
                                })->count();


                            $sections = Section::where('department_id', $department->id)->get();
                            foreach ($sections as $section) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('section_id', $section->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($sex, function ($query) use ($sex) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($sex) {
                                                $q->where('sex', $sex);
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );

                                // Count male and female by section
                                $male_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('division_id', $area_id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile.personalInformation', function ($query) {
                                        $query->where('sex', 'Male');
                                    })->count();

                                $female_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('division_id', $area_id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile.personalInformation', function ($query) {
                                        $query->where('sex', 'Female');
                                    })->count();


                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $employees = $employees->merge(
                                        AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                            $query->whereNull('deactivated_at');
                                        }])
                                            ->where('unit_id', $unit->id)
                                            ->where('employee_profile_id', '<>', 1)
                                            ->when($sex, function ($query) use ($sex) {
                                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($sex) {
                                                    $q->where('sex', $sex);
                                                });
                                            })
                                            ->when($search, function ($query) use ($search) {
                                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                    if (!empty($search)) {
                                                        $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                            ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                    }
                                                });
                                            })
                                            ->get()
                                    );

                                    // Count male and female by section
                                    $male_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('division_id', $area_id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->whereHas('employeeProfile.personalInformation', function ($query) {
                                            $query->where('sex', 'Male');
                                        })->count();

                                    $female_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('division_id', $area_id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->whereHas('employeeProfile.personalInformation', function ($query) {
                                            $query->where('sex', 'Female');
                                        })->count();

                                }
                            }
                        }


                        // Get sections directly under the division (if any) that are not under any department
                        $sections = Section::where('division_id', $area_id)->whereNull('department_id')->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($sex, function ($query) use ($sex) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($sex) {
                                            $q->where('sex', $sex);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            // Count male and female by section
                            $male_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile.personalInformation', function ($query) {
                                    $query->where('sex', 'Male');
                                })->count();

                            $female_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile.personalInformation', function ($query) {
                                    $query->where('sex', 'Female');
                                })->count();


                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($sex, function ($query) use ($sex) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($sex) {
                                                $q->where('sex', $sex);
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );

                                // Count male and female by section
                                $male_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('division_id', $area_id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile.personalInformation', function ($query) {
                                        $query->where('sex', 'Male');
                                    })->count();

                                $female_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('division_id', $area_id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile.personalInformation', function ($query) {
                                        $query->where('sex', 'Female');
                                    })->count();

                            }
                        }

                        break;

                    case 'department':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('department_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($sex, function ($query) use ($sex) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($sex) {
                                        $q->where('sex', $sex);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        // Count male and female by section
                        $male_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                            $query->whereNull('deactivated_at');
                        }])
                            ->where('division_id', $area_id)
                            ->where('employee_profile_id', '<>', 1)
                            ->whereHas('employeeProfile.personalInformation', function ($query) {
                                $query->where('sex', 'Male');
                            })->count();

                        $female_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                            $query->whereNull('deactivated_at');
                        }])
                            ->where('division_id', $area_id)
                            ->where('employee_profile_id', '<>', 1)
                            ->whereHas('employeeProfile.personalInformation', function ($query) {
                                $query->where('sex', 'Female');
                            })->count();


                        $sections = Section::where('department_id', $area_id)->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($sex, function ($query) use ($sex) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($sex) {
                                            $q->where('sex', $sex);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            // Count male and female by section
                            $male_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile.personalInformation', function ($query) {
                                    $query->where('sex', 'Male');
                                })->count();

                            $female_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile.personalInformation', function ($query) {
                                    $query->where('sex', 'Female');
                                })->count();


                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($sex, function ($query) use ($sex) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($sex) {
                                                $q->where('sex', $sex);
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );

                                // Count male and female by section
                                $male_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('division_id', $area_id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile.personalInformation', function ($query) {
                                        $query->where('sex', 'Male');
                                    })->count();

                                $female_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('division_id', $area_id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile.personalInformation', function ($query) {
                                        $query->where('sex', 'Female');
                                    })->count();

                            }
                        }
                        break;

                    case 'section':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('section_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($sex, function ($query) use ($sex) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($sex) {
                                        $q->where('sex', $sex);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        // Count male and female by section
                        $male_count_by_area += AssignArea::where('section_id', $area_id)
                            ->where('employee_profile_id', '<>', 1)
                            ->whereHas('employeeProfile.personalInformation', function ($query) {
                                $query->where('sex', 'Male');
                            })->count();

                        $female_count_by_area += AssignArea::where('section_id', $area_id)
                            ->where('employee_profile_id', '<>', 1)
                            ->whereHas('employeeProfile.personalInformation', function ($query) {
                                $query->where('sex', 'Female');
                            })->count();

                        $units = Unit::where('section_id', $area_id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('unit_id', $unit->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($sex, function ($query) use ($sex) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($sex) {
                                            $q->where('sex', $sex);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            // Count male and female by section
                            $male_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile.personalInformation', function ($query) {
                                    $query->where('sex', 'Male');
                                })->count();

                            $female_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile.personalInformation', function ($query) {
                                    $query->where('sex', 'Female');
                                })->count();

                        }
                        break;

                    case 'unit':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('unit_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($sex, function ($query) use ($sex) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($sex) {
                                        $q->where('sex', $sex);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        // Count male and female by section
                        $male_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                            $query->whereNull('deactivated_at');
                        }])
                            ->where('division_id', $area_id)
                            ->where('employee_profile_id', '<>', 1)
                            ->whereHas('employeeProfile.personalInformation', function ($query) {
                                $query->where('sex', 'Male');
                            })->count();

                        $female_count_by_area += AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                            $query->whereNull('deactivated_at');
                        }])
                            ->where('division_id', $area_id)
                            ->where('employee_profile_id', '<>', 1)
                            ->whereHas('employeeProfile.personalInformation', function ($query) {
                                $query->where('sex', 'Female');
                            })->count();

                        break;

                    default:
                        return response()->json(['message', 'Invalid input. Please input a valid sector'], 400);
                }
            }

            // After all merge operations
            $employees = $employees->unique('employee_profile_id');
            // Sort employees by first name
            $employees = $employees->sortBy(function ($employee) {
                return $employee->employeeProfile->personalInformation->first_name;
            });

            $male_count = AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                $query->whereNull('deactivated_at');
            }])
                ->where('employee_profile_id', '<>', 1)
                ->whereHas('employeeProfile.personalInformation', function ($query) {
                    $query->where('sex', 'Male');
                })->count();

            $female_count = AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                $query->whereNull('deactivated_at');
            }])
                ->where('employee_profile_id', '<>', 1)
                ->whereHas('employeeProfile.personalInformation', function ($query) {
                    $query->where('sex', 'Female');
                })->count();

            $total_count = $male_count + $female_count;
            $male_percentage = floatval($total_count > 0 ? number_format(($male_count / $total_count) * 100, 2) : 0);
            $female_percentage = floatval($total_count > 0 ? number_format(($female_count / $total_count) * 100, 2) : 0);

            // Paginate the results
            $current_page = LengthAwarePaginator::resolveCurrentPage();
            $paginated_employees = new LengthAwarePaginator(
                $employees->forPage($current_page, 10),
                $employees->count(),
                10,
                $current_page,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );

            // Transform and paginate employee data
            $data = EmployeesDetailsReportBySex::collection($paginated_employees);
            $print_employees = EmployeesDetailsReportBySex::collection($employees);

            if ($isPrint) {
                return Helpers::generatePdf($print_employees, $columns, $report_name, $orientation);
            }

            return response()->json([
                'pagination' => [
                    'current_page' => $paginated_employees->currentPage(),
                    'per_page' => $paginated_employees->perPage(),
                    'total' => $paginated_employees->total(),
                    'last_page' => $paginated_employees->lastPage(),
                    'has_more_pages' => $paginated_employees->hasMorePages(),
                ],
                'count' => COUNT($paginated_employees),
                'female_count_by_area' => $female_count_by_area,
                'male_count_by_area' => $male_count_by_area,
                'female_count' => $female_count,
                'male_count' => $male_count,
                'female_percentage' => $female_percentage,
                'male_percentage' => $male_percentage,
                'data' => EmployeesDetailsReportBySex::collection($data),
                'message' => 'List of employees retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterEmployyeByBloodType', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function filterEmployeesByPWD(Request $request): JsonResponse
    {
        try {
            $employees = collect();
            $report_name = 'Employee By PWD Report';
            $sector = $request->query('sector');
            $area_id = $request->query('area_id');
            $columns = json_decode($request->query('columns'), true) ?? [];
            $search = $request->query('search');
            $isPrint = (bool)$request->query('isPrint');
            $perPage = $request->input('per_page', 10);  // Default to 10 items per page if not provided
            $pwd = 13;

            // count number of columns
            $column_count = count($columns);
            // print page orientation
            $orientation = $column_count <= 3 ? 'portrait' : 'landscape';

            if ((!$sector && $area_id) || ($sector && !$area_id)) {
                return response()->json(['message' => 'Invalid sector or area id input'], 400);
            }

            if (!$sector && !$area_id) {
                $employees = AssignArea::with(
                    [
                        'employeeProfile' => function ($query) {
                            $query->whereNull('deactivated_at');
                        },
                        'employeeProfile.personalInformation',
                        'employeeProfile.personalInformation.legalInformation'
                    ])
                    ->where('employee_profile_id', '<>', 1)
                    ->when($pwd, function ($query) use ($pwd) {
                        $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($pwd) {
                            $q->where('legal_iq_id', $pwd)->where('answer', 1);
                        });
                    })
                    ->get();
            } else {
                switch ($sector) {
                    case 'division':
                        $employees = $employees->merge(
                                AssignArea::with(
                                    [
                                        'employeeProfile' => function ($query) {
                                            $query->whereNull('deactivated_at');
                                        },
                                        'employeeProfile.personalInformation',
                                        'employeeProfile.personalInformation.legalInformation'
                                    ]
                                )
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($pwd, function ($query) use ($pwd) {
                                    $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($pwd) {
                                        $q->where('legal_iq_id', $pwd)->where('answer', 1);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $departments = Department::where('division_id', $area_id)->get();
                        foreach ($departments as $department) {
                            $employees = $employees->merge(
                                AssignArea::with(
                                    [
                                        'employeeProfile' => function ($query) {
                                            $query->whereNull('deactivated_at');
                                        },
                                        'employeeProfile.personalInformation',
                                        'employeeProfile.personalInformation.legalInformation'
                                    ]
                                )
                                    ->where('department_id', $department->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($pwd, function ($query) use ($pwd) {
                                        $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($pwd) {
                                            $q->where('legal_iq_id', $pwd)->where('answer', 1);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $sections = Section::where('department_id', $department->id)->get();
                            foreach ($sections as $section) {
                                $employees = $employees->merge(
                                    AssignArea::with(
                                        [
                                            'employeeProfile' => function ($query) {
                                                $query->whereNull('deactivated_at');
                                            },
                                            'employeeProfile.personalInformation',
                                            'employeeProfile.personalInformation.legalInformation'
                                        ]
                                    )
                                        ->where('section_id', $section->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($pwd, function ($query) use ($pwd) {
                                            $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($pwd) {
                                                $q->where('legal_iq_id', $pwd)->where('answer', 1);
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );

                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $employees = $employees->merge(
                                        AssignArea::with(
                                            [
                                                'employeeProfile' => function ($query) {
                                                    $query->whereNull('deactivated_at');
                                                },
                                                'employeeProfile.personalInformation',
                                                'employeeProfile.personalInformation.legalInformation'
                                            ]
                                        )
                                            ->where('unit_id', $unit->id)
                                            ->where('employee_profile_id', '<>', 1)
                                            ->when($pwd, function ($query) use ($pwd) {
                                                $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($pwd) {
                                                    $q->where('legal_iq_id', $pwd)->where('answer', 1);
                                                });
                                            })
                                            ->when($search, function ($query) use ($search) {
                                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                    if (!empty($search)) {
                                                        $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                            ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                    }
                                                });
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
                                AssignArea::with(
                                    [
                                        'employeeProfile' => function ($query) {
                                            $query->whereNull('deactivated_at');
                                        },
                                        'employeeProfile.personalInformation',
                                        'employeeProfile.personalInformation.legalInformation'
                                    ]
                                )
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($pwd, function ($query) use ($pwd) {
                                        $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($pwd) {
                                            $q->where('legal_iq_id', $pwd)->where('answer', 1);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(
                                        [
                                            'employeeProfile' => function ($query) {
                                                $query->whereNull('deactivated_at');
                                            },
                                            'employeeProfile.personalInformation',
                                            'employeeProfile.personalInformation.legalInformation'
                                        ]
                                    )
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($pwd, function ($query) use ($pwd) {
                                            $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($pwd) {
                                                $q->where('legal_iq_id', $pwd)->where('answer', 1);
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'department':
                        $employees = $employees->merge(
                            AssignArea::with(
                                [
                                    'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    },
                                    'employeeProfile.personalInformation',
                                    'employeeProfile.personalInformation.legalInformation'
                                ]
                            )
                                ->where('department_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($pwd, function ($query) use ($pwd) {
                                    $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($pwd) {
                                        $q->where('legal_iq_id', $pwd)->where('answer', 1);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $sections = Section::where('department_id', $area_id)->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(
                                    [
                                        'employeeProfile' => function ($query) {
                                            $query->whereNull('deactivated_at');
                                        },
                                        'employeeProfile.personalInformation',
                                        'employeeProfile.personalInformation.legalInformation'
                                    ]
                                )
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($pwd, function ($query) use ($pwd) {
                                        $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($pwd) {
                                            $q->where('legal_iq_id', $pwd)->where('answer', 1);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(
                                        [
                                            'employeeProfile' => function ($query) {
                                                $query->whereNull('deactivated_at');
                                            },
                                            'employeeProfile.personalInformation',
                                            'employeeProfile.personalInformation.legalInformation'
                                        ]
                                    )
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($pwd, function ($query) use ($pwd) {
                                            $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($pwd) {
                                                $q->where('legal_iq_id', $pwd)->where('answer', 1);
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'section':
                        $employees = $employees->merge(
                            AssignArea::with(
                                [
                                    'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    },
                                    'employeeProfile.personalInformation',
                                    'employeeProfile.personalInformation.legalInformation'
                                ]
                            )
                                ->where('section_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($pwd, function ($query) use ($pwd) {
                                    $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($pwd) {
                                        $q->where('legal_iq_id', $pwd)->where('answer', 1);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $units = Unit::where('section_id', $area_id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(
                                    [
                                        'employeeProfile' => function ($query) {
                                            $query->whereNull('deactivated_at');
                                        },
                                        'employeeProfile.personalInformation',
                                        'employeeProfile.personalInformation.legalInformation'
                                    ]
                                )
                                    ->where('unit_id', $unit->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($pwd, function ($query) use ($pwd) {
                                        $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($pwd) {
                                            $q->where('legal_iq_id', $pwd)->where('answer', 1);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );
                        }
                        break;

                    case 'unit':
                        $employees = $employees->merge(
                            AssignArea::with(
                                [
                                    'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    },
                                    'employeeProfile.personalInformation',
                                    'employeeProfile.personalInformation.legalInformation'
                                ]
                            )
                                ->where('unit_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($pwd, function ($query) use ($pwd) {
                                    $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($pwd) {
                                        $q->where('legal_iq_id', $pwd)->where('answer', 1);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );
                        break;
                    default:
                        return response()->json(['message', 'Invalid input. Please input a valid sector'], 400);
                }
            }

            // After all merge operations
            $employees = $employees->unique('employee_profile_id');
            // Sort employees by first name
            $employees = $employees->sortBy(function ($employee) {
                return $employee->employeeProfile->personalInformation->first_name;
            });

            // Paginate the results
            $current_page = LengthAwarePaginator::resolveCurrentPage();
            $paginated_employees = new LengthAwarePaginator(
                $employees->forPage($current_page, 10),
                $employees->count(),
                10,
                $current_page,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );

            // Transform and paginate employee data
            $data = EmployeesDetailsReport::collection($paginated_employees);
            $print_employees = EmployeesDetailsReport::collection($employees);

            if ($isPrint) {
                return Helpers::generatePdf($print_employees, $columns, $report_name, $orientation);
            }

            return response()->json([
                'pagination' => [
                    'current_page' => $paginated_employees->currentPage(),
                    'per_page' => $paginated_employees->perPage(),
                    'total' => $paginated_employees->total(),
                    'last_page' => $paginated_employees->lastPage(),
                    'has_more_pages' => $paginated_employees->hasMorePages(),
                ],
                'count' => COUNT($paginated_employees),
                'data' => $data,
                'message' => 'List of employees retrieved'
            ], ResponseAlias::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterEmployeeByPWD', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function filterEmployeesBySoloParent(Request $request)
    {
        try {
            $employees = collect();
            $sector = $request->sector;
            $area_id = $request->area_id;;
            $search = $request->search;
            $page = $request->page ?: 1;
            $solo_parent = 14;

            if ((!$sector && $area_id) || ($sector && !$area_id)) {
                return response()->json(['message' => 'Invalid sector or area id input'], 400);
            }

            if (!$sector && !$area_id) {
                $employees = AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.personalInformation.legalInformation'])
                    ->where('employee_profile_id', '<>', 1)
                    ->when($solo_parent, function ($query) use ($solo_parent) {
                        $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($solo_parent) {
                            $q->where('legal_iq_id', $solo_parent)->where('answer', 1);
                        });
                    })
                    ->get();
            } else {
                switch ($sector) {
                    case 'division':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($solo_parent, function ($query) use ($solo_parent) {
                                    $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($solo_parent) {
                                        $q->where('legal_iq_id', $solo_parent)->where('answer', 1);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $departments = Department::where('division_id', $area_id)->get();
                        foreach ($departments as $department) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                    ->where('department_id', $department->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($solo_parent, function ($query) use ($solo_parent) {
                                        $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($solo_parent) {
                                            $q->where('legal_iq_id', $solo_parent)->where('answer', 1);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $sections = Section::where('department_id', $department->id)->get();
                            foreach ($sections as $section) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                        ->where('section_id', $section->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($solo_parent, function ($query) use ($solo_parent) {
                                            $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($solo_parent) {
                                                $q->where('legal_iq_id', $solo_parent)->where('answer', 1);
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );

                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $employees = $employees->merge(
                                        AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                            ->where('unit_id', $unit->id)
                                            ->where('employee_profile_id', '<>', 1)
                                            ->when($solo_parent, function ($query) use ($solo_parent) {
                                                $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($solo_parent) {
                                                    $q->where('legal_iq_id', $solo_parent)->where('answer', 1);
                                                });
                                            })
                                            ->when($search, function ($query) use ($search) {
                                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                    if (!empty($search)) {
                                                        $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                            ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                    }
                                                });
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
                                AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($solo_parent, function ($query) use ($solo_parent) {
                                        $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($solo_parent) {
                                            $q->where('legal_iq_id', $solo_parent)->where('answer', 1);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($solo_parent, function ($query) use ($solo_parent) {
                                            $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($solo_parent) {
                                                $q->where('legal_iq_id', $solo_parent)->where('answer', 1);
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'department':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                ->where('department_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($solo_parent, function ($query) use ($solo_parent) {
                                    $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($solo_parent) {
                                        $q->where('legal_iq_id', $solo_parent)->where('answer', 1);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $sections = Section::where('department_id', $area_id)->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($solo_parent, function ($query) use ($solo_parent) {
                                        $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($solo_parent) {
                                            $q->where('legal_iq_id', $solo_parent)->where('answer', 1);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($solo_parent, function ($query) use ($solo_parent) {
                                            $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($solo_parent) {
                                                $q->where('legal_iq_id', $solo_parent)->where('answer', 1);
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'section':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                ->where('section_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($solo_parent, function ($query) use ($solo_parent) {
                                    $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($solo_parent) {
                                        $q->where('legal_iq_id', $solo_parent)->where('answer', 1);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $units = Unit::where('section_id', $area_id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation'])
                                    ->where('unit_id', $unit->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($solo_parent, function ($query) use ($solo_parent) {
                                        $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($solo_parent) {
                                            $q->where('legal_iq_id', $solo_parent)->where('answer', 1);
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );
                        }
                        break;

                    case 'unit':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile', 'employeeProfile.personalInformation', 'employeeProfile.personalInformation.legalInformation'])
                                ->where('unit_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($solo_parent, function ($query) use ($solo_parent) {
                                    $query->whereHas('employeeProfile.personalInformation.legalInformation', function ($q) use ($solo_parent) {
                                        $q->where('legal_iq_id', $solo_parent)->where('answer', 1);
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );
                        break;
                    default:
                        return response()->json(['message', 'Invalid input. Please input a valid sector'], 400);
                }
            }

            // After all merge operations
            $employees = $employees->unique('employee_profile_id');
            // Sort employees by first name
            $employees = $employees->sortBy(function ($employee) {
                return $employee->employeeProfile->personalInformation->first_name;
            });

            // Paginate the results
            $current_page = LengthAwarePaginator::resolveCurrentPage();
            $paginated_employees = new LengthAwarePaginator(
                $employees->forPage($current_page, 10),
                $employees->count(),
                10,
                $current_page,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );

            // TRansform and paginate employee data
            $data = $paginated_employees;

            return response()->json([
                'pagination' => [
                    'current_page' => $paginated_employees->currentPage(),
                    'per_page' => $paginated_employees->perPage(),
                    'total' => $paginated_employees->total(),
                    'last_page' => $paginated_employees->lastPage(),
                    'has_more_pages' => $paginated_employees->hasMorePages(),
                ],
                'count' => COUNT($paginated_employees),
                'data' => EmployeesDetailsReport::collection($data),
                'message' => 'List of employees retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterEmployyeByBloodType', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function filterEmployeesByReligion(Request $request): JsonResponse
    {
        try {
            $employees = collect();
            $report_name = 'Employee by Religion Report';
            $sector = $request->query('sector');
            $area_id = $request->query('area_id');
            $religion = $request->query('religion');
            $columns = json_decode($request->query('columns'), true) ?? [];
            $search = $request->query('search');
            $isPrint = (bool)$request->query('isPrint');

            // count number of columns
            $column_count = count($columns);
            // print page orientation
            $orientation = $column_count <= 3 ? 'portrait' : 'landscape';

            if ((!$sector && $area_id) || ($sector && !$area_id)) {
                return response()->json(['message' => 'Invalid sector or area id input'], 400);
            }

            if (!$sector && !$area_id) {
                $employees = AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                    $query->whereNull('deactivated_at');
                }])
                    ->where('employee_profile_id', '<>', 1)
                    ->when($religion, function ($query) use ($religion) {
                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($religion) {
                            $q->where('religion', 'like', "%{$religion}%");
                        });
                    })
                    ->get();
            } else {
                switch ($sector) {
                    case 'division':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($religion, function ($query) use ($religion) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($religion) {
                                        $q->where('religion', 'like', "%{$religion}%");
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $departments = Department::where('division_id', $area_id)->get();
                        foreach ($departments as $department) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('division_id', $department->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($religion, function ($query) use ($religion) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($religion) {
                                            $q->where('religion', 'like', "%{$religion}%");
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $sections = Section::where('department_id', $department->id)->get();
                            foreach ($sections as $section) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('division_id', $section->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($religion, function ($query) use ($religion) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($religion) {
                                                $q->where('religion', 'like', "%{$religion}%");
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );

                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $employees = $employees->merge(
                                        AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                            $query->whereNull('deactivated_at');
                                        }])
                                            ->where('division_id', $unit->id)
                                            ->where('employee_profile_id', '<>', 1)
                                            ->when($religion, function ($query) use ($religion) {
                                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($religion) {
                                                    $q->where('religion', 'like', "%{$religion}%");
                                                });
                                            })
                                            ->when($search, function ($query) use ($search) {
                                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                    if (!empty($search)) {
                                                        $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                            ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                    }
                                                });
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
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('division_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($religion, function ($query) use ($religion) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($religion) {
                                            $q->where('religion', 'like', "%{$religion}%");
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('division_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($religion, function ($query) use ($religion) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($religion) {
                                                $q->where('religion', 'like', "%{$religion}%");
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'department':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($religion, function ($query) use ($religion) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($religion) {
                                        $q->where('religion', 'like', "%{$religion}%");
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $sections = Section::where('department_id', $area_id)->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('division_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($religion, function ($query) use ($religion) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($religion) {
                                            $q->where('religion', 'like', "%{$religion}%");
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('division_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($religion, function ($query) use ($religion) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($religion) {
                                                $q->where('religion', 'like', "%{$religion}%");
                                            });
                                        })
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'section':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($religion, function ($query) use ($religion) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($religion) {
                                        $q->where('religion', 'like', "%{$religion}%");
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $units = Unit::where('section_id', $area_id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('division_id', $unit->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($religion, function ($query) use ($religion) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($religion) {
                                            $q->where('religion', 'like', "%{$religion}%");
                                        });
                                    })
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );
                        }
                        break;

                    case 'unit':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('unit_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($religion, function ($query) use ($religion) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($religion) {
                                        $q->where('religion', 'like', "%{$religion}%");
                                    });
                                })
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );
                        break;
                    default:
                        return response()->json(['message', 'Invalid input. Please input a valid sector'], 400);
                }
            }

            // Ensure employees are unique based on employee_profile_id
            $employees = $employees->unique('employee_profile_id');

            // Sort employees by first name
            $employees = $employees->sortBy(function ($employee) {
                return $employee->employeeProfile->personalInformation->first_name;
            });

            // Paginate the results
            $current_page = LengthAwarePaginator::resolveCurrentPage();
            $paginated_employees = new LengthAwarePaginator(
                $employees->forPage($current_page, 10),
                $employees->count(),
                10,
                $current_page,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );

            // Transform and paginate employee data
            $data = EmployeesDetailsReportByReligion::collection($paginated_employees);
            $print_employees = EmployeesDetailsReportByReligion::collection($employees);

            if ($isPrint) {
                return Helpers::generatePdf($print_employees, $columns, $report_name, $orientation);
            }

            return response()->json([
                'pagination' => [
                    'current_page' => $paginated_employees->currentPage(),
                    'per_page' => $paginated_employees->perPage(),
                    'total' => $paginated_employees->total(),
                    'last_page' => $paginated_employees->lastPage(),
                    'has_more_pages' => $paginated_employees->hasMorePages(),
                ],
                'count' => COUNT($paginated_employees),
                'data' => EmployeesDetailsReportByReligion::collection($data),
                'message' => 'List of employees retrieved'
            ], ResponseAlias::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterEmployeeByBloodType', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function filterEmployeesByServiceLength(Request $request)
    {
        try {
            // Retrieve parameters from the request
            $sector = $request->sector;
            $area_id = $request->area_id;
            $search = $request->search;
            $page = $request->input('page', 1);  // Default to page 1 if not provided
            $perPage = $request->input('per_page', 10);  // Default to 10 items per page if not provided
            $service_length = $request->input('service_length', [5]);  // Default to 5 years if not provided
            if (!is_array($service_length)) {
                $service_length = explode(',', $service_length); // Converts comma-separated string to array
            }
            $service_length = array_map('intval', $service_length); // Convert all values to integers

            // Initialize an empty collection
            $employees = collect();

            if ((!$sector && $area_id) || ($sector && !$area_id)) {
                return response()->json(['message' => 'Invalid sector or area id input'], 400);
            }

            // Fetch employees based on sector and area_id
            if (!$sector && !$area_id) {
                $employees = AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                    $query->whereNull('deactivated_at');
                }])
                    ->where('employee_profile_id', '<>', 1)
                    ->get();
            } else {
                switch ($sector) {
                    case 'division':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->get()
                        );

                        $departments = Department::where('division_id', $area_id)->get();
                        foreach ($departments as $department) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('department_id', $department->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->get()
                            );

                            $sections = Section::where('department_id', $department->id)->get();
                            foreach ($sections as $section) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('section_id', $section->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->get()
                                );

                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $employees = $employees->merge(
                                        AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                            $query->whereNull('deactivated_at');
                                        }])
                                            ->where('unit_id', $unit->id)
                                            ->where('employee_profile_id', '<>', 1)
                                            ->get()
                                    );
                                }
                            }
                        }

                        // Get sections directly under the division (if any) that are not under any department
                        $sections = Section::where('division_id', $area_id)->whereNull('department_id')->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'department':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('department_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->get()
                        );

                        $sections = Section::where('department_id', $area_id)->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'section':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('section_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->get()
                        );

                        $units = Unit::where('section_id', $area_id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('unit_id', $unit->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->get()
                            );
                        }
                        break;

                    case 'unit':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('unit_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->get()
                        );
                        break;
                    default:
                        return response()->json(['message', 'Invalid input. Please input a valid sector'], 400);
                }
            }

            // Apply search filter if provided
            if ($search) {
                $employees = $employees->filter(function ($employee) use ($search) {
                    $employeeName = $employee->employeeProfile->personalInformation->fullName();
                    // Check if search term matches either the name
                    return stripos($employeeName, $search) !== false;
                });
            }
            // Sort employees by total years of service in descending order
            $employees = $employees->sortByDesc(function ($employee) {
                return $employee->service_length['total_years_zcmc_regular'] ?? 0;
            });

            // Calculate service length for each employee and handle potential null values
            $employees = $employees->map(function ($employee) {
                $employee->service_length = $this->calculateServiceLength($employee);
                return $employee;
            });

            // Apply service length filter
            if (!empty($service_length)) {
                $employees = $employees->filter(function ($employee) use ($service_length) {
                    // Ensure service_length and total_years_zcmc_regular are set before accessing them
                    if (!isset($employee->service_length['total_years_zcmc_regular'])) {
                        return false;
                    }

                    $totalYears = $employee->service_length['total_years_zcmc_regular'];
                    foreach ($service_length as $interval) {
                        if ($totalYears >= $interval && $totalYears < $interval + 5) {
                            return true;
                        }
                    }
                    return false;
                });
            }

            // After all merge operations
            $employees = $employees->unique('employee_profile_id');
            // Sort employees by total years of service safely
            $employees = $employees->sortBy(function ($employee) {
                return $employee->service_length['total_years_zcmc_regular'] ?? 0;
            });

            // Paginate the results
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $paginatedEmployees = new LengthAwarePaginator(
                $employees->forPage($currentPage, $perPage),
                $employees->count(),
                $perPage,
                $currentPage,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );

            return response()->json([
                'pagination' => [
                    'current_page' => $paginatedEmployees->currentPage(),
                    'per_page' => $paginatedEmployees->perPage(),
                    'total' => $paginatedEmployees->total(),
                    'last_page' => $paginatedEmployees->lastPage(),
                    'has_more_pages' => $paginatedEmployees->hasMorePages(),
                ],
                'count' => $paginatedEmployees->total(),
                'data' => EmployeesDetailsReport::collection($paginatedEmployees),
                'message' => 'List of employees by service length retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterEmployeesByServiceLength', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Helper function to calculate service length
    private function calculateServiceLength($employee): array
    {
        $total_months = 0;
        $total_zcmc = 0;
        $total_jo_service_months = 0;
        $total_jo_current_service_months = 0;
        $total_outside_zcmc = 0;

        foreach ($employee->employeeProfile->personalInformation->workExperience as $experience) {
            $date_from = Carbon::parse($experience->date_from);
            $date_to = Carbon::parse($experience->date_to);
            $months = $date_from->diffInMonths($date_to);

            if ($experience->company == "Zamboanga City Medical Center") {
                if ($experience->government_office === 'Yes') {
                    $total_zcmc += $months;
                }
                if ($experience->government_office === 'No') {
                    $total_jo_service_months += $months;
                }
            } else if ($experience->government_office === 'Yes') {
                // Calculate the service time for government work outside ZCMC
                $total_outside_zcmc += $months;
            }

            $total_months += $months;
        }

        // Calculate current service months
        $current_service_months = 0;
        $employee_profile = $employee->employeeProfile;

        if ($employee_profile->employment_type_id !== 5) {
            $date_hired = Carbon::parse($employee_profile->date_hired);
            $current_service_months = $date_hired->diffInMonths(Carbon::now());
        } else {
            $date_hired_jo = Carbon::parse($employee_profile->date_hired);
            $total_jo_current_service_months = $date_hired_jo->diffInMonths(Carbon::now());
        }

        // Calculate total months and years
        $total = $current_service_months + $total_months;
        $total_years = floor($total / 12);
        $total_remaining_months = $total % 12;

        // Calculate total service in ZCMC
        $total_months_in_zcmc = $total_zcmc + $current_service_months;
        $total_years_in_zcmc = floor($total_months_in_zcmc / 12);
        $total_remaining_months_in_zcmc = $total_months_in_zcmc % 12;

        // Calculate total years in government including ZCMC
        $total_months_with_zcmc = $total_months + $total_months_in_zcmc;
        $total_years_with_zcmc = floor($total_months_with_zcmc / 12);
        $total_remaining_months_with_zcmc = $total_months_with_zcmc % 12;

        // Calculate total years in ZCMC as JO / current (if ID JO)
        $total_jo_months = $total_jo_service_months + $total_jo_current_service_months;
        $total_jo_years = floor($total_jo_months / 12);
        $total_remaining_months_jo = $total_jo_months % 12;

        // Calculate total government service outside ZCMC
        $total_years_outside_zcmc = floor($total_outside_zcmc / 12);
        $total_remaining_months_outside_zcmc = $total_outside_zcmc % 12;

        return [
            'total_govt_months' => $total,
            'total_govt_years' => $total_years,
            'total_govt_remaining_months' => $total_remaining_months,
            'total_govt_months_with_zcmc' => $total_months_with_zcmc,
            'total_govt_years_with_zcmc' => $total_years_with_zcmc,
            'total_govt_remaining_months_with_zcmc' => $total_remaining_months_with_zcmc,
            'total_months_zcmc_regular' => $total_months_in_zcmc,
            'total_years_zcmc_regular' => $total_years_in_zcmc,
            'total_remaining_months_zcmc_regular' => $total_remaining_months_in_zcmc,
            'total_months_zcmc_as_jo' => $total_jo_months,
            'total_years_zcmc_as_jo' => $total_jo_years,
            'total_remaining_months_zcmc_as_jo' => $total_remaining_months_jo,
            'total_months_outside_zcmc' => $total_outside_zcmc,
            'total_years_outside_zcmc' => $total_years_outside_zcmc,
            'total_remaining_months_outside_zcmc' => $total_remaining_months_outside_zcmc,
        ];
    }

    /*******************************************************************************************
     *
     * OLD FUNCTIONS
     *
     *******************************************************************************************/

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
                $total_jo_months = $total_job_order_service_months + $total_job_order_current_service_months;
                $total_jo_years = floor($total_jo_months / 12);

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
            $temporary = $employees->filter(function ($row) {
                return $row->employment_type_id === 4;
            });
            $job_order = $employees->filter(function ($row) {
                return $row->employment_type_id === 5;
            });

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
                $temporary = $employees->filter(function ($row) {
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
                $temporary = $employees->filter(function ($row) {
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
                $temporary = $employees->filter(function ($row) {
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
