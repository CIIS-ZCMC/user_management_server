<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // Add this import
use Illuminate\Http\Response;
use App\Models\Department;
use App\Models\Division;
use App\Models\Section;
use App\Models\Unit;
use App\Helpers\Helpers;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\AssignArea;
use Illuminate\Support\Facades\DB; // Import DB facade


class LeaveApplicationReportController extends Controller
{
    private $CONTROLLER_NAME = "Leave Management Reports";

    // Function to format nested areas and return only the children
    private function formatNestedAreas($areas)
    {
        $children = [];

        foreach ($areas as $area) {
            // Add departments if available
            if ($area->relationLoaded('departments')) {
                foreach ($area->departments as $department) {
                    $formattedDepartment = [
                        'id' => $department->id,
                        'name' => $department->name,
                        'code' => $department->code,
                        'sector' => 'Department',
                        'children' => []
                    ];

                    // Add sections if available
                    if ($department->relationLoaded('sections')) {
                        foreach ($department->sections as $section) {
                            $formattedSection = [
                                'id' => $section->id,
                                'name' => $section->name,
                                'code' => $section->code,
                                'sector' => 'Section',
                                'children' => []
                            ];

                            // Add units if available
                            if ($section->relationLoaded('units')) {
                                foreach ($section->units as $unit) {
                                    $formattedUnit = [
                                        'id' => $unit->id,
                                        'name' => $unit->name,
                                        'code' => $unit->code,
                                        'sector' => 'Unit'
                                    ];
                                    $formattedSection['children'][] = $formattedUnit;
                                }
                            }

                            $formattedDepartment['children'][] = $formattedSection;
                        }
                    }

                    $children[] = $formattedDepartment;
                }
            }

            // Add sections directly under division if available
            if ($area->relationLoaded('sections')) {
                foreach ($area->sections as $section) {
                    $formattedSection = [
                        'id' => $section->id,
                        'name' => $section->name,
                        'code' => $section->code,
                        'sector' => 'Section',
                        'children' => []
                    ];

                    // Add units if available
                    if ($section->relationLoaded('units')) {
                        foreach ($section->units as $unit) {
                            $formattedUnit = [
                                'id' => $unit->id,
                                'name' => $unit->name,
                                'code' => $unit->code,
                                'sector' => 'Unit'
                            ];
                            $formattedSection['children'][] = $formattedUnit;
                        }
                    }

                    $children[] = $formattedSection;
                }
            }

            // Add units directly under division if available
            if ($area->relationLoaded('units')) {
                foreach ($area->units as $unit) {
                    $formattedUnit = [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'code' => $unit->code,
                        'sector' => 'Unit'
                    ];
                    $children[] = $formattedUnit;
                }
            }
        }

        return $children;
    }

    public function selectArea(Request $request)
    {
        try {
            $sector = $request->sector;
            $area_id = $request->area_id;

            // Fetch specific area and its nested areas based on user's assigned area
            switch ($sector) {
                case "Division":
                case "division":
                    $areas = Division::where('id', $area_id)
                        ->with(['departments.sections.units', 'sections.units'])
                        ->get();
                    break;
                case "Department":
                case "department":
                    $areas = Department::where('id', $area_id)
                        ->with('sections.units')
                        ->get();
                    break;
                case "Section":
                case "section":
                    $areas = Section::where('id', $area_id)
                        ->with('units')
                        ->get();
                    break;
                case "Unit":
                case "unit":
                    $areas = Unit::where('id', $area_id)
                        ->get();
                    break;
                default:
                    // Handle default case or error scenario
                    return response()->json(['message' => 'Invalid user area'], Response::HTTP_BAD_REQUEST);
            }

            // Log the retrieved areas for debugging
            Log::info('Retrieved Areas: ', $areas->toArray());

            $nested_area = $this->formatNestedAreas($areas);

            return response()->json([
                'data' => $nested_area,
                'message' => 'Successfully retrieved nested areas based on user\'s role.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'test', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approvalStatus(Request $request)
    {
        try {
            $status = $request->input('status'); // 'approved', 'declined', 'cancelled'

            $leave_applications = LeaveApplication::where('status', $status)->get();

            return response()->json([
                'count' => COUNT($leave_applications),
                'data' => $leave_applications
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'approvalStatus', $e->getMessage());
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function leaveType(Request $request)
    {
        try {
            $leave_type_id = $request->input('leave_type_id');

            $leave_applications = LeaveApplication::where('leave_type_id', $leave_type_id)->get();

            return response()->json([
                'count' => COUNT($leave_applications),
                'data' => $leave_applications
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'leaveType', $e->getMessage());
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function dateRange(Request $request)
    {
        try {
            $date_from = $request->input('date_from');
            $date_to = $request->input('date_to');

            $leave_applications = LeaveApplication::whereBetween('date_from', [$date_from, $date_to])->get();

            return response()->json([
                'count' => count($leave_applications),
                'data' => $leave_applications
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'dateRange', $e->getMessage());
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /* ------------------------------------------------------------------------------------------------------------------------------------------------------------------ */

    public function filterLeaveApplication(Request $request)
    {
        try {
            // Retrieve request parameters
            $report_format = $request->report_format;
            $sector = $request->sector;
            $area_id = $request->area_id;
            $area_under = $request->area_under;
            $leave_type_id = $request->leave_type_id;
            $status = $request->status;
            $date_from = $request->date_from;
            $date_to = $request->date_to;
            $sort_order = $request->sort_by;

            $areas = [];
            $leaveType = LeaveType::find($leave_type_id);

            switch ($report_format) {
                case 'area':
                case 'Area':
                    // Filter leave applications by area type
                    switch (strtolower($sector)) {
                        case 'division':
                            $areas = $this->getLeaveApplicationsByDivision($area_id, $leave_type_id, $area_under, $status, $date_from, $date_to, $sort_order, $leaveType);
                            break;
                        case 'department':
                            $areas = $this->getLeaveApplicationsByDepartment($area_id, $leave_type_id, $area_under, $status, $date_from, $date_to, $sort_order, $leaveType);
                            break;
                        case 'section':
                            $areas = $this->getLeaveApplicationsBySection($area_id, $leave_type_id, $area_under, $status, $date_from, $date_to, $sort_order, $leaveType);
                            break;
                        case 'unit':
                            $areas = $this->getLeaveApplicationsByUnit($area_id, $leave_type_id, $status, $date_from, $date_to, $sort_order, $leaveType);
                            break;
                        default:
                            return response()->json(['message' => 'Invalid area type'], Response::HTTP_BAD_REQUEST);
                    }
                    break;
                case 'employee':
                case 'Employee':
                    // Get leave applications by employee
                    $areas = $this->getLeaveApplicationsByEmployee($status, $leave_type_id, $date_from, $date_to, $sort_order);
                    break;
                default:
                    return response()->json(['areas' => 'Invalid report format']);
            }


            return response()->json(['data' => $areas]);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'generateLeaveReport', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // GOOD
    private function getLeaveApplicationsByDivision($divisionId, $leaveTypeId, $area_under, $status, $dateFrom, $dateTo, $sortOrder, $leaveType)
    {
        $areas = [];
        $division = Division::find($divisionId);
        if ($division) {
            // Count leaves directly under the division
            $leaveCount = $this->getLeaveCount($leaveTypeId, 'division_id', $division->id, $status, $dateFrom, $dateTo);
            $areas[] = $this->formatAreaData($division->id, 'Division', $division->name, $leaveCount, $leaveType);

            if ($area_under === 'all' || $area_under === 'All') {
                // Get departments directly under the division
                $departments = Department::where('division_id', $divisionId)->get();

                foreach ($departments as $department) {
                    $leaveCount = $this->getLeaveCount($leaveTypeId, 'department_id', $department->id, $status, $dateFrom, $dateTo);
                    $areas[] = $this->formatAreaData($department->id, 'Department', $department->name, $leaveCount, $leaveType);

                    // Get sections directly under the department
                    $sections = Section::where('department_id', $department->id)->get();
                    foreach ($sections as $section) {
                        $leaveCount = $this->getLeaveCount($leaveTypeId, 'section_id', $section->id, $status, $dateFrom, $dateTo);
                        $areas[] = $this->formatAreaData($section->id, 'Section', $section->name, $leaveCount, $leaveType);

                        // Get all units directly under the section
                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $leaveCount = $this->getLeaveCount($leaveTypeId, 'unit_id', $unit->id, $status, $dateFrom, $dateTo);
                            $areas[] = $this->formatAreaData($unit->id, 'Unit', $unit->name, $leaveCount, $leaveType);
                        }
                    }
                }

                // Get sections directly under the division (if any) that are not under any department
                $sections = Section::where('division_id', $divisionId)->whereNull('department_id')->get();
                foreach ($sections as $section) {
                    $leaveCount = $this->getLeaveCount($leaveTypeId, 'section_id', $section->id, $status, $dateFrom, $dateTo);
                    $areas[] = $this->formatAreaData($section->id, 'Section', $section->name, $leaveCount, $leaveType);

                    // Get all units directly under the section
                    $units = Unit::where('section_id', $section->id)->get();
                    foreach ($units as $unit) {
                        $leaveCount = $this->getLeaveCount($leaveTypeId, 'unit_id', $unit->id, $status, $dateFrom, $dateTo);
                        $areas[] = $this->formatAreaData($unit->id, 'Unit', $unit->name, $leaveCount, $leaveType);
                    }
                }
            }
        }

        usort($areas, fn ($a, $b) => $sortOrder === 'highest' ? $b['leave_count'] <=> $a['leave_count'] : $a['leave_count'] <=> $b['leave_count']);
        return $areas;
    }

    // GOOD
    private function getLeaveApplicationsByDepartment($departmentId, $leave_type_id, $area_under, $status, $date_from, $date_to, $sort_order, $leaveType)
    {
        $areas = [];
        $department = Department::find($departmentId);
        if ($department) {
            $leaveCount = $this->getLeaveCount($leave_type_id, 'department_id', $department->id, $status, $date_from, $date_to);
            $areas[] = $this->formatAreaData($department->id, 'Department', $department->name, $leaveCount, $leaveType);
        }
        if ($area_under === 'all') {
            $sections = Section::where('department_id', $departmentId)->get();
            foreach ($sections as $section) {
                $leaveCount = $this->getLeaveCount($leave_type_id, 'section_id', $section->id, $status, $date_from, $date_to);
                $areas[] = $this->formatAreaData($section->id, 'Section', $section->name, $leaveCount, $leaveType);

                $units = Unit::where('section_id', $section->id)->get();
                foreach ($units as $unit) {
                    $leaveCount = $this->getLeaveCount($leave_type_id, 'unit_id', $unit->id, $status, $date_from, $date_to);
                    $areas[] = $this->formatAreaData($unit->id, 'Unit', $unit->name, $leaveCount, $leaveType);
                }
            }
        }
        usort($areas, fn ($a, $b) => $sort_order === 'highest' ? $b['leave_count'] <=> $a['leave_count'] : $a['leave_count'] <=> $b['leave_count']);
        return $areas;
    }

    // GOod
    private function getLeaveApplicationsBySection($sectionId, $leave_type_id, $area_under, $status, $date_from, $date_to, $sort_order, $leaveType)
    {
        $areas = [];
        $section = Section::find($sectionId);
        if ($section) {
            $leaveCount = $this->getLeaveCount($leave_type_id, 'section_id', $section->id, $status, $date_from, $date_to);
            $areas[] = $this->formatAreaData($section->id, 'Section', $section->name, $leaveCount, $leaveType);
        }
        if ($area_under === 'all') {
            $units = Unit::where('section_id', $sectionId)->get();
            foreach ($units as $unit) {
                $leaveCount = $this->getLeaveCount($leave_type_id, 'unit_id', $unit->id, $status, $date_from, $date_to);
                $areas[] = $this->formatAreaData($unit->id, 'Unit', $unit->name, $leaveCount, $leaveType);
            }
        }
        usort($areas, fn ($a, $b) => $sort_order === 'highest' ? $b['leave_count'] <=> $a['leave_count'] : $a['leave_count'] <=> $b['leave_count']);
        return $areas;
    }

    // GOOD
    private function getLeaveApplicationsByUnit($unitId, $leave_type_id, $status, $date_from, $date_to, $sort_order, $leaveType)
    {
        $areas = [];
        $unit = Unit::find($unitId);
        if ($unit) {
            $leaveCount = $this->getLeaveCount($leave_type_id, 'id', $unit->id, $status, $date_from, $date_to);
            $areas[] = $this->formatAreaData($unit->id, 'Unit', $unit->name, $leaveCount, $leaveType);
        }
        usort($areas, fn ($a, $b) => $sort_order === 'highest' ? $b['leave_count'] <=> $a['leave_count'] : $a['leave_count'] <=> $b['leave_count']);
        return $areas;
    }

    private function getLeaveApplicationsByEmployee($status, $leave_type_id, $date_from, $date_to, $sort_order)
    {
        // Retrieve leave applications with the specified filters
        $leaveApplications = LeaveApplication::where(function ($query) use ($status, $leave_type_id, $date_from, $date_to) {
            $query->where('status', $status)
                ->orWhere('leave_type_id', $leave_type_id)
                ->orWhereBetween('date_from', [$date_from, $date_to]);
        })->get();

        $applications = [];

        // Iterate over the retrieved leave applications
        foreach ($leaveApplications as $leaveApplication) {
            $employeeProfile = $leaveApplication->employeeProfile;
            if ($employeeProfile) {
                $personalInfo = $employeeProfile->personalInformation;
                $assignedAreas = $employeeProfile->assignedAreas;
                $employeeAreas = [];

                // Gather assigned areas
                foreach ($assignedAreas as $assignedArea) {
                    if ($assignedArea->division) {
                        $employeeAreas[] = ['id' => $assignedArea->division->id, 'name' => $assignedArea->division->name, 'sector' => 'Division'];
                    }
                    if ($assignedArea->department) {
                        $employeeAreas[] = ['id' => $assignedArea->department->id, 'name' => $assignedArea->department->name, 'sector' => 'Department'];
                    }
                    if ($assignedArea->section) {
                        $employeeAreas[] = ['id' => $assignedArea->section->id, 'name' => $assignedArea->section->name, 'sector' => 'Section'];
                    }
                    if ($assignedArea->unit) {
                        $employeeAreas[] = ['id' => $assignedArea->unit->id, 'name' => $assignedArea->unit->name, 'sector' => 'Unit'];
                    }
                }

                // Structure the application data
                $applications[] = [
                    'leave_application_id' => $leaveApplication->id,
                    'leave_type_name' => $leaveApplication->leaveType->name,
                    'leave_type_code' => $leaveApplication->leaveType->code,
                    'date_from' => $leaveApplication->date_from,
                    'date_to' => $leaveApplication->date_to,
                    'status' => $leaveApplication->status,
                    'employee_areas' => $employeeAreas,
                    'employee' => [
                        'id' => $employeeProfile->id,
                        'employee_id' => $employeeProfile->employee_id,
                        'first_name' => $personalInfo->first_name,
                        'middle_name' => $personalInfo->middle_name,
                        'last_name' => $personalInfo->last_name,
                        'name_extension' => $personalInfo->name_extension,
                        'sex' => $personalInfo->sex,
                        'date_of_birth' => $personalInfo->date_of_birth,
                        'place_of_birth' => $personalInfo->place_of_birth,
                        'civil_status' => $personalInfo->civil_status,
                        'citizenship' => $personalInfo->citizenship,
                    ]
                ];
            }
        }

        // Sort applications if necessary
        usort($applications, fn ($a, $b) => $sort_order === 'highest' ? $b['leave_application_id'] <=> $a['leave_application_id'] : $a['leave_application_id'] <=> $b['leave_application_id']);

        return $applications;
    }



    private function getLeaveCount($leave_type_id, $areaColumn, $area_id, $status, $date_from, $date_to)
    {
        return LeaveApplication::where('leave_type_id', $leave_type_id)
            ->whereHas('employeeProfile', function ($query) use ($areaColumn, $area_id) {
                $query->whereHas('assignedAreas', function ($q) use ($areaColumn, $area_id) {
                    $q->where($areaColumn, $area_id);
                });
            })
            ->orWhere('status', $status)
            ->orWhere('date_from', [$date_from, $date_to])
            ->count();
    }


    private function formatAreaData($id, $sector, $name, $leaveCount, $leaveType)
    {
        return [
            'id' => $id . '-' . strtolower($sector),
            'name' => $name,
            'sector' => $sector,
            'leave_count' => $leaveCount,
            'leave_type_name' => $leaveType ? $leaveType->name : null,
            'leave_type_code' => $leaveType ? $leaveType->code : null
        ];
    }
}
