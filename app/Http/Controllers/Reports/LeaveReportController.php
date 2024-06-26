<?php

namespace App\Http\Controllers\Reports;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AssignArea;
use App\Models\Department;
use App\Models\Division;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Section;
use App\Models\Unit;
use App\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LeaveReportController extends Controller
{
    private $CONTROLLER_NAME = 'Leave Reports';

    /**
     * Filter leave reports based on provided criteria.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filterLeave(Request $request)
    {
        try {
            $areas = [];
            $sector = $request->sector;
            $report_format = strtolower($request->report_format);
            $status = $request->status;
            $area_under = strtolower($request->area_under);
            $area_id = $request->area_id;
            $leave_type_ids = $request->leave_type_ids ? $request->leave_type_ids : [];
            $date_from = $request->date_from;
            $date_to = $request->date_to;
            $sort_by = $request->sort_by;
            $limit = $request->limit;

            // Check if no filters are applied
            if (
                empty($sector) &&
                empty($status) &&
                empty($area_under) &&
                empty($area_id) &&
                empty($leave_type_ids) &&
                empty($date_from) &&
                empty($date_to) &&
                empty($sort_by) &&
                empty($limit)
            ) {
                $areas = $this->getAllData($report_format);
            }

            // Determine report format and fetch data accordingly
            switch ($report_format) {
                case 'area':
                    $areas = $this->getAreaFilter($sector, $status, $area_under, $area_id, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                    break;
                case 'employee':
                    $areas = $this->getEmployeeFilter($sector, $status, $area_under, $area_id, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                    break;
                default:
                    return response()->json(
                        [
                            'count' => count($areas),
                            'data' => $areas,
                            'message' => 'Invalid report format'
                        ],
                        Response::HTTP_OK
                    );
            }

            return response()->json([
                'count' => count($areas),
                'data' => $areas,
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

    /**
     * Retrieve all data based on report format.
     *
     * @param string $report_format
     * @return array
     */
    private function getAllData($report_format)
    {
        $areas = [];
        switch ($report_format) {
            case 'area':
                $divisions = Division::all();
                foreach ($divisions as $division) {
                    $areas = array_merge($areas, $this->getDivisionData($division->id, '', '', [], '', '', '', ''));
                }
                break;
            case 'employees':
                $employees = EmployeeProfile::all();
                foreach ($employees as $employee) {
                    $areas = array_merge($areas, $this->getEmployeeData($employee->id, '', '', [], '', '', '', ''));
                }
                break;
        }

        return $areas;
    }

    /**
     * Filter area data based on provided criteria.
     *
     * @param string $sector
     * @param string $status
     * @param string $area_under
     * @param int $area_id
     * @param array $leave_type_ids
     * @param string $date_from
     * @param string $date_to
     * @param string $sort_by
     * @param int $limit
     * @return array
     */
    private function getAreaFilter($sector, $status, $area_under, $area_id, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)
    {
        $areas = [];
        switch ($sector) {
            case 'division':
                $areas = $this->getDivisionData($area_id, $status, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                break;
            case 'department':
                $areas = $this->getDepartmentData($area_id, $status, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                break;
            case 'section':
                $areas = $this->getSectionData($area_id, $status, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                break;
            case 'unit':
                $areas = $this->getUnitData($area_id, $status, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                break;
        }

        return $areas;
    }

    /**
     * Filter employee data based on provided criteria.
     *
     * @param string $sector
     * @param string $status
     * @param string $area_under
     * @param int $area_id
     * @param array $leave_type_ids
     * @param string $date_from
     * @param string $date_to
     * @param string $sort_by
     * @param int $limit
     * @return array
     */
    private function getEmployeeFilter($sector, $status, $area_under, $area_id, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)
    {
        $employees = [];
        switch ($sector) {
            case 'division':
                $employees = $this->getEmployeesByDivision($area_id, $status, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                break;
            case 'department':
                $employees = $this->getEmployeesByDepartment($area_id, $status, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                break;
            case 'section':
                $employees = $this->getEmployeesBySection($area_id, $status, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                break;
            case 'unit':
                $employees = $this->getEmployeesByUnit($area_id, $status, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                break;
        }

        return $employees;
    }

    /**
     * Retrieve division data based on provided criteria.
     *
     * @param int $division_id
     * @param string $status
     * @param string $area_under
     * @param array $leave_type_ids
     * @param string $date_from
     * @param string $date_to
     * @param string $sort_by
     * @param int $limit
     * @return array
     */
    private function getDivisionData($division_id, $status, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)
    {
        $areas = [];

        if (empty($division_id) && empty($area_under)) {
            $divisions = Division::all();
            foreach ($divisions as $division) {
                $areas = array_merge($areas, $this->getDivisionData($division->id, $status, '', [], $date_from, $date_to, $sort_by, $limit));
            }

            return $areas;
        }

        if (!empty($area_under) && !empty($division_id)) {
            $division = Division::find($division_id);
            $areas = [$this->result($division, 'division', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)];

            if ($area_under === 'all') {
                $departments = Department::where('division_id', $division_id)->get();
                foreach ($departments as $department) {
                    $areas[] =  $this->result($department, 'department', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                    $sections = Section::where('department_id', $department->id)->get();
                    foreach ($sections as $section) {
                        $areas[] =  $this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $areas[] =  $this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                        }
                    }
                }
                // Get sections directly under the division (if any) that are not under any department
                $sections = Section::where('division_id', $division_id)->whereNull('department_id')->get();
                foreach ($sections as $section) {
                    $areas[] = $this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                    // Get all units directly under the section
                    $units = Unit::where('section_id', $section->id)->get();
                    foreach ($units as $unit) {
                        $areas[] = $this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                    }
                }
            } elseif ($area_under === 'staff') {
                $division = Division::find($division_id);
                $areas = [$this->result($division, 'division', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)];
            }
        }

        if (!empty($area_under) && empty($division_id)) {
            $divisions = Division::all();
            foreach ($divisions as $division) {
                $areas[] = $this->result($division, 'division', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                if ($area_under === 'all') {
                    $departments = Department::where('division_id', $division->id)->get();
                    foreach ($departments as $department) {
                        $areas[] =  $this->result($department, 'department', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                        $sections = Section::where('department_id', $department->id)->get();
                        foreach ($sections as $section) {
                            $areas[] =  $this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $areas[] =  $this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                            }
                        }
                    }
                    // Get sections directly under the division (if any) that are not under any department
                    $sections = Section::where('division_id', $division->id)->whereNull('department_id')->get();
                    foreach ($sections as $section) {
                        $areas[] = $this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                        // Get all units directly under the section
                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $areas[] = $this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                        }
                    }
                } elseif ($area_under === 'staff') {
                    // handle specific logic for employees if required
                    $division = Division::find($division_id);
                    $areas = [$this->result($division, 'division', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)];
                }
            }
        }

        return $areas;
    }

    /**
     * Retrieve department data based on provided criteria.
     *
     * @param int $department_id
     * @param string $status
     * @param string $area_under
     * @param array $leave_type_ids
     * @param string $date_from
     * @param string $date_to
     * @param string $sort_by
     * @param int $limit
     * @return array
     */
    private function getDepartmentData($department_id, $status, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)
    {
        $areas = [];

        if (empty($department_id) && empty($area_under)) {
            $departments = Department::all();
            foreach ($departments as $department) {
                $areas = array_merge($areas, $this->getDepartmentData($department->id, $status, '', [], $date_from, $date_to, $sort_by, $limit));
            }
            return $areas;
        }

        if (!empty($area_under) && !empty($department_id)) {
            $department = Department::find($department_id);
            $areas = [$this->result($department, 'department', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)];

            if ($area_under === 'all') {
                $sections = Section::where('department_id', $department->id)->get();
                foreach ($sections as $section) {
                    $areas[] = $this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                    $units = Unit::where('section_id', $section->id)->get();
                    foreach ($units as $unit) {
                        $areas[] = $this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                    }
                }
            } elseif ($area_under === 'staff') {
                $areas = [$this->result($department, 'department', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)];
            }
        }

        if (!empty($area_under) && empty($department_id)) {
            $departments = Department::all();
            foreach ($departments as $department) {
                $areas[] = $this->result($department, 'department', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);

                if ($area_under === 'all') {
                    $sections = Section::where('department_id', $department->id)->get();
                    foreach ($sections as $section) {
                        $areas[] = $this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $areas[] = $this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                        }
                    }
                } elseif ($area_under === 'staff') {
                    $areas[] = $this->result($department, 'department', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                }
            }
        }

        return $areas;
    }

    /**
     * Retrieve section data based on provided criteria.
     *
     * @param int $section_id
     * @param string $status
     * @param string $area_under
     * @param array $leave_type_ids
     * @param string $date_from
     * @param string $date_to
     * @param string $sort_by
     * @param int $limit
     * @return array
     */
    private function getSectionData($section_id, $status, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)
    {
        $areas = [];

        if (empty($section_id) && empty($area_under)) {
            $sections = Section::all();
            foreach ($sections as $section) {
                $areas = array_merge($areas, $this->getSectionData($section->id, $status, '', [], $date_from, $date_to, $sort_by, $limit));
            }
            return $areas;
        }

        if (!empty($area_under) && !empty($section_id)) {
            $section = Section::find($section_id);
            $areas = [$this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)];

            if ($area_under === 'all') {
                $units = Unit::where('section_id', $section->id)->get();
                foreach ($units as $unit) {
                    $areas[] = $this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                }
            } elseif ($area_under === 'staff') {
                $areas = [$this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)];
            }
        }

        if (!empty($area_under) && empty($section_id)) {
            $sections = Section::all();
            foreach ($sections as $section) {
                $areas[] = $this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);

                if ($area_under === 'all') {
                    $units = Unit::where('section_id', $section->id)->get();
                    foreach ($units as $unit) {
                        $areas[] = $this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                    }
                } elseif ($area_under === 'staff') {
                    $areas[] = $this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                }
            }
        }

        return $areas;
    }

    /**
     * Retrieve unit data based on provided criteria.
     *
     * @param int $unit_id
     * @param string $status
     * @param string $area_under
     * @param array $leave_type_ids
     * @param string $date_from
     * @param string $date_to
     * @param string $sort_by
     * @param int $limit
     * @return array
     */
    private function getUnitData($unit_id, $status, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)
    {
        $areas = [];

        if (empty($unit_id) && empty($area_under)) {
            $units = Unit::all();
            foreach ($units as $unit) {
                $areas = array_merge($areas, $this->getUnitData($unit->id, $status, '', [], $date_from, $date_to, $sort_by, $limit));
            }
            return $areas;
        }

        if (!empty($area_under) && !empty($unit_id)) {
            $unit = Unit::find($unit_id);
            $areas = [$this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)];
        }

        if (!empty($area_under) && empty($unit_id)) {
            $units = Unit::all();
            foreach ($units as $unit) {
                $areas[] = $this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
            }
        }

        return $areas;
    }

    /**
     * Retrieve employee data based on provided criteria.
     *
     * @param int $division_id
     * @param string $status
     * @param string $area_under
     * @param array $leave_type_ids
     * @param string $date_from
     * @param string $date_to
     * @param string $sort_by
     * @param int $limit
     * @return array
     */
    private function getEmployeesByDivision($division_id, $status, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)
    {
        $arr_employees = [];

        // if (empty($division_id) && empty($area_under)) {
        //     $divisions = Division::all();

        //     foreach ($divisions as $division) {
        //         $departments = Department::where('division_id', $division->id)->get();
        //         foreach ($departments as $department) {
        //             $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
        //                 ->where('department_id', $department->id)
        //                 ->get();

        //             foreach ($assignAreas as $assignArea) {
        //                 $arr_employees[] = $this->resultEmployee(
        //                     $assignArea->employeeProfile,
        //                     'department',
        //                     $status,
        //                     $leave_type_ids,
        //                     $date_from,
        //                     $date_to,
        //                     $sort_by,
        //                     $limit
        //                 );
        //             }

        //             $sections = Section::where('department_id', $department->id)->get();
        //             foreach ($sections as $section) {
        //                 $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
        //                     ->where('section_id', $section->id)
        //                     ->get();

        //                 foreach ($assignAreas as $assignArea) {
        //                     $arr_employees[] = $this->resultEmployee(
        //                         $assignArea->employeeProfile,
        //                         'section',
        //                         $status,
        //                         $leave_type_ids,
        //                         $date_from,
        //                         $date_to,
        //                         $sort_by,
        //                         $limit
        //                     );
        //                 }

        //                 $units = Unit::where('section_id', $section->id)->get();
        //                 foreach ($units as $unit) {
        //                     $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
        //                         ->where('unit_id', $unit->id)
        //                         ->get();

        //                     foreach ($assignAreas as $assignArea) {
        //                         $arr_employees[] = $this->resultEmployee(
        //                             $assignArea->employeeProfile,
        //                             'unit',
        //                             $status,
        //                             $leave_type_ids,
        //                             $date_from,
        //                             $date_to,
        //                             $sort_by,
        //                             $limit
        //                         );
        //                     }
        //                 }
        //             }
        //         }

        //         // Get sections directly under the division (if any) that are not under any department
        //         $sections = Section::where('division_id', $division_id)->whereNull('department_id')->get();
        //         foreach ($sections as $section) {
        //             $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
        //                 ->where('section_id', $section->id)
        //                 ->get();

        //             foreach ($assignAreas as $assignArea) {
        //                 $arr_employees[] = $this->resultEmployee(
        //                     $assignArea->employeeProfile,
        //                     'section',
        //                     $status,
        //                     $leave_type_ids,
        //                     $date_from,
        //                     $date_to,
        //                     $sort_by,
        //                     $limit
        //                 );
        //             }

        //             // Get all units directly under the section
        //             $units = Unit::where('section_id', $section->id)->get();
        //             foreach ($units as $unit) {
        //                 $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
        //                     ->where('unit_id', $unit->id)
        //                     ->get();

        //                 foreach ($assignAreas as $assignArea) {
        //                     $arr_employees[] = $this->resultEmployee(
        //                         $assignArea->employeeProfile,
        //                         'unit',
        //                         $status,
        //                         $leave_type_ids,
        //                         $date_from,
        //                         $date_to,
        //                         $sort_by,
        //                         $limit
        //                     );
        //                 }
        //             }
        //         }
        //     }
        //     return $arr_employees;
        // }

        if (!empty($area_under) && !empty($division_id)) {
            if ($area_under === 'all') {
                // $division = Division::find($division_id);
                $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                    ->where('division_id', $division_id)
                    ->distinct()
                    ->get();

                foreach ($assignAreas as $assignArea) {

                    $arr_employees[] = $this->resultEmployee(
                        $assignArea->employeeProfile,
                        'division',
                        $status,
                        $leave_type_ids,
                        $date_from,
                        $date_to,
                        $sort_by,
                        $limit
                    );
                }

                $departments = Department::where('division_id', $division_id)->get();
                foreach ($departments as $department) {
                    $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                        ->where('department_id', $department->id)
                        ->get();

                    foreach ($assignAreas as $assignArea) {
                        $arr_employees[] = $this->resultEmployee(
                            $assignArea->employeeProfile,
                            'department',
                            $status,
                            $leave_type_ids,
                            $date_from,
                            $date_to,
                            $sort_by,
                            $limit
                        );
                    }

                    $sections = Section::where('department_id', $department->id)->get();
                    foreach ($sections as $section) {
                        $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                            ->where('section_id', $section->id)
                            ->get();

                        foreach ($assignAreas as $assignArea) {
                            $arr_employees[] = $this->resultEmployee(
                                $assignArea->employeeProfile,
                                'section',
                                $status,
                                $leave_type_ids,
                                $date_from,
                                $date_to,
                                $sort_by,
                                $limit
                            );
                        }

                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                                ->where('unit_id', $unit->id)
                                ->get();

                            foreach ($assignAreas as $assignArea) {
                                $arr_employees[] = $this->resultEmployee(
                                    $assignArea->employeeProfile,
                                    'unit',
                                    $status,
                                    $leave_type_ids,
                                    $date_from,
                                    $date_to,
                                    $sort_by,
                                    $limit
                                );
                            }
                        }
                    }
                }

                // Get sections directly under the division (if any) that are not under any department
                $sections = Section::where('division_id', $division_id)->whereNull('department_id')->get();
                foreach ($sections as $section) {
                    $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                        ->where('section_id', $section->id)
                        ->get();

                    foreach ($assignAreas as $assignArea) {
                        $arr_employees[] = $this->resultEmployee(
                            $assignArea->employeeProfile,
                            'section',
                            $status,
                            $leave_type_ids,
                            $date_from,
                            $date_to,
                            $sort_by,
                            $limit
                        );
                    }

                    // Get all units directly under the section
                    $units = Unit::where('section_id', $section->id)->get();
                    foreach ($units as $unit) {
                        $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                            ->where('unit_id', $unit->id)
                            ->get();

                        foreach ($assignAreas as $assignArea) {
                            $arr_employees[] = $this->resultEmployee(
                                $assignArea->employeeProfile,
                                'unit',
                                $status,
                                $leave_type_ids,
                                $date_from,
                                $date_to,
                                $sort_by,
                                $limit
                            );
                        }
                    }
                }
            } elseif ($area_under === 'staff') {
                $assignAreas = AssignArea::with(['employeeProfile', 'division'])
                    ->where('division_id', $division_id)
                    ->distinct()
                    ->get();

                foreach ($assignAreas as $assignArea) {
                    $arr_employees[] = $this->resultEmployee(
                        $assignArea->employeeProfile,
                        'division',
                        $status,
                        $leave_type_ids,
                        $date_from,
                        $date_to,
                        $sort_by,
                        $limit
                    );
                }
            }
        }

        if (!empty($area_under) && empty($division_id)) {
            $divisions = Division::all();
            foreach ($divisions as $division) {
                $arr_employees = array_merge($arr_employees, $this->getEmployeesByDivision($division->id, $status, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by, $limit));
            }
        }

        return $arr_employees;
    }

    /**
     * Retrieve employee data based on provided department criteria.
     *
     * @param int $department_id
     * @param string $status
     * @param string $area_under
     * @param array $leave_type_ids
     * @param string $date_from
     * @param string $date_to
     * @param string $sort_by
     * @param int $limit
     * @return array
     */
    private function getEmployeesByDepartment($department_id, $status, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)
    {
        $arr_employees = [];

        if (!empty($area_under) && !empty($department_id)) {
            if ($area_under === 'all') {
                $assignedAreas = AssignArea::with(['employeeProfile', 'department', 'section', 'unit'])
                    ->where('department_id', $department_id)
                    ->get();

                foreach ($assignedAreas as $assignedArea) {
                    $arr_employees[] = $this->resultEmployee(
                        $assignedArea->employeeProfile,
                        'department',
                        $status,
                        $leave_type_ids,
                        $date_from,
                        $date_to,
                        $sort_by,
                        $limit
                    );
                }
                $sections = Section::where('department_id', $department_id)->get();
                foreach ($sections as $section) {
                    $assignAreas = AssignArea::with(['employeeProfile', 'department', 'section', 'unit'])
                        ->where('section_id', $section->id)
                        ->get();

                    foreach ($assignAreas as $assignArea) {
                        $arr_employees[] = $this->resultEmployee(
                            $assignArea->employeeProfile,
                            'section',
                            $status,
                            $leave_type_ids,
                            $date_from,
                            $date_to,
                            $sort_by,
                            $limit
                        );
                    }

                    $units = Unit::where('section_id', $section->id)->get();
                    foreach ($units as $unit) {
                        $assignAreas = AssignArea::with(['employeeProfile', 'department', 'section', 'unit'])
                            ->where('unit_id', $unit->id)
                            ->get();

                        foreach ($assignAreas as $assignArea) {
                            $arr_employees[] = $this->resultEmployee(
                                $assignArea->employeeProfile,
                                'unit',
                                $status,
                                $leave_type_ids,
                                $date_from,
                                $date_to,
                                $sort_by,
                                $limit
                            );
                        }
                    }
                }
            } elseif ($area_under === 'staff') {
                $assignedAreas = AssignArea::with(['employeeProfile', 'department'])
                    ->where('department_id', $department_id)
                    ->get();

                foreach ($assignedAreas as $assignedArea) {
                    $arr_employees[] = $this->resultEmployee(
                        $assignedArea->employeeProfile,
                        'department',
                        $status,
                        $leave_type_ids,
                        $date_from,
                        $date_to,
                        $sort_by,
                        $limit
                    );
                }
            }
        }

        return $arr_employees;
    }

    /**
     * Retrieve employee data based on provided section criteria.
     *
     * @param int $section_id
     * @param string $status
     * @param string $area_under
     * @param array $leave_type_ids
     * @param string $date_from
     * @param string $date_to
     * @param string $sort_by
     * @param int $limit
     * @return array
     */
    private function getEmployeesBySection($section_id, $status, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)
    {
        $arr_employees = [];
        if (!empty($area_under) && !empty($section_id)) {
            if ($area_under === 'all') {
                $assignedAreas = AssignArea::with(['employeeProfile', 'section', 'unit'])
                    ->where('section_id', $section_id)
                    ->get();

                foreach ($assignedAreas as $assignedArea) {
                    $arr_employees[] = $this->resultEmployee(
                        $assignedArea->employeeProfile,
                        'section',
                        $status,
                        $leave_type_ids,
                        $date_from,
                        $date_to,
                        $sort_by,
                        $limit
                    );
                }
                $units = Unit::where('section_id', $section_id)->get();
                foreach ($units as $unit) {
                    $assignAreas = AssignArea::with(['employeeProfile', 'department', 'section', 'unit'])
                        ->where('unit_id', $unit->id)
                        ->get();

                    foreach ($assignAreas as $assignArea) {
                        $arr_employees[] = $this->resultEmployee(
                            $assignArea->employeeProfile,
                            'unit',
                            $status,
                            $leave_type_ids,
                            $date_from,
                            $date_to,
                            $sort_by,
                            $limit
                        );
                    }
                }
            } elseif ($area_under === 'staff') {
                $assignedAreas =  AssignArea::with(['employeeProfile', 'section'])
                    ->where('section_id', $section_id)
                    ->get();

                foreach ($assignedAreas as $assignedArea) {
                    $arr_employees[] = $this->resultEmployee(
                        $assignedArea->employeeProfile,
                        'section',
                        $status,
                        $leave_type_ids,
                        $date_from,
                        $date_to,
                        $sort_by,
                        $limit
                    );
                }
            }
        }

        return $arr_employees;
    }

    /**
     * Retrieve employee data based on provided unit criteria.
     *
     * @param int $unit_id
     * @param string $status
     * @param string $area_under
     * @param array $leave_type_ids
     * @param string $date_from
     * @param string $date_to
     * @param string $sort_by
     * @param int $limit
     * @return array
     */
    private function getEmployeesByUnit($unit_id, $status, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)
    {
        $arr_employees = [];
        if (!empty($area_under) && !empty($unit_id)) {
            $assignedAreas = AssignArea::with(['employeeProfile', 'unit'])
                ->where('unit_id', $unit_id)
                ->get();

            foreach ($assignedAreas as $assignedArea) {
                $arr_employees[] = $this->resultEmployee(
                    $assignedArea->employeeProfile,
                    'unit',
                    $status,
                    $leave_type_ids,
                    $date_from,
                    $date_to,
                    $sort_by,
                    $limit
                );
            }
        }

        return $arr_employees;
    }


    /**
     * Format area data for the result.
     *
     * @param mixed $area
     * @param string $sector
     * @param string $status
     * @param array $leave_type_ids
     * @param string $date_from
     * @param string $date_to
     * @param string $sort_by
     * @param int $limit
     * @return array
     */
    private function result($area, $sector, $status, $leave_type_ids = [], $date_from, $date_to, $sort_by, $limit)
    {
        // Initialize the result array with area details and leave counts
        $area_data = [
            'id' => $area->id . '-' . $sector,
            'name' => $area->name,
            'code' => $area->code,
            'sector' => ucfirst($sector),
            'leave_count' => 0, // Initialize leave count
            'leave_count_with_pay' => 0,
            'leave_count_without_pay' => 0,
            'leave_types' => [] // Initialize leave types array
        ];

        // Initialize additional leave count fields only if status is not empty
        if (!empty($status)) {
            $area_data['leave_count_received'] = 0;
            $area_data['leave_count_cancelled'] = 0;
            $area_data['leave_count_approved'] = 0;
        }

        // Build the leave applications query with necessary relationships and filters
        $leave_applications = LeaveApplication::with(['leaveType'])
            ->whereHas('employeeProfile.assignedAreas', function ($query) use ($area, $sector) {
                // Filter by sector and area id
                $query->where($sector . '_id', $area->id);
            });

        // Filter by leave type ids if provided
        if (!empty($leave_type_ids)) {
            $leave_applications->whereIn('leave_type_id', $leave_type_ids);
        }

        // Apply status filter if provided
        if (!empty($status)) {
            $leave_applications->where('status', $status);
        }

        // Apply date filters if provided
        if (!empty($date_from)) {
            $leave_applications->where('date_from', '>=', $date_from);
        }

        if (!empty($date_to)) {
            $leave_applications->where('date_to', '<=', $date_to);
        }

        // Apply sorting if provided
        if (!empty($sort_by)) {
            $leave_applications->orderBy('created_at', $sort_by);
        }

        // Apply limit if provided
        if (!empty($limit)) {
            $leave_applications->limit($limit);
        }

        // Execute the query and get results
        $leave_applications = $leave_applications->get();

        // Process results to count leaves and aggregate leave types data
        $leave_count_total = $leave_applications->count();
        $leave_count_with_pay_total = $leave_applications->where('without_pay', 0)->count();
        $leave_count_without_pay_total = $leave_applications->where('without_pay', 1)->count();
        $leave_types_data = [];

        // Only calculate specific leave counts if status is not empty
        if (!empty($status)) {
            $leave_count_total_received = $leave_applications->where('status', 'received')->count();
            $leave_count_total_cancelled = $leave_applications->where('status', 'cancelled')->count();
            $leave_count_total_approved = $leave_applications->where('status', 'approved')->count();
        }

        foreach ($leave_applications as $application) {
            $leave_type = $application->leaveType;
            if ($leave_type) {
                if (!isset($leave_types_data[$leave_type->id])) {
                    $leave_types_data[$leave_type->id] = [
                        'id' => $leave_type->id,
                        'name' => $leave_type->name,
                        'code' => $leave_type->code,
                        'count' => 0
                    ];
                }
                $leave_types_data[$leave_type->id]['count']++;
            }
        }

        // If leave_type_ids is empty, load all leave types with a count of 0 for those not in the results
        if (empty($leave_type_ids)) {
            $all_leave_types = LeaveType::all();
            foreach ($all_leave_types as $leave_type) {
                if (!isset($leave_types_data[$leave_type->id])) {
                    $leave_types_data[$leave_type->id] = [
                        'id' => $leave_type->id,
                        'name' => $leave_type->name,
                        'code' => $leave_type->code,
                        'count' => 0
                    ];
                }
            }
        } else {
            foreach ($leave_type_ids as $leave_type_id) {
                if (!isset($leave_types_data[$leave_type_id])) {
                    $leave_type = LeaveType::find($leave_type_id);
                    if ($leave_type) {
                        $leave_types_data[$leave_type->id] = [
                            'id' => $leave_type->id,
                            'name' => $leave_type->name,
                            'code' => $leave_type->code,
                            'count' => 0 // If no applications, count remains 0
                        ];
                    }
                }
            }
        }

        // Update area data with aggregated leave counts and leave types
        $area_data['leave_count'] = $leave_count_total;
        $area_data['leave_count_with_pay'] = $leave_count_with_pay_total;
        $area_data['leave_count_without_pay'] = $leave_count_without_pay_total;
        $area_data['leave_types'] = array_values($leave_types_data);

        // Only update specific leave counts if status is not empty
        if (!empty($status)) {
            $area_data['leave_count_received'] = $leave_count_total_received;
            $area_data['leave_count_cancelled'] = $leave_count_total_cancelled;
            $area_data['leave_count_approved'] = $leave_count_total_approved;
        }

        return $area_data;
    }


    /**
     * Format employee data for the result.
     *
     * @param EmployeeProfile $employee
     * @param string $sector
     * @param string $status
     * @param array $leave_type_ids
     * @param string $date_from
     * @param string $date_to
     * @param string $sort_by
     * @param int $limit
     * @return array
     */
    private function resultEmployee($employee, $sector, $status, $leave_type_ids = [], $date_from, $date_to, $sort_by, $limit)
    {
        // Initialize the result array with employee details and leave counts
        $employee_data = [
            'id' => $employee->id,
            'employee_profile_id' => $employee->personal_information_id,
            'employee_name' => $employee->personalInformation->employeeName(),
            'designation' => $employee->findDesignation(),
            'leave_count' => 0, // Initialize leave count
            'leave_count_with_pay' => 0,
            'leave_count_without_pay' => 0,
            'sector' => ucfirst($sector),
            'area_name' => $employee->assignedArea->findDetails()['details']['name'],
            'area_code' => $employee->assignedArea->findDetails()['details']['code'],
            'leave_types' => [] // Initialize leave types array
        ];

        // Initialize additional leave count fields only if status is not empty
        if (!empty($status)) {
            $employee_data['leave_count_received'] = 0;
            $employee_data['leave_count_cancelled'] = 0;
            $employee_data['leave_count_approved'] = 0;
        }

        // Build the leave applications query with necessary relationships and filters
        $leave_applications = LeaveApplication::with(['leaveType'])
            ->where('employee_profile_id', $employee->id);

        // Filter by leave type ids if provided
        if (!empty($leave_type_ids)) {
            $leave_applications->whereIn('leave_type_id', $leave_type_ids);
        }

        // Apply status filter if provided
        if (!empty($status)) {
            $leave_applications->where('status', $status);
        }

        // Apply date filters if provided
        if (!empty($date_from)) {
            $leave_applications->where('date_from', '>=', $date_from);
        }

        if (!empty($date_to)) {
            $leave_applications->where('date_to', '<=', $date_to);
        }

        // Apply sorting if provided
        if (!empty($sort_by)) {
            $leave_applications->orderBy('created_at', $sort_by);
        }

        // Apply limit if provided
        if (!empty($limit)) {
            $leave_applications->limit($limit);
        }

        // Execute the query and get results
        $leave_applications = $leave_applications->get();

        // Process results to count leaves and aggregate leave types data
        $leave_count_total = $leave_applications->count();
        $leave_count_with_pay_total = $leave_applications->where('without_pay', 0)->count();
        $leave_count_without_pay_total = $leave_applications->where('without_pay', 1)->count();
        $leave_types_data = [];

        // Only calculate specific leave counts if status is not empty
        if (!empty($status)) {
            $leave_count_total_received = $leave_applications->where('status', 'received')->count();
            $leave_count_total_cancelled = $leave_applications->where('status', 'cancelled')->count();
            $leave_count_total_approved = $leave_applications->where('status', 'approved')->count();
        }

        foreach ($leave_applications as $application) {
            $leave_type = $application->leaveType;
            if ($leave_type) {
                if (!isset($leave_types_data[$leave_type->id])) {
                    $leave_types_data[$leave_type->id] = [
                        'id' => $leave_type->id,
                        'name' => $leave_type->name,
                        'count' => 0
                    ];
                }
                $leave_types_data[$leave_type->id]['count']++;
            }
        }

        // If leave_type_ids is empty, load all leave types with a count of 0 for those not in the results
        if (empty($leave_type_ids)) {
            $all_leave_types = LeaveType::all();
            foreach ($all_leave_types as $leave_type) {
                if (!isset($leave_types_data[$leave_type->id])) {
                    $leave_types_data[$leave_type->id] = [
                        'id' => $leave_type->id,
                        'name' => $leave_type->name,
                        'count' => 0
                    ];
                }
            }
        } else {
            foreach ($leave_type_ids as $leave_type_id) {
                if (!isset($leave_types_data[$leave_type_id])) {
                    $leave_type = LeaveType::find($leave_type_id);
                    if ($leave_type) {
                        $leave_types_data[$leave_type->id] = [
                            'id' => $leave_type->id,
                            'name' => $leave_type->name,
                            'count' => 0 // If no applications, count remains 0
                        ];
                    }
                }
            }
        }

        // Update employee data with aggregated leave counts and leave types
        $employee_data['leave_count'] = $leave_count_total;
        $employee_data['leave_count_with_pay'] = $leave_count_with_pay_total;
        $employee_data['leave_count_without_pay'] = $leave_count_without_pay_total;
        $employee_data['leave_types'] = array_values($leave_types_data);

        // Only update specific leave counts if status is not empty
        if (!empty($status)) {
            $employee_data['leave_count_received'] = $leave_count_total_received;
            $employee_data['leave_count_cancelled'] = $leave_count_total_cancelled;
            $employee_data['leave_count_approved'] = $leave_count_total_approved;
        }

        return $employee_data;
    }
}