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

    private function processArea($area, $sector)
    {
        $leave_count = 0;
        $leave_count_total_with_pay = 0;
        $leave_count_total_without_pay = 0;
        $leave_count_cancelled = 0;
        $leave_count_approved = 0;
        $leave_count_received = 0;

        // Get all leave types
        $all_leave_types = LeaveType::all();

        // Initialize leave type count array with all leave types, setting count to 0
        $leave_types_count = [];
        foreach ($all_leave_types as $leave_type) {
            $leave_types_count[$leave_type->id] = [
                'id' => $leave_type->id,
                'name' => $leave_type->name,
                'code' => $leave_type->code,
                'count' => 0,
            ];
        }

        $assign_areas = $area->assignArea;
        foreach ($assign_areas as $assign_area) {

            // Get leave applications data
            $leave_applications = $assign_area->employeeProfile->leaveApplications;

            foreach ($leave_applications as $leave_application) {
                // Count leave types
                $leave_type = $leave_application->leaveType;
                if ($leave_type) {
                    $leave_type_id = $leave_type->id;
                    // Increment count for the applied leave type
                    $leave_types_count[$leave_type_id]['count']++;
                }
            }

            $leave_count += $leave_applications->count();
            $leave_count_total_with_pay += $leave_applications->where('without_pay', 0)->count();
            $leave_count_total_without_pay += $leave_applications->where('without_pay', 1)->count();
            $leave_count_cancelled += $leave_applications->where('status', 'cancelled')->count();
            $leave_count_approved += $leave_applications->where('status', 'approved')->count();
            $leave_count_received += $leave_applications->where('status', 'received')->count();
        }

        // Sort leave types by their IDs
        $leave_types_sorted = collect($leave_types_count)->sortBy('id')->values()->toArray();

        return [
            'id' => $area->id,
            'name' => $area->name,
            'code' => $area->code,
            'sector' => ucfirst($sector),
            'leave_count' => $leave_count,
            'leave_count_with_pay' => $leave_count_total_with_pay,
            'leave_count_without_pay' => $leave_count_total_without_pay,
            'leave_count_received' => $leave_count_received,
            'leave_count_cancelled' => $leave_count_cancelled,
            'leave_count_approved' => $leave_count_approved,
            'leave_types' => $leave_types_sorted, // Sorted leave types
        ];
    }


    private function GenerateReportSummary($report_format, $data)
    {
        try {
            switch ($report_format) {
                case 'area':

                    break;
                case 'employee':
                    break;
                default:
                    return response()->json([
                        'message' => 'Invalid report format'
                    ]);
            }
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'GenerateReportLeaveByArea', $th->getMessage());
            return response()->json(
                [
                    'message' => $th->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    private function GenerateReportLeaveByArea($areas, $sector)
    {
        try {
            $data = [];

            foreach ($areas as $area) {
                $data[] = $this->processArea($area, $sector);
            }

            return $data;
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'GenerateReportLeaveByArea', $th->getMessage());
            return response()->json(
                [
                    'message' => $th->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }


    private function GenerateReportLeaveByEmployees($employee)
    {
        try {
            $leave_count = 0;
            $leave_count_total_with_pay = 0;
            $leave_count_total_without_pay = 0;
            $leave_count_cancelled = 0;
            $leave_count_approved = 0;
            $leave_count_received = 0;
            $leave_count_applied = 0;

            $leave_types_count = [];

            // Skip if no employeeProfile
            if (!$employee->employeeProfile) {
                return null;
            }

            // Get all leave types
            $all_leave_types = LeaveType::all();

            // Initialize leave types count with all leave types, setting count to 0
            foreach ($all_leave_types as $leave_type) {
                $leave_types_count[$leave_type->id] = [
                    'id' => $leave_type->id,
                    'name' => $leave_type->name,
                    'code' => $leave_type->code,
                    'count' => 0,
                ];
            }

            // Ensure leaveApplications is a collection or array
            $leave_applications = $employee->employeeProfile->leaveApplications;
            if (!$leave_applications instanceof \Illuminate\Support\Collection) {
                $leave_applications = collect($leave_applications);
            }

            foreach ($leave_applications as $leave_application) {
                $leave_type = $leave_application->leaveType;
                $leave_type_id = $leave_type->id;

                // Increment count for the applied leave type
                $leave_types_count[$leave_type_id]['count']++;

                $leave_count++;
                if ($leave_application->without_pay) {
                    $leave_count_total_without_pay++;
                } else {
                    $leave_count_total_with_pay++;
                }

                // Count based on status
                switch ($leave_application->status) {
                    case 'cancelled':
                        $leave_count_cancelled++;
                        break;
                    case 'approved':
                        $leave_count_approved++;
                        break;
                    case 'received':
                        $leave_count_received++;
                        break;
                    case 'applied':
                        $leave_count_applied++;
                        break;
                }
            }

            $leave_types_sorted = collect($leave_types_count)->sortBy('id')->values()->toArray();

            return [
                'id' => $employee->employeeProfile->id,
                'employee_id' => $employee->employeeProfile->employee_id,
                'employee_name' => $employee->employeeProfile->personalInformation->employeeName(),
                'personal_information_id' => $employee->employeeProfile->personal_information_id,
                'designation' => $employee->employeeProfile->findDesignation()['name'],
                'designation_code' => $employee->employeeProfile->findDesignation()['code'],
                'sector' => ucfirst($employee->employeeProfile->assignedArea->findDetails()['sector']),
                'area_name' => $employee->employeeProfile->assignedArea->findDetails()['details']['name'],
                'area_code' => $employee->employeeProfile->assignedArea->findDetails()['details']['code'],
                'leave_count' => $leave_count,
                'leave_count_with_pay' => $leave_count_total_with_pay,
                'leave_count_without_pay' => $leave_count_total_without_pay,
                'leave_count_received' => $leave_count_received,
                'leave_count_cancelled' => $leave_count_cancelled,
                'leave_count_approved' => $leave_count_approved,
                'leave_count_applied' => $leave_count_applied,
                'leave_types' => $leave_types_sorted,
            ];
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'GenerateReportLeaveByArea', $th->getMessage());
            return response()->json(
                [
                    'message' => $th->getMessage()
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    private function getFilteredLeaveTypes($leave_type_ids)
    {
        // If $leave_type_ids is empty, return all active leave types
        if (empty($leave_type_ids)) {
            return LeaveType::where('is_active', 1)->pluck('name');
        }

        // If specific leave_type_ids are provided, return matching leave types
        return LeaveType::whereIn('id', $leave_type_ids)
            ->where('is_active', 1)
            ->pluck('name');
    }

    private function getReportColumns($report_format)
    {
        // Common fields shared by both report formats
        $common_fields = [
            [
                "field" => "leave_count",
                "headerName" => "Total Applications",
                "flex" => 1.2
            ],
            [
                "field" => "vl",
                "flex" => 1,
                "headerName" => "VL"
            ],
            [
                "field" => "sl",
                "flex" => 1,
                "headerName" => "SL"
            ],
            [
                "field" => "spl",
                "flex" => 1,
                "headerName" => "SPL"
            ],
            [
                "field" => "fl",
                "flex" => 1,
                "headerName" => "FL"
            ],
            [
                "field" => "leave_types",
                "flex" => 1,
                "headerName" => "Other leave types"
            ],
            [
                "field" => "leave_count_applied",
                "flex" => 1,
                "headerName" => "Pending/Applied"
            ],
            [
                "field" => "leave_count_approved",
                "flex" => 1,
                "headerName" => "Approved"
            ],
            [
                "field" => "leave_count_received",
                "flex" => 1,
                "headerName" => "Received"
            ],
            [
                "field" => "leave_count_cancelled",
                "flex" => 1,
                "headerName" => "Cancelled"
            ]
        ];

        // Format-specific fields
        $format_fields = match ($report_format) {
            'area' => [
                [
                    "field" => "name",
                    "headerName" => "Area Name",
                    "flex" => 1.2
                ],
                [
                    "field" => "sector",
                    "flex" => 1,
                    "headerName" => "Type of area"
                ]
            ],

            'employee' => [
                [
                    "field" => "employee_name",
                    "headerName" => "Employee Name",
                    "flex" => 1.2
                ],
                [
                    "field" => "area_assigned",
                    "flex" => 1,
                    "headerName" => "Area Assigned"
                ]
            ],

            default => [] // In case the report_format doesn't match any case
        };

        // Combine the common fields with format-specific fields
        return array_merge($format_fields, $common_fields);
    }


    private function generateLeaveReportSummary($results)
    {
        $summary = [
            'total_applications' => 0,
            'total_with_pay' => 0,
            'total_without_pay' => 0,
            'total_cancelled' => 0,
            'total_approved' => 0,
            'total_received' => 0,
            'total_applied' => 0,
            'leave_type_counts' => []
        ];

        // Loop through each result and aggregate the counts
        foreach ($results as $result) {
            $summary['total_applications'] += $result['leave_count'];
            $summary['total_with_pay'] += $result['leave_count_with_pay'];
            $summary['total_without_pay'] += $result['leave_count_without_pay'];
            $summary['total_cancelled'] += $result['leave_count_cancelled'];
            $summary['total_approved'] += $result['leave_count_approved'];
            $summary['total_received'] += $result['leave_count_received'];
            $summary['total_applied'] += $result['leave_count_applied'];

            // Loop through the leave types and aggregate the counts per leave type
            foreach ($result['leave_types'] as $leave_type) {
                $leave_type_id = $leave_type['id'];
                if (!isset($summary['leave_type_counts'][$leave_type_id])) {
                    $summary['leave_type_counts'][$leave_type_id] = [
                        'id' => $leave_type_id,
                        'name' => $leave_type['name'],
                        'code' => $leave_type['code'],
                        'count' => 0
                    ];
                }
                $summary['leave_type_counts'][$leave_type_id]['count'] += $leave_type['count'];
            }
        }

        // Optionally sort leave types by ID
        ksort($summary['leave_type_counts']);

        return $summary;
    }

    /**
     * Filter leave reports based on provided criteria.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filterLeave(Request $request)
    {
        try {
            $results = [];
            $sector = $request->query('sector');
            $report_format = strtolower($request->query('report_format'));
            $status = $request->query('status');
            $area_under = strtolower($request->query('area_under'));
            $area_id = $request->query('area_id');
            $leave_type_ids = $request->query('leave_type_ids')
                ? array_map('intval', preg_split('/\s*,\s*/', $request->query('leave_type_ids')))
                : [];

            $date_from = $request->query('date_from');
            $date_to = $request->query('date_to');
            $sort_by = $request->query('sort_by');
            $limit = $request->query('limit');

            // variable declaration for report generation
            $summary = collect();
            $report_name = 'Leave Report';
            $is_print = $request->query('is_print');
            $columns = $this->getReportColumns($report_format);
            $orientation = 'landscape';
            $filters = [
                "sector" => $sector,
                "report_format" => $report_format,
                "status" => $status,
                "area_under" => $area_under,
                "area_id" => $area_id,
                "leave_types" =>  $this->getFilteredLeaveTypes($leave_type_ids),
                "date_from" => $date_from,
                "date_to" => $date_to,
                "sort_by" => $sort_by,
                "limit" => $limit
            ];


            // Determine report format and fetch data accordingly
            switch ($report_format) {
                case 'area':
                    $results = $this->getAreaFilter($sector, $status, $area_under, $area_id, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                    break;
                case 'employee':
                    $results = $this->getEmployeeFilter($sector, $status, $area_under, $area_id, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                    break;
                default:
                    return response()->json(
                        [
                            'count' => count($results),
                            'data' => $results,
                            'message' => 'Invalid report format'
                        ],
                        Response::HTTP_OK
                    );
            }


            $summary = $this->generateLeaveReportSummary($results);
            // return $summary;
            if ($is_print) {
                return Helpers::generateLeavePdf($results, $columns, $report_name, $orientation, $summary, $filters);
            }

            return response()->json([
                'summary' => $summary,
                'count' => count($results),
                'data' => $results,
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
            case 'employee':
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
        if (empty($area_under) && empty($sector) && empty($area_id)) {
            return $this->getAreasWithLeaveApplicationsOnly($status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
        }

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

        // Ensure sort_by is in the correct format
        $sort_field = 'leave_count';

        if (strpos($sort_by, ':') !== false) {
            list($sort_field, $sort_by) = explode(':', $sort_by);
        }

        // Sort areas based on the sort_by variable
        $this->sortAreas($areas, $sort_field, $sort_by);

        // Check if limit is defined and is a positive integer
        if (isset($limit) && is_numeric($limit) && (int)$limit > 0) {
            $areas = array_slice($areas, 0, (int)$limit);
        }


        return $areas;
    }

    /**
     * Get areas with leave applications only.
     *
     * @param string|null $status
     * @param array $leave_type_ids
     * @param string|null $date_from
     * @param string|null $date_to
     * @param string|null $sort_by
     * @param int|null $limit
     * @return array
     */
    private function getAreasWithLeaveApplicationsOnly($status = null, $leave_type_ids = [], $date_from = null, $date_to = null, $sort_by = null, $limit = null)
    {
        $areas = AssignArea::whereHas('employeeProfile.leaveApplications', function ($query) use ($status, $leave_type_ids, $date_from, $date_to) {
            // Apply filters to the leave applications
            if (!empty($leave_type_ids)) {
                $query->whereIn('leave_type_id', $leave_type_ids);
            }
            if (!empty($status)) {
                $query->where('status', 'LIKE', '%' . $status . '%');
            }
            if (!empty($date_from)) {
                $query->where('date_from', '>=', $date_from);
            }
            if (!empty($date_to)) {
                $query->where('date_to', '<=', $date_to);
            }
        })->with(['division', 'department', 'section', 'unit'])->get();

        $result = [];
        $unique_areas = [];

        foreach ($areas as $area) {
            $details = $area->findDetails();
            $area_key = $details['details']->id . '-' . $details['sector'];
            if (!in_array($area_key, $unique_areas)) {
                $result[] = $this->result($details['details'], strtolower($details['sector']), $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                $unique_areas[] = $area_key;
            }
        }

        // Ensure sort_by is in the correct format
        $sort_field = 'leave_count';
        if (strpos($sort_by, ':') !== false) {
            list($sort_field, $sort_by) = explode(':', $sort_by);
        }

        // Sort areas based on the sort_by variable
        $this->sortAreas($result, $sort_field, $sort_by);

        // Check if limit is defined and is a positive integer
        if (isset($limit) && is_numeric($limit) && (int)$limit > 0) {
            $areas = array_slice($areas, 0, (int)$limit);
        }

        return $result;
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
        if (empty($area_under) && empty($sector) && empty($area_id)) {
            return $this->getEmployeesWithLeaveApplicationsOnly($status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
        }

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

        // Ensure sort_by is in the correct format
        $sort_field = 'leave_count';

        if (strpos($sort_by, ':') !== false) {
            list($sort_field, $sort_by) = explode(':', $sort_by);
        }

        // Sort employees based on the sort_by variable
        $this->sortEmployees($employees, $sort_field, $sort_by);

        // Apply limit if provided and it is a valid integer
        if (isset($limit) && is_numeric($limit) && (int)$limit > 0) {
            $employees = array_slice($employees, 0, (int)$limit);
        }


        return $employees;
    }


    /**
     * Get employees with leave applications only.
     *
     * @param string|null $status
     * @param array $leave_type_ids
     * @param string|null $date_from
     * @param string|null $date_to
     * @param string|null $sort_by
     * @param int|null $limit
     * @return array
     */
    private function getEmployeesWithLeaveApplicationsOnly($status = null, $leave_type_ids = [], $date_from = null, $date_to = null, $sort_by = null, $limit = null)
    {
        $employees = EmployeeProfile::whereHas('leaveApplications', function ($query) use ($status, $leave_type_ids, $date_from, $date_to) {
            // Apply filters to the leave applications
            if (!empty($leave_type_ids)) {
                $query->whereIn('leave_type_id', $leave_type_ids);
            }
            if (!empty($status)) {
                $query->where('status', 'LIKE', '%' . $status . '%');
            }
            if (!empty($date_from)) {
                $query->where('date_from', '>=', $date_from);
            }
            if (!empty($date_to)) {
                $query->where('date_to', '<=', $date_to);
            }
        })->get();

        $result = [];
        foreach ($employees as $employee) {
            $result[] = $this->resultEmployee($employee, '', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
        }

        // Ensure sort_by is in the correct format
        $sort_field = 'leave_count';

        if (strpos($sort_by, ':') !== false) {
            list($sort_field, $sort_by) = explode(':', $sort_by);
        }

        // Sort employees based on the sort_by variable
        $this->sortEmployees($result, $sort_field, $sort_by);

        // Apply limit if provided
        if (!empty($limit)) {
            $result = array_slice($result, 0, $limit);
        }

        return $result;
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
            $result = $this->result($division, 'division', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
            if ($result) {
                $areas[] = $result;
            }

            if ($area_under === 'all') {
                $departments = Department::where('division_id', $division_id)->get();
                foreach ($departments as $department) {
                    $result = $this->result($department, 'department', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                    if ($result) {
                        $areas[] = $result;
                    }
                    $sections = Section::where('department_id', $department->id)->get();
                    foreach ($sections as $section) {
                        $result = $this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                        if ($result) {
                            $areas[] = $result;
                        }
                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $result = $this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                            if ($result) {
                                $areas[] = $result;
                            }
                        }
                    }
                }

                $sections = Section::where('division_id', $division_id)->whereNull('department_id')->get();
                foreach ($sections as $section) {
                    $result = $this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                    if ($result) {
                        $areas[] = $result;
                    }
                    $units = Unit::where('section_id', $section->id)->get();
                    foreach ($units as $unit) {
                        $result = $this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                        if ($result) {
                            $areas[] = $result;
                        }
                    }
                }
            } elseif ($area_under === 'staff') {
                $result = $this->result($division, 'division', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                if ($result) {
                    $areas[] = $result;
                }
            }
        }

        if (!empty($area_under) && empty($division_id)) {
            $divisions = Division::all();
            foreach ($divisions as $division) {
                $result = $this->result($division, 'division', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                if ($result) {
                    $areas[] = $result;
                }
                if ($area_under === 'all') {
                    $departments = Department::where('division_id', $division->id)->get();
                    foreach ($departments as $department) {
                        $result = $this->result($department, 'department', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                        if ($result) {
                            $areas[] = $result;
                        }
                        $sections = Section::where('department_id', $department->id)->get();
                        foreach ($sections as $section) {
                            $result = $this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                            if ($result) {
                                $areas[] = $result;
                            }
                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $result = $this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                                if ($result) {
                                    $areas[] = $result;
                                }
                            }
                        }
                    }
                    $sections = Section::where('division_id', $division->id)->whereNull('department_id')->get();
                    foreach ($sections as $section) {
                        $result = $this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                        if ($result) {
                            $areas[] = $result;
                        }
                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $result = $this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                            if ($result) {
                                $areas[] = $result;
                            }
                        }
                    }
                } elseif ($area_under === 'staff') {
                    $result = $this->result($division, 'division', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                    if ($result) {
                        $areas[] = $result;
                    }
                }
            }
        }

        // Filter out null values
        return array_filter($areas);
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
            $result = $this->result($department, 'department', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
            if ($result) {
                $areas[] = $result;
            }

            if ($area_under === 'all') {
                $sections = Section::where('department_id', $department->id)->get();
                foreach ($sections as $section) {
                    $result = $this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                    if ($result) {
                        $areas[] = $result;
                    }
                    $units = Unit::where('section_id', $section->id)->get();
                    foreach ($units as $unit) {
                        $result = $this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                        if ($result) {
                            $areas[] = $result;
                        }
                    }
                }
            } elseif ($area_under === 'staff') {
                $result = $this->result($department, 'department', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                if ($result) {
                    $areas[] = $result;
                }
            }
        }

        if (!empty($area_under) && empty($department_id)) {
            $departments = Department::all();
            foreach ($departments as $department) {
                $result = $this->result($department, 'department', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                if ($result) {
                    $areas[] = $result;
                }

                if ($area_under === 'all') {
                    $sections = Section::where('department_id', $department->id)->get();
                    foreach ($sections as $section) {
                        $result = $this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                        if ($result) {
                            $areas[] = $result;
                        }
                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $result = $this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                            if ($result) {
                                $areas[] = $result;
                            }
                        }
                    }
                } elseif ($area_under === 'staff') {
                    $result = $this->result($department, 'department', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                    if ($result) {
                        $areas[] = $result;
                    }
                }
            }
        }

        // Filter out null values
        return array_filter($areas);
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
            $result = $this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
            if ($result) {
                $areas[] = $result;
            }

            if ($area_under === 'all') {
                $units = Unit::where('section_id', $section->id)->get();
                foreach ($units as $unit) {
                    $result = $this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                    if ($result) {
                        $areas[] = $result;
                    }
                }
            } elseif ($area_under === 'staff') {
                $result = $this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                if ($result) {
                    $areas[] = $result;
                }
            }
        }

        if (!empty($area_under) && empty($section_id)) {
            $sections = Section::all();
            foreach ($sections as $section) {
                $result = $this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                if ($result) {
                    $areas[] = $result;
                }

                if ($area_under === 'all') {
                    $units = Unit::where('section_id', $section->id)->get();
                    foreach ($units as $unit) {
                        $result = $this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                        if ($result) {
                            $areas[] = $result;
                        }
                    }
                } elseif ($area_under === 'staff') {
                    $result = $this->result($section, 'section', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                    if ($result) {
                        $areas[] = $result;
                    }
                }
            }
        }

        // Filter out null values
        return array_filter($areas);
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
            $result = $this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
            if ($result) {
                $areas[] = $result;
            }
        }

        if (!empty($area_under) && empty($unit_id)) {
            $units = Unit::all();
            foreach ($units as $unit) {
                $result = $this->result($unit, 'unit', $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                if ($result) {
                    $areas[] = $result;
                }
            }
        }

        // Filter out null values
        return array_filter($areas);
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

        if (!empty($area_under) && !empty($division_id)) {
            if ($area_under === 'all') {
                // $division = Division::find($division_id);
                $assignAreas = AssignArea::with(['employeeProfile', 'division', 'department', 'section', 'unit'])
                    ->where('division_id', $division_id)
                    ->where('employee_profile_id', '<>', 1) // Use where clause with '<>' for not equal
                    ->whereHas('employeeProfile.leaveApplications', function ($q) use ($leave_type_ids) {
                        if (!empty($leave_type_ids)) {
                            $q->whereIn('leave_type_id', $leave_type_ids);
                        }
                    })
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
                        ->whereHas('employeeProfile.leaveApplications', function ($q) use ($leave_type_ids) {
                            if (!empty($leave_type_ids)) {
                                $q->whereIn('leave_type_id', $leave_type_ids);
                            }
                        })
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
                            ->whereHas('employeeProfile.leaveApplications', function ($q) use ($leave_type_ids) {
                                if (!empty($leave_type_ids)) {
                                    $q->whereIn('leave_type_id', $leave_type_ids);
                                }
                            })
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
                                ->whereHas('employeeProfile.leaveApplications', function ($q) use ($leave_type_ids) {
                                    if (!empty($leave_type_ids)) {
                                        $q->whereIn('leave_type_id', $leave_type_ids);
                                    }
                                })
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
                        ->whereHas('employeeProfile.leaveApplications', function ($q) use ($leave_type_ids) {
                            if (!empty($leave_type_ids)) {
                                $q->whereIn('leave_type_id', $leave_type_ids);
                            }
                        })
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
                            ->whereHas('employeeProfile.leaveApplications', function ($q) use ($leave_type_ids) {
                                if (!empty($leave_type_ids)) {
                                    $q->whereIn('leave_type_id', $leave_type_ids);
                                }
                            })
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
                    ->whereHas('employeeProfile.leaveApplications', function ($q) use ($leave_type_ids) {
                        if (!empty($leave_type_ids)) {
                            $q->whereIn('leave_type_id', $leave_type_ids);
                        }
                    })
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
                    ->where('employee_profile_id', '<>', 1) // Use where clause with '<>' for not equal
                    ->whereHas('employeeProfile.leaveApplications', function ($q) use ($leave_type_ids) {
                        if (!empty($leave_type_ids)) {
                            $q->whereIn('leave_type_id', $leave_type_ids);
                        }
                    })
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
                        ->whereHas('employeeProfile.leaveApplications', function ($q) use ($leave_type_ids) {
                            if (!empty($leave_type_ids)) {
                                $q->whereIn('leave_type_id', $leave_type_ids);
                            }
                        })
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
                            ->whereHas('employeeProfile.leaveApplications', function ($q) use ($leave_type_ids) {
                                if (!empty($leave_type_ids)) {
                                    $q->whereIn('leave_type_id', $leave_type_ids);
                                }
                            })
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
                    ->whereHas('employeeProfile.leaveApplications', function ($q) use ($leave_type_ids) {
                        if (!empty($leave_type_ids)) {
                            $q->whereIn('leave_type_id', $leave_type_ids);
                        }
                    })
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
                    ->where('employee_profile_id', '!=', 1) // Use where clause with '<>' for not equal
                    ->whereHas('employeeProfile.leaveApplications', function ($q) use ($leave_type_ids) {
                        if (!empty($leave_type_ids)) {
                            $q->whereIn('leave_type_id', $leave_type_ids);
                        }
                    })
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
                        ->whereHas('employeeProfile.leaveApplications', function ($q) use ($leave_type_ids) {
                            if (!empty($leave_type_ids)) {
                                $q->whereIn('leave_type_id', $leave_type_ids);
                            }
                        })
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
                    ->whereHas('employeeProfile.leaveApplications', function ($q) use ($leave_type_ids) {
                        if (!empty($leave_type_ids)) {
                            $q->whereIn('leave_type_id', $leave_type_ids);
                        }
                    })
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
                ->where('employee_profile_id', '<>', 1) // Use where clause with '<>' for not equal
                ->whereHas('employeeProfile.leaveApplications', function ($q) use ($leave_type_ids) {
                    if (!empty($leave_type_ids)) {
                        $q->whereIn('leave_type_id', $leave_type_ids);
                    }
                })
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
     * @return array|null
     */
    private function result($area, $sector, $status, $leave_type_ids = [], $date_from, $date_to, $sort_by, $limit)
    {
        // Check if $area is null or does not have the expected properties
        if (is_null($area) || !isset($area->id) || !isset($area->name) || !isset($area->code)) {
            return null;
        }

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

        // Apply date filters if provided
        if (!empty($date_from)) {
            $leave_applications->where('date_from', '>=', $date_from);
        }

        if (!empty($date_to)) {
            $leave_applications->where('date_to', '<=', $date_to);
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

        // If there are no leave applications, return null


        $leave_types_data = [];

        // Initialize specific leave counts
        $leave_count_total_received = 0;
        $leave_count_total_cancelled = 0;
        $leave_count_total_approved = 0;
        $leave_count_total_applied = 0;

        // Only calculate specific leave counts if status is not empty
        if (!empty($status)) {
            // Apply status filter
            $leave_applications->where('status', $status);
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

            // Count specific leave statuses
            if ($application->status == 'received') {
                $leave_count_total_received++;
            } elseif (stripos($application->status, 'cancelled') !== false) {
                $leave_count_total_cancelled++;
            } elseif ($application->status == 'approved') {
                $leave_count_total_approved++;
            } elseif ($application->status == 'applied') {
                $leave_count_total_applied++;
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

        // Sort leave types by ID
        ksort($leave_types_data);

        // Update area data with aggregated leave counts and leave types
        $area_data['leave_count'] = $leave_count_total;
        $area_data['leave_count_with_pay'] = $leave_count_with_pay_total;
        $area_data['leave_count_without_pay'] = $leave_count_without_pay_total;
        $area_data['leave_types'] = array_values($leave_types_data);

        // Only update specific leave counts if status is not empty
        $area_data['leave_count_received'] = $leave_count_total_received;
        $area_data['leave_count_cancelled'] = $leave_count_total_cancelled;
        $area_data['leave_count_approved'] = $leave_count_total_approved;
        $area_data['leave_count_applied'] = $leave_count_total_applied;
        // return $area_data;
        return $leave_count_total > 0 ? $area_data : [];
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
            'employee_id' => $employee->employee_id,
            'employee_name' => $employee->personalInformation->employeeName(),
            'personal_information_id' => $employee->personal_information_id,
            'designation' => $employee->findDesignation()['name'],
            'designation_code' => $employee->findDesignation()['code'],
            'leave_count' => 0, // Initialize leave count
            'leave_count_with_pay' => 0,
            'leave_count_without_pay' => 0,
            'sector' => ucfirst($sector),
            'area_name' => $employee->assignedArea->findDetails()['details']['name'],
            'area_code' => $employee->assignedArea->findDetails()['details']['code'],
            'leave_types' => [] // Initialize leave types array
        ];

        // Initialize additional leave count fields only if status is not empty
        $leave_count_total_received = 0;
        $leave_count_total_cancelled = 0;
        $leave_count_total_approved = 0;
        $leave_count_total_applied = 0;

        // Build the leave applications query with necessary relationships and filters
        $leave_applications = LeaveApplication::where('employee_profile_id', $employee->id);



        // Filter by leave type ids if provided
        if (!empty($leave_type_ids)) {
            $leave_applications->whereIn('leave_type_id', $leave_type_ids);
        }

        // Apply status filter if provided
        if (!empty($status)) {
            $leave_applications->where('status', 'LIKE', '%' .  $status . '%');
        }

        // Apply date filters if provided
        if (!empty($date_from)) {
            $leave_applications->where('date_from', '>=', $date_from);
        }

        if (!empty($date_to)) {
            $leave_applications->where('date_to', '<=', $date_to);
        }

        // Apply limit if provided
        if (!empty($limit)) {
            $leave_applications->limit($limit);
        }

        // Get the leave applications
        $leave_applications = $leave_applications->get();

        // Process results to count leaves and aggregate leave types data
        $leave_count_total = $leave_applications->count();
        $leave_count_with_pay_total = $leave_applications->where('without_pay', 0)->count();
        $leave_count_without_pay_total = $leave_applications->where('without_pay', 1)->count();
        $leave_types_data = [];

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

            // Count specific leave statuses
            if ($application->status == 'received') {
                $leave_count_total_received++;
            } elseif (stripos($application->status, 'cancelled') !== false) {
                $leave_count_total_cancelled++;
            } elseif ($application->status == 'approved') {
                $leave_count_total_approved++;
            } elseif ($application->status == 'applied') {
                $leave_count_total_applied++;
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
        // Sort leave types by ID
        ksort($leave_types_data);
        // Update employee data with aggregated leave counts and leave types
        $employee_data['leave_count'] = $leave_count_total;
        $employee_data['leave_count_with_pay'] = $leave_count_with_pay_total;
        $employee_data['leave_count_without_pay'] = $leave_count_without_pay_total;
        $employee_data['leave_types'] = array_values($leave_types_data);

        // Only update specific leave counts if status is not empty
        $employee_data['leave_count_received'] = $leave_count_total_received;
        $employee_data['leave_count_cancelled'] = $leave_count_total_cancelled;
        $employee_data['leave_count_approved'] = $leave_count_total_approved;
        $employee_data['leave_count_applied'] = $leave_count_total_applied;

        return $employee_data;
    }


    /**
     * Sorts an array of areas based on a specified field and order.
     *
     * @param array $areas The array of areas to be sorted.
     * @param string $sort_by The field to sort by (e.g., 'leave_count').
     * @param string $order The order to sort by ('asc' or 'desc').
     * @return void
     */
    private function sortAreas(&$areas, $sort_by, $order)
    {
        usort($areas, function ($a, $b) use ($sort_by, $order) {
            if ($order === 'asc') {
                return $a[$sort_by] <=> $b[$sort_by];
            }
            return $b[$sort_by] <=> $a[$sort_by];
        });
    }

    /**
     * Sorts an array of employees based on a specified field and order.
     *
     * @param array $employees The array of employees to be sorted.
     * @param string $sort_by The field to sort by (e.g., 'leave_count').
     * @param string $order The order to sort by ('asc' or 'desc').
     * @return void
     */
    private function sortEmployees(&$employees, $sort_by, $order)
    {
        usort($employees, function ($a, $b) use ($sort_by, $order) {
            if ($order === 'asc') {
                return $a[$sort_by] <=> $b[$sort_by];
            }
            return $b[$sort_by] <=> $a[$sort_by];
        });
    }
}
