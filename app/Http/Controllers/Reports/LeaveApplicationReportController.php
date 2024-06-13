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
            $leaveTypeId = $request->input('leave_type_id');

            $leave_applications = LeaveApplication::where('leave_type_id', $leaveTypeId)->get();

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

    // New function to generate leave application report
    public function test(Request $request)
    {
        try {
            $status = $request->input('status');
            $leaveTypeId = $request->input('leave_type_id');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $areaId = $request->input('area_id');
            $childAreaId = $request->input('child_area_id');

            $query = LeaveApplication::query();

            if ($status) {
                $query->where('status', $status);
            }

            if ($leaveTypeId) {
                $query->where('leave_type_id', $leaveTypeId);
            }

            if ($dateFrom && $dateTo) {
                $query->whereBetween('date_from', [$dateFrom, $dateTo]);
            }

            if ($areaId) {
                // Filter by area_id, assuming it is related to assigned_areas
                $query->whereHas('employeeProfile.assignedAreas', function ($q) use ($areaId) {
                    $q->where('division_id', $areaId)
                        ->orWhere('department_id', $areaId)
                        ->orWhere('section_id', $areaId)
                        ->orWhere('unit_id', $areaId);
                });
            }

            if ($childAreaId) {
                // Filter by child_area_id
                $query->whereHas('employeeProfile.assignedAreas', function ($q) use ($childAreaId) {
                    $q->where('division_id', $childAreaId)
                        ->orWhere('department_id', $childAreaId)
                        ->orWhere('section_id', $childAreaId)
                        ->orWhere('unit_id', $childAreaId);
                });
            }

            $leaveApplications = $query->get();

            return response()->json([
                'count' => count($leaveApplications),
                'data' => $leaveApplications
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'generateReport', $e->getMessage());
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
