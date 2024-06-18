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
use App\Models\leave_type;
use App\Models\AssignArea;
use App\Models\LeaveType;
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

    public function leave_type(Request $request)
    {
        try {
            $leave_type_id = $request->input('leave_type_id');

            $leave_applications = LeaveApplication::where('leave_type_id', $leave_type_id)->get();

            return response()->json([
                'count' => COUNT($leave_applications),
                'data' => $leave_applications
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'leave_type', $e->getMessage());
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
            $leave_type_ids = $request->leave_type_ids ?? []; // Ensure leave_type_ids is an array
            $status = $request->status;
            $date_from = $request->date_from;
            $date_to = $request->date_to;
            $sort_order = $request->sort_by;

            $areas = [];

            switch ($report_format) {
                case 'area':
                case 'Area':
                    if (empty($sector)) {
                        // If sector is empty, return all areas
                        $areas = $this->getAllLeaveApplications($leave_type_ids, $status, $date_from, $date_to, $sort_order);
                    } else {
                        switch (strtolower($sector)) {
                            case 'division':
                                $areas = $this->getLeaveApplicationsByDivision($area_id, $leave_type_ids, $area_under, $status, $date_from, $date_to, $sort_order);
                                break;
                            case 'department':
                                $areas = $this->getLeaveApplicationsByDepartment($area_id, $leave_type_ids, $area_under, $status, $date_from, $date_to, $sort_order);
                                break;
                            case 'section':
                                $areas = $this->getLeaveApplicationsBySection($area_id, $leave_type_ids, $area_under, $status, $date_from, $date_to, $sort_order);
                                break;
                            case 'unit':
                                $areas = $this->getLeaveApplicationsByUnit($area_id, $leave_type_ids, $status, $date_from, $date_to, $sort_order);
                                break;
                            default:
                                return response()->json(['message' => 'Invalid area type'], Response::HTTP_BAD_REQUEST);
                        }
                    }
                    break;
                case 'employee':
                case 'Employee':
                    // Get leave applications by employee
                    $areas = $this->getLeaveApplicationsByEmployee($status, $leave_type_ids, $date_from, $date_to, $sort_order);
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

    private function getAllLeaveApplications($leave_type_ids, $status, $date_from, $date_to, $sort_order)
    {
        $areas = [];

        // Fetch all divisions
        $divisions = Division::all();
        foreach ($divisions as $division) {
            $areas = array_merge($areas, $this->getLeaveApplicationsByDivision($division->id, $leave_type_ids, 'all', $status, $date_from, $date_to, $sort_order));
        }

        usort($areas, fn ($a, $b) => $sort_order === 'highest' ? $b['total_leave_count'] <=> $a['total_leave_count'] : $a['total_leave_count'] <=> $b['total_leave_count']);
        return $areas;
    }

    private function getLeaveApplicationsByDivision($division_id, $leave_type_ids, $area_under, $status, $date_from, $date_to, $sort_order)
    {
        $areas = [];
        $division = Division::find($division_id);
        if ($division) {
            // Count leaves directly under the division
            $totalLeaveCount = $this->getLeaveCount($leave_type_ids, 'division_id', $division->id, $status, $date_from, $date_to);
            $leaveCountWithPay = $this->getLeaveCountWithPay($leave_type_ids, 'division_id', $division->id, $status, $date_from, $date_to);
            $leaveCountWithoutPay = $this->getLeaveCountWithoutPay($leave_type_ids, 'division_id', $division->id, $status, $date_from, $date_to);
            $receivedCount = $this->getReceivedCount($leave_type_ids, 'division_id', $division->id, $status, $date_from, $date_to);
            $cancelledCount = $this->getCancelledCount($leave_type_ids, 'division_id', $division->id, $status, $date_from, $date_to);
            $otherLeaveTypes = $this->getOtherLeaveTypesCounts($leave_type_ids, 'division_id', $division->id, $status, $date_from, $date_to);

            $areas[] = $this->formatAreaData($division->id, 'Division', $division->name, $totalLeaveCount, $leaveCountWithPay, $leaveCountWithoutPay, $receivedCount, $cancelledCount, $otherLeaveTypes);

            if ($area_under === 'all' || $area_under === 'All') {
                // Get departments directly under the division
                $departments = Department::where('division_id', $division_id)->get();

                foreach ($departments as $department) {
                    $totalLeaveCount = $this->getLeaveCount($leave_type_ids, 'department_id', $department->id, $status, $date_from, $date_to);
                    $leaveCountWithPay = $this->getLeaveCountWithPay($leave_type_ids, 'department_id', $department->id, $status, $date_from, $date_to);
                    $leaveCountWithoutPay = $this->getLeaveCountWithoutPay($leave_type_ids, 'department_id', $department->id, $status, $date_from, $date_to);
                    $receivedCount = $this->getReceivedCount($leave_type_ids, 'department_id', $department->id, $status, $date_from, $date_to);
                    $cancelledCount = $this->getCancelledCount($leave_type_ids, 'department_id', $department->id, $status, $date_from, $date_to);
                    $otherLeaveTypes = $this->getOtherLeaveTypesCounts($leave_type_ids, 'department_id', $department->id, $status, $date_from, $date_to);

                    $areas[] = $this->formatAreaData($department->id, 'Department', $department->name, $totalLeaveCount, $leaveCountWithPay, $leaveCountWithoutPay, $receivedCount, $cancelledCount, $otherLeaveTypes);

                    // Get sections directly under the department
                    $sections = Section::where('department_id', $department->id)->get();
                    foreach ($sections as $section) {
                        $totalLeaveCount = $this->getLeaveCount($leave_type_ids, 'section_id', $section->id, $status, $date_from, $date_to);
                        $leaveCountWithPay = $this->getLeaveCountWithPay($leave_type_ids, 'section_id', $section->id, $status, $date_from, $date_to);
                        $leaveCountWithoutPay = $this->getLeaveCountWithoutPay($leave_type_ids, 'section_id', $section->id, $status, $date_from, $date_to);
                        $receivedCount = $this->getReceivedCount($leave_type_ids, 'section_id', $section->id, $status, $date_from, $date_to);
                        $cancelledCount = $this->getCancelledCount($leave_type_ids, 'section_id', $section->id, $status, $date_from, $date_to);
                        $otherLeaveTypes = $this->getOtherLeaveTypesCounts($leave_type_ids, 'section_id', $section->id, $status, $date_from, $date_to);

                        $areas[] = $this->formatAreaData($section->id, 'Section', $section->name, $totalLeaveCount, $leaveCountWithPay, $leaveCountWithoutPay, $receivedCount, $cancelledCount, $otherLeaveTypes);

                        // Get all units directly under the section
                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $totalLeaveCount = $this->getLeaveCount($leave_type_ids, 'unit_id', $unit->id, $status, $date_from, $date_to);
                            $leaveCountWithPay = $this->getLeaveCountWithPay($leave_type_ids, 'unit_id', $unit->id, $status, $date_from, $date_to);
                            $leaveCountWithoutPay = $this->getLeaveCountWithoutPay($leave_type_ids, 'unit_id', $unit->id, $status, $date_from, $date_to);
                            $receivedCount = $this->getReceivedCount($leave_type_ids, 'unit_id', $unit->id, $status, $date_from, $date_to);
                            $cancelledCount = $this->getCancelledCount($leave_type_ids, 'unit_id', $unit->id, $status, $date_from, $date_to);
                            $otherLeaveTypes = $this->getOtherLeaveTypesCounts($leave_type_ids, 'unit_id', $unit->id, $status, $date_from, $date_to);

                            $areas[] = $this->formatAreaData($unit->id, 'Unit', $unit->name, $totalLeaveCount, $leaveCountWithPay, $leaveCountWithoutPay, $receivedCount, $cancelledCount, $otherLeaveTypes);
                        }
                    }
                }

                // Get sections directly under the division (if any) that are not under any department
                $sections = Section::where('division_id', $division_id)->whereNull('department_id')->get();
                foreach ($sections as $section) {
                    $totalLeaveCount = $this->getLeaveCount($leave_type_ids, 'section_id', $section->id, $status, $date_from, $date_to);
                    $leaveCountWithPay = $this->getLeaveCountWithPay($leave_type_ids, 'section_id', $section->id, $status, $date_from, $date_to);
                    $leaveCountWithoutPay = $this->getLeaveCountWithoutPay($leave_type_ids, 'section_id', $section->id, $status, $date_from, $date_to);
                    $receivedCount = $this->getReceivedCount($leave_type_ids, 'section_id', $section->id, $status, $date_from, $date_to);
                    $cancelledCount = $this->getCancelledCount($leave_type_ids, 'section_id', $section->id, $status, $date_from, $date_to);
                    $otherLeaveTypes = $this->getOtherLeaveTypesCounts($leave_type_ids, 'section_id', $section->id, $status, $date_from, $date_to);

                    $areas[] = $this->formatAreaData($section->id, 'Section', $section->name, $totalLeaveCount, $leaveCountWithPay, $leaveCountWithoutPay, $receivedCount, $cancelledCount, $otherLeaveTypes);

                    // Get all units directly under the section
                    $units = Unit::where('section_id', $section->id)->get();
                    foreach ($units as $unit) {
                        $totalLeaveCount = $this->getLeaveCount($leave_type_ids, 'unit_id', $unit->id, $status, $date_from, $date_to);
                        $leaveCountWithPay = $this->getLeaveCountWithPay($leave_type_ids, 'unit_id', $unit->id, $status, $date_from, $date_to);
                        $leaveCountWithoutPay = $this->getLeaveCountWithoutPay($leave_type_ids, 'unit_id', $unit->id, $status, $date_from, $date_to);
                        $receivedCount = $this->getReceivedCount($leave_type_ids, 'unit_id', $unit->id, $status, $date_from, $date_to);
                        $cancelledCount = $this->getCancelledCount($leave_type_ids, 'unit_id', $unit->id, $status, $date_from, $date_to);
                        $otherLeaveTypes = $this->getOtherLeaveTypesCounts($leave_type_ids, 'unit_id', $unit->id, $status, $date_from, $date_to);

                        $areas[] = $this->formatAreaData($unit->id, 'Unit', $unit->name, $totalLeaveCount, $leaveCountWithPay, $leaveCountWithoutPay, $receivedCount, $cancelledCount, $otherLeaveTypes);
                    }
                }
            }
        } else {
            // If division_id is empty, get all divisions
            $divisions = Division::all();
            foreach ($divisions as $division) {
                $areas = array_merge($areas, $this->getLeaveApplicationsByDivision($division->id, $leave_type_ids, $area_under, $status, $date_from, $date_to, $sort_order));
            }
        }

        usort($areas, fn ($a, $b) => $sort_order === 'highest' ? $b['total_leave_count'] <=> $a['total_leave_count'] : $a['total_leave_count'] <=> $b['total_leave_count']);
        return $areas;
    }

    // Similar updates for getLeaveApplicationsByDepartment, getLeaveApplicationsBySection, getLeaveApplicationsByUnit methods

    private function getLeaveCount($leave_type_ids, $area_column, $area_id, $status, $date_from, $date_to)
    {
        return LeaveApplication::whereIn('leave_type_id', $leave_type_ids)
            ->whereHas('employeeProfile', function ($query) use ($area_column, $area_id) {
                $query->whereHas('assignedAreas', function ($q) use ($area_column, $area_id) {
                    $q->where($area_column, $area_id);
                });
            })
            ->orWhere('status', $status)
            ->orWhereBetween('date_from', [$date_from, $date_to])
            ->count();
    }

    private function getLeaveCountWithPay($leave_type_ids, $area_column, $area_id, $status, $date_from, $date_to)
    {
        return LeaveApplication::whereIn('leave_type_id', $leave_type_ids)
            ->where('without_pay', 0) // Ensure the leave is with pay
            ->whereHas('employeeProfile', function ($query) use ($area_column, $area_id) {
                $query->whereHas('assignedAreas', function ($q) use ($area_column, $area_id) {
                    $q->where($area_column, $area_id);
                });
            })
            ->orWhere('status', $status)
            ->orWhereBetween('date_from', [$date_from, $date_to])
            ->count();
    }

    private function getLeaveCountWithoutPay($leave_type_ids, $area_column, $area_id, $status, $date_from, $date_to)
    {
        return LeaveApplication::whereIn('leave_type_id', $leave_type_ids)
            ->where('without_pay', 1) // Ensure the leave is without pay
            ->whereHas('employeeProfile', function ($query) use ($area_column, $area_id) {
                $query->whereHas('assignedAreas', function ($q) use ($area_column, $area_id) {
                    $q->where($area_column, $area_id);
                });
            })
            ->orWhere('status', $status)
            ->orWhereBetween('date_from', [$date_from, $date_to])
            ->count();
    }

    private function getReceivedCount($leave_type_ids, $area_column, $area_id, $status, $date_from, $date_to)
    {
        return LeaveApplication::whereIn('leave_type_id', $leave_type_ids)
            ->whereHas('employeeProfile', function ($query) use ($area_column, $area_id) {
                $query->whereHas('assignedAreas', function ($q) use ($area_column, $area_id) {
                    $q->where($area_column, $area_id);
                });
            })
            ->orWhere('status', 'received')
            ->orWhereBetween('date_from', [$date_from, $date_to])
            ->count();
    }

    private function getCancelledCount($leave_type_ids, $area_column, $area_id, $status, $date_from, $date_to)
    {
        return LeaveApplication::whereIn('leave_type_id', $leave_type_ids)
            ->whereHas('employeeProfile', function ($query) use ($area_column, $area_id) {
                $query->whereHas('assignedAreas', function ($q) use ($area_column, $area_id) {
                    $q->where($area_column, $area_id);
                });
            })
            ->orWhereNotNull('cancelled_at')
            ->orWhereBetween('date_from', [$date_from, $date_to])
            ->count();
    }

    private function getOtherLeaveTypesCounts($leave_type_ids, $area_column, $area_id, $status, $date_from, $date_to)
    {
        // Fetch all leave types except the current ones
        $leaveTypes = LeaveType::whereNotIn('id', $leave_type_ids)->get();

        $leaveCounts = [];

        foreach ($leaveTypes as $leaveType) {
            $count = LeaveApplication::where('leave_type_id', $leaveType->id)
                ->whereHas('employeeProfile', function ($query) use ($area_column, $area_id) {
                    $query->whereHas('assignedAreas', function ($q) use ($area_column, $area_id) {
                        $q->where($area_column, $area_id);
                    });
                })
                ->orWhere(function ($query) use ($status, $date_from, $date_to) {
                    $query->orWhere('status', $status)
                        ->orWhereBetween('date_from', [$date_from, $date_to]);
                })
                ->count();

            $leaveCounts[] = [
                'leave_type_id' => $leaveType->id,
                'leave_type_name' => $leaveType->name,
                'leave_count' => $count,
            ];
        }

        return $leaveCounts ?? []; // Ensure leaveCounts is an array
    }

    private function formatAreaData($id, $sector, $name, $totalLeaveCount, $leaveCountWithPay, $leaveCountWithoutPay, $receivedCount, $cancelledCount, $otherLeaveTypes)
    {
        return [
            'id' => $id . '-' . strtolower($sector),
            'name' => $name,
            'sector' => $sector,
            'total_leave_count' => $totalLeaveCount,
            'leave_count_with_pay' => $leaveCountWithPay,
            'leave_count_without_pay' => $leaveCountWithoutPay,
            'received_count' => $receivedCount,
            'cancelled_count' => $cancelledCount,
            'other_leave_types' => $otherLeaveTypes
        ];
    }
}
