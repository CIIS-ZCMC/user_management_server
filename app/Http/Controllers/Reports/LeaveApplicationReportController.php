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
    public function generateReport(Request $request)
    {
        try {
            $status = $request->input('status'); // Received, Cancelled
            $leaveTypeIds = $request->input('leave_type_id'); // Can be an array
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $sector = $request->input('sector');
            $areaId = $request->input('area_id');
            $areaUnder = $request->input('area_under');
            $reportFormat = $request->input('report_format'); // By area, By employee, Leave utilization

            $leaveApplications = $this->filterLeaveApplications($status, $leaveTypeIds, $dateFrom, $dateTo, $sector, $areaId, $areaUnder);

            if ($reportFormat === 'area') {
                return $this->generateAreaReport($leaveApplications);
            } elseif ($reportFormat === 'employee') {
                return $this->generateEmployeeReport($leaveApplications);
            } else {
                return response()->json(['message' => 'Invalid report format'], Response::HTTP_BAD_REQUEST);
            }
        } catch (\Throwable $e) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'generateReport', $e->getMessage());
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    private function filterLeaveApplications($status, $leaveTypeIds, $dateFrom, $dateTo, $sector, $areaId, $areaUnder)
    {
        $query = LeaveApplication::with([
            'employeeProfile.assignedAreas.division',
            'employeeProfile.assignedAreas.department',
            'employeeProfile.assignedAreas.section',
            'employeeProfile.assignedAreas.unit',
            'employeeProfile.assignedAreas.designation',
            'leaveType'
        ]);

        if ($status) {
            $query->where('status', $status);
        }

        if (!empty($leaveTypeIds)) {
            $query->whereIn('leave_type_id', $leaveTypeIds);
        }

        if ($dateFrom && $dateTo) {
            $query->whereBetween('date_from', [$dateFrom, $dateTo]);
        } elseif ($dateFrom) {
            $query->whereDate('date_from', '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->whereDate('date_from', '<=', $dateTo);
        }

        if ($sector && $areaId) {
            $query->whereHas('employeeProfile.assignedAreas', function ($q) use ($sector, $areaId, $areaUnder) {
                switch (strtolower($sector)) {
                    case 'division':
                        $q->where('division_id', $areaId);
                        if ($areaUnder === 'All') {
                            $departments = Department::where('division_id', $areaId)->get();
                            foreach ($departments as $department) {
                                $q->orWhere('department_id', $department->id);
                                $sections = Section::where('department_id', $department->id)->get();
                                foreach ($sections as $section) {
                                    $q->orWhere('section_id', $section->id);
                                    $units = Unit::where('section_id', $section->id)->get();
                                    foreach ($units as $unit) {
                                        $q->orWhere('unit_id', $unit->id);
                                    }
                                }
                            }
                        }
                        break;
                    case 'department':
                        $q->where('department_id', $areaId);
                        if ($areaUnder === 'All') {
                            $sections = Section::where('department_id', $areaId)->get();
                            foreach ($sections as $section) {
                                $q->orWhere('section_id', $section->id);
                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $q->orWhere('unit_id', $unit->id);
                                }
                            }
                        }
                        break;
                    case 'section':
                        $q->where('section_id', $areaId);
                        if ($areaUnder === 'All') {
                            $units = Unit::where('section_id', $areaId)->get();
                            foreach ($units as $unit) {
                                $q->orWhere('unit_id', $unit->id);
                            }
                        }
                        break;
                    case 'unit':
                        $q->where('unit_id', $areaId);
                        break;
                }
            });
        }

        return $query->get();
    }



    private function generateEmployeeReport($leaveApplications)
    {
        $employeeData = $leaveApplications->groupBy('employee_profile_id')->map(function ($group) {
            $employee = $group->first()->employeeProfile;
            return [
                'employee_id' => $employee->employee_id,
                'profile_url' => $employee->profile_url,
                'date_hired' => $employee->date_hired,
                'total_leaves' => $group->count(),
                'leave_applications' => $group->map(function ($leaveApplication) {
                    return [
                        'leave_application_id' => $leaveApplication->id,
                        'date_from' => $leaveApplication->date_from,
                        'date_to' => $leaveApplication->date_to,
                        'status' => $leaveApplication->status,
                        'reason' => $leaveApplication->reason,
                        'is_printed' => $leaveApplication->is_printed,
                        'leave_type_name' => optional($leaveApplication->leaveType)->name,
                        'leave_application_created_at' => $leaveApplication->created_at,
                        'leave_application_updated_at' => $leaveApplication->updated_at,
                    ];
                })
            ];
        });

        return response()->json([
            'total_count' => $leaveApplications->count(),
            'data' => $employeeData
        ], Response::HTTP_OK);
    }

    private function generateAreaReport($leaveApplications)
    {
        $utilizationData = $leaveApplications->groupBy(function ($leaveApplication) {
            $assignedArea = $leaveApplication->employeeProfile->assignedAreas->first();
            if ($assignedArea->division_id) {
                return json_encode(['sector' => 'Division', 'name' => optional($assignedArea->division)->name]);
            } elseif ($assignedArea->department_id) {
                return json_encode(['sector' => 'Department', 'name' => optional($assignedArea->department)->name]);
            } elseif ($assignedArea->section_id) {
                return json_encode(['sector' => 'Section', 'name' => optional($assignedArea->section)->name]);
            } elseif ($assignedArea->unit_id) {
                return json_encode(['sector' => 'Unit', 'name' => optional($assignedArea->unit)->name]);
            } else {
                return json_encode(['sector' => 'Unassigned', 'name' => 'Unassigned']);
            }
        })->map(function ($group, $key) {
            $key = json_decode($key, true);

            $leaveTypes = $group->groupBy('leave_type_id')->map(function ($leaveGroup, $leaveTypeId) {
                return [
                    'leave_type_id' => $leaveTypeId,
                    'count' => $leaveGroup->count()
                ];
            })->values()->all();

            $result = [
                'sector' => $key['sector'],
                'name' => $key['name'],
                'leave_with_pay' => $group->where('without_pay', 0)->count(),
                'leave_without_pay' => $group->where('without_pay', 1)->count(),
                'received_applications' => $group->count(),
                'cancelled_leave' => $group->where('status', 'Cancelled')->count(),
                'from_regular_employees' => $group->where('employeeProfile.employment_type_id', 1)->count(),
                'from_temporary_employees' => $group->where('employeeProfile.employment_type_id', 2)->count(),
                'employee_names' => $group->pluck('employeeProfile.employee_id')->unique()->values()->all(),
                'leave_types' => $leaveTypes
            ];

            // Filter out leave type counts that are 0
            $leaveTypeCounts = [
                'VL' => $group->where('leave_type_id', 1)->count(),
                'SL' => $group->where('leave_type_id', 2)->count(),
                'FL' => $group->where('leave_type_id', 3)->count(),
                'SPL' => $group->where('leave_type_id', 4)->count()
            ];

            foreach ($leaveTypeCounts as $type => $count) {
                if ($count > 0) {
                    $result[$type] = $count;
                }
            }

            return $result;
        })->filter(function ($area) {
            // Exclude areas with no received applications
            return $area['received_applications'] > 0;
        })->values();

        return response()->json($utilizationData, Response::HTTP_OK);
    }


    public function test(Request $request)
    {
        try {
            $area = $request->sector;
            $report_format = $request->report_format;
            $status = $request->status;
            $area_under = $request->area_under;
            $area_id = $request->area_id;
            $leave_type_id = $request->leave_type_id;
            $areas = [];

            // Debugging: Log the received leave_type_id
            Log::info('Received leave_type_id: ' . $leave_type_id);

            if ($report_format === 'area' || $report_format === 'Area') {
                $leave_type = LeaveType::find($leave_type_id);
                // return $leave_type;

                // Debugging: Log the leave_type details
                if ($leave_type) {
                    Log::info('LeaveType found: ' . $leave_type->name . ' (' . $leave_type->code . ')');
                } else {
                    Log::warning('LeaveType not found for ID: ' . $leave_type_id);
                }

                // Only return areas
                switch (strtolower($area)) {
                    case "division":
                        $division = Division::where('id', $area_id)->first();
                        if ($division) {
                            $leave_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                ->whereHas('employeeProfile.assignedArea', function ($query) use ($division) {
                                    $query->where('division_id', $division->id);
                                })
                                ->count();
                            $areas[] = [
                                //'id' => $division->id . '-division',
                                'name' => $division->name,
                                'sector' => 'Division',
                                'leave_count' => $leave_count,
                                'leave_type_name' => $leave_type ? $leave_type->name : null,
                                'leave_type_code' => $leave_type ? $leave_type->code : null
                            ];
                        }

                        if ($area_under === 'All' || $area_under === 'all') {
                            $departments = Department::where('division_id', $area_id)->get();
                            foreach ($departments as $department) {
                                $leave_count = LeaveApplication::whereHas('employeeProfile.assignedArea', function ($query) use ($department) {
                                    $query->where('department_id', $department->id);
                                })->count();
                                $areas[] = [
                                    'id' => $department->id . '-department',
                                    'name' => $department->name,
                                    'sector' => 'Department',
                                    'leave_count' => $leave_count,
                                    'leave_type_name' => $leave_type ? $leave_type->name : null,
                                    'leave_type_code' => $leave_type ? $leave_type->code : null
                                ];
                                $sections = Section::where('department_id', $department->id)->get();
                                foreach ($sections as $section) {
                                    $leave_count = LeaveApplication::whereHas('employeeProfile.assignedArea', function ($query) use ($section) {
                                        $query->where('section_id', $section->id);
                                    })->count();
                                    $areas[] = [
                                        'id' => $section->id . '-section',
                                        'name' => $section->name,
                                        'sector' => 'Section',
                                        'leave_count' => $leave_count,
                                        'leave_type_name' => $leave_type ? $leave_type->name : null,
                                        'leave_type_code' => $leave_type ? $leave_type->code : null
                                    ];
                                    $units = Unit::where('section_id', $section->id)->get();
                                    foreach ($units as $unit) {
                                        $leave_count = LeaveApplication::whereHas('employeeProfile.assignedArea', function ($query) use ($unit) {
                                            $query->where('unit_id', $unit->id);
                                        })->count();
                                        $areas[] = [
                                            'id' => $unit->id . '-unit',
                                            'name' => $unit->name,
                                            'sector' => 'Unit',
                                            'leave_count' => $leave_count,
                                            'leave_type_name' => $leave_type ? $leave_type->name : null,
                                            'leave_type_code' => $leave_type ? $leave_type->code : null
                                        ];
                                    }
                                }
                            }
                        }
                        break;
                    case "department":
                        $department = Department::where('id', $area_id)->first();
                        if ($department) {
                            $leave_count = LeaveApplication::whereHas('employeeProfile.assignedArea', function ($query) use ($department) {
                                $query->where('department_id', $department->id);
                            })->count();
                            $areas[] = [
                                'id' => $area_id . '-department',
                                'name' => $department->name,
                                'sector' => 'Department',
                                'leave_count' => $leave_count,
                                'leave_type_name' => $leave_type ? $leave_type->name : null,
                                'leave_type_code' => $leave_type ? $leave_type->code : null
                            ];
                        }
                        if ($area_under === 'All') {
                            $sections = Section::where('department_id', $area_id)->get();
                            foreach ($sections as $section) {
                                $leave_count = LeaveApplication::whereHas('employeeProfile.assignedArea', function ($query) use ($section) {
                                    $query->where('section_id', $section->id);
                                })->count();
                                $areas[] = [
                                    'id' => $section->id . '-section',
                                    'name' => $section->name,
                                    'sector' => 'Section',
                                    'leave_count' => $leave_count,
                                    'leave_type_name' => $leave_type ? $leave_type->name : null,
                                    'leave_type_code' => $leave_type ? $leave_type->code : null
                                ];
                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $leave_count = LeaveApplication::whereHas('employeeProfile.assignedArea', function ($query) use ($unit) {
                                        $query->where('unit_id', $unit->id);
                                    })->count();
                                    $areas[] = [
                                        'id' => $unit->id . '-unit',
                                        'name' => $unit->name,
                                        'sector' => 'Unit',
                                        'leave_count' => $leave_count,
                                        'leave_type_name' => $leave_type ? $leave_type->name : null,
                                        'leave_type_code' => $leave_type ? $leave_type->code : null
                                    ];
                                }
                            }
                        }
                        break;
                    case "section":
                        $section = Section::where('id', $area_id)->first();
                        if ($section) {
                            $leave_count = LeaveApplication::whereHas('employeeProfile.assignedArea', function ($query) use ($section) {
                                $query->where('section_id', $section->id);
                            })->count();
                            $areas[] = [
                                'id' => $area_id . '-section',
                                'name' => $section->name,
                                'sector' => 'Section',
                                'leave_count' => $leave_count,
                                'leave_type_name' => $leave_type ? $leave_type->name : null,
                                'leave_type_code' => $leave_type ? $leave_type->code : null
                            ];
                        }
                        if ($area_under === 'All') {
                            $units = Unit::where('section_id', $area_id)->get();
                            foreach ($units as $unit) {
                                $leave_count = LeaveApplication::whereHas('employeeProfile.assignedArea', function ($query) use ($unit) {
                                    $query->where('unit_id', $unit->id);
                                })->count();
                                $areas[] = [
                                    'id' => $unit->id . '-unit',
                                    'name' => $unit->name,
                                    'sector' => 'Unit',
                                    'leave_count' => $leave_count,
                                    'leave_type_name' => $leave_type ? $leave_type->name : null,
                                    'leave_type_code' => $leave_type ? $leave_type->code : null
                                ];
                            }
                        }
                        break;
                    case "unit":
                        $unit = Unit::where('id', $area_id)->first();
                        if ($unit) {
                            $leave_count = LeaveApplication::whereHas('employeeProfile.assignedArea', function ($query) use ($unit) {
                                $query->where('unit_id', $unit->id);
                            })->count();
                            $areas[] = [
                                'id' => $area_id . '-unit',
                                'name' => $unit->name,
                                'sector' => 'Unit',
                                'leave_count' => $leave_count,
                                'leave_type_name' => $leave_type ? $leave_type->name : null,
                                'leave_type_code' => $leave_type ? $leave_type->code : null
                            ];
                        }
                        break;
                }
                // Sort the areas by leave_count in descending order
                usort($areas, function ($a, $b) {
                    return $b['leave_count'] - $a['leave_count'];
                });
                return response()->json(['areas' => $areas]);
            } elseif ($report_format === 'employee') {
                // Return leave applications with areas
                $status = $request->status; // Assuming leave_status is passed in the request
                $leave_applications = LeaveApplication::where('status', $status)->get();

                foreach ($leave_applications as $leave_application) {
                    $employee_profile_id = $leave_application->employee_profile_id;

                    // Get employee's assigned areas
                    $employee = AssignArea::where('employee_profile_id', $employee_profile_id)->first();
                    $employee_areas = [];

                    if ($employee) {
                        switch (strtolower($area)) {
                            case "division":
                                $division = Division::where('id', $area_id)->first();
                                if ($division) {
                                    $employee_areas[] = ['id' => $area_id . '-division', 'name' => $division->name, 'sector' => 'Division'];
                                }
                                if ($area_under) {
                                    $departments = Department::where('division_id', $area_id)->get();
                                    foreach ($departments as $department) {
                                        $employee_areas[] = ['id' => $department->id . '-department', 'name' => $department->name, 'sector' => 'Department'];
                                        $sections = Section::where('department_id', $department->id)->get();
                                        foreach ($sections as $section) {
                                            $employee_areas[] = ['id' => $section->id . '-section', 'name' => $section->name, 'sector' => 'Section'];
                                            $units = Unit::where('section_id', $section->id)->get();
                                            foreach ($units as $unit) {
                                                $employee_areas[] = ['id' => $unit->id . '-unit', 'name' => $unit->name, 'sector' => 'Unit'];
                                            }
                                        }
                                    }
                                }
                                break;
                            case "department":
                                $department = Department::where('id', $area_id)->first();
                                if ($department) {
                                    $employee_areas[] = ['id' => $area_id . '-department', 'name' => $department->name, 'sector' => 'Department'];
                                }
                                if ($area_under) {
                                    $sections = Section::where('department_id', $area_id)->get();
                                    foreach ($sections as $section) {
                                        $employee_areas[] = ['id' => $section->id . '-section', 'name' => $section->name, 'sector' => 'Section'];
                                        $units = Unit::where('section_id', $section->id)->get();
                                        foreach ($units as $unit) {
                                            $employee_areas[] = ['id' => $unit->id . '-unit', 'name' => $unit->name, 'sector' => 'Unit'];
                                        }
                                    }
                                }
                                break;
                            case "section":
                                $section = Section::where('id', $area_id)->first();
                                if ($section) {
                                    $employee_areas[] = ['id' => $area_id . '-section', 'name' => $section->name, 'sector' => 'Section'];
                                }
                                if ($area_under) {
                                    $units = Unit::where('section_id', $area_id)->get();
                                    foreach ($units as $unit) {
                                        $employee_areas[] = ['id' => $unit->id . '-unit', 'name' => $unit->name, 'sector' => 'Unit'];
                                    }
                                }
                                break;
                            case "unit":
                                $unit = Unit::where('id', $area_id)->first();
                                if ($unit) {
                                    $employee_areas[] = ['id' => $area_id . '-unit', 'name' => $unit->name, 'sector' => 'Unit'];
                                }
                                break;
                        }
                    }

                    // Combine leave application with employee areas
                    $areas[] = [
                        'leave_application' => $leave_application,
                        'employee_areas' => $employee_areas
                    ];
                }

                return response()->json(['areas' => $areas]);
            }

            return response()->json([
                'data' => $areas,
                'message' => 'Successfully retrieved all my areas.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'myAreas', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function filterLeaveApplicationReport(Request $request)
    {
    }
    // public function test(Request $request)
    // {
    //     try {

    //         $area = $request->sector;
    //         $report_format = $request->report_format;
    //         $status = $request->status;
    //         $area_under = $request->area_under;
    //         $area_id = $request->area_id;
    //         $leave_type_id = $request->leave_type_id;
    //         $areas = [];

    //         // return $request;

    //         if ($report_format === 'area' or $report_format === 'Area') {
    //             $leave_type = LeaveType::find($leave_type_id);
    //             // Only return areas
    //             switch ($area) {
    //                 case "Division":
    //                 case "division":
    //                     $division = Division::where('id', $area_id)->first();
    //                     if ($division) {
    //                         $leave_count = LeaveApplication::where('leave_type_id', $leave_type_id)->whereHas('employeeProfile', function ($query) use ($division) {
    //                             $query->whereHas('assignedArea', function ($q) use ($division) {
    //                                 $q->where('division_id', $division->id);
    //                             });
    //                         })->count();
    //                         $areas[] = [
    //                             //'id' => $division->id . '-division',
    //                             'name' => $division->name,
    //                             'sector' => 'Division',
    //                             'leave_count' => $leave_count,
    //                             'leave_type_name' => $leave_type ? $leave_type->name : null,
    //                             'leave_type_code' => $leave_type ? $leave_type->code : null
    //                         ];
    //                     }
    //                     if ($area_under === 'All' or $area_under === 'all') {
    //                         $departments = Department::where('division_id', $area_id)->get();
    //                         foreach ($departments as $department) {
    //                             $leave_count = LeaveApplication::whereHas('employeeProfile', function ($query) use ($department) {
    //                                 $query->whereHas('assignedArea', function ($q) use ($department) {
    //                                     $q->where('department_id', $department->id);
    //                                 });
    //                             })->count();
    //                             $areas[] = [
    //                                 'id' => $department->id . '-department',
    //                                 'name' => $department->name,
    //                                 'sector' => 'Department',
    //                                 'leave_count' => $leave_count,
    //                                 'leave_type_name' => $leave_type ? $leave_type->name : null,
    //                                 'leave_type_code' => $leave_type ? $leave_type->code : null
    //                             ];
    //                             $sections = Section::where('department_id', $department->id)->get();
    //                             foreach ($sections as $section) {
    //                                 $leave_count = LeaveApplication::whereHas('employeeProfile', function ($query) use ($sections) {
    //                                     $query->whereHas('assignedArea', function ($q) use ($sections) {
    //                                         $q->where('section_id', $sections->id);
    //                                     });
    //                                 })->count();
    //                                 $areas[] = [
    //                                     'id' => $section->id . '-section',
    //                                     'name' => $section->name,
    //                                     'sector' => 'Section',
    //                                     'leave_count' => $leave_count,
    //                                     'leave_type_name' => $leave_type ? $leave_type->name : null,
    //                                     'leave_type_code' => $leave_type ? $leave_type->code : null
    //                                 ];
    //                                 $units = Unit::where('section_id', $section->id)->get();
    //                                 foreach ($units as $unit) {
    //                                     $leave_count = LeaveApplication::whereHas('employeeProfile', function ($query) use ($unit) {
    //                                         $query->whereHas('assignedArea', function ($q) use ($unit) {
    //                                             $q->where('unit_id', $unit->id);
    //                                         });
    //                                     })->count();
    //                                     $areas[] = [
    //                                         'id' => $unit->id . '-unit',
    //                                         'name' => $unit->name,
    //                                         'sector' => 'Unit',
    //                                         'leave_count' => $leave_count,
    //                                         'leave_type_name' => $leave_type ? $leave_type->name : null,
    //                                         'leave_type_code' => $leave_type ? $leave_type->code : null
    //                                     ];
    //                                 }
    //                             }
    //                         }
    //                     }
    //                     break;
    //                 case "Department":
    //                 case "department":
    //                     $department = Department::where('id', $area_id)->first();
    //                     if ($department) {
    //                         $leave_count = LeaveApplication::whereHas('employeeProfile', function ($query) use ($department) {
    //                             $query->whereHas('assignedArea', function ($q) use ($department) {
    //                                 $q->where('department_id', $department->id);
    //                             });
    //                         })->count();
    //                         $areas[] = [
    //                             'id' => $area_id . '-' . strtolower($area),
    //                             'name' => $department->name,
    //                             'sector' => $area,
    //                             'leave_count' => $leave_count,
    //                             'leave_type_name' => $leave_type ? $leave_type->name : null,
    //                             'leave_type_code' => $leave_type ? $leave_type->code : null
    //                         ];
    //                     }
    //                     if ($area_under === 'All') {
    //                         $sections = Section::where('department_id', $area_id)->get();
    //                         foreach ($sections as $section) {
    //                             $leave_count = LeaveApplication::whereHas('employeeProfile', function ($query) use ($section) {
    //                                 $query->whereHas('assignedArea', function ($q) use ($section) {
    //                                     $q->where('section_id', $section->id);
    //                                 });
    //                             })->count();
    //                             $areas[] = [
    //                                 'id' => $section->id . '-section',
    //                                 'name' => $section->name,
    //                                 'sector' => 'Section',
    //                                 'leave_count' => $leave_count,
    //                                 'leave_type_name' => $leave_type ? $leave_type->name : null,
    //                                 'leave_type_code' => $leave_type ? $leave_type->code : null
    //                             ];
    //                             $units = Unit::where('section_id', $section->id)->get();
    //                             foreach ($units as $unit) {
    //                                 $leave_count = LeaveApplication::whereHas('employeeProfile', function ($query) use ($unit) {
    //                                     $query->whereHas('assignedArea', function ($q) use ($unit) {
    //                                         $q->where('unit_id', $unit->id);
    //                                     });
    //                                 })->count();
    //                                 $areas[] = [
    //                                     'id' => $unit->id . '-unit',
    //                                     'name' => $unit->name,
    //                                     'sector' => 'Unit',
    //                                     'leave_count' => $leave_count,
    //                                     'leave_type_name' => $leave_type ? $leave_type->name : null,
    //                                     'leave_type_code' => $leave_type ? $leave_type->code : null
    //                                 ];
    //                             }
    //                         }
    //                     }
    //                     break;
    //                 case "Section":
    //                 case "section":
    //                     $section = Section::where('id', $area_id)->first();
    //                     if ($section) {
    //                         $leave_count = LeaveApplication::whereHas('employeeProfile', function ($query) use ($section) {
    //                             $query->whereHas('assignedArea', function ($q) use ($section) {
    //                                 $q->where('section_id', $section->id);
    //                             });
    //                         })->count();
    //                         $areas[] = [
    //                             'id' => $area_id . '-' . strtolower($area),
    //                             'name' => $section->name,
    //                             'sector' => $area,
    //                             'leave_count' => $leave_count,
    //                             'leave_type_name' => $leave_type ? $leave_type->name : null,
    //                             'leave_type_code' => $leave_type ? $leave_type->code : null
    //                         ];
    //                     }
    //                     if ($area_under === 'All') {
    //                         $units = Unit::where('section_id', $area_id)->get();
    //                         foreach ($units as $unit) {
    //                             $leave_count = LeaveApplication::whereHas('employeeProfile', function ($query) use ($unit) {
    //                                 $query->whereHas('assignedArea', function ($q) use ($unit) {
    //                                     $q->where('unit_id', $unit->id);
    //                                 });
    //                             })->count();
    //                             $areas[] = [
    //                                 'id' => $unit->id . '-unit',
    //                                 'name' => $unit->name,
    //                                 'sector' => 'Unit',
    //                                 'leave_count' => $leave_count,
    //                                 'leave_type_name' => $leave_type ? $leave_type->name : null,
    //                                 'leave_type_code' => $leave_type ? $leave_type->code : null
    //                             ];
    //                         }
    //                     }
    //                     break;
    //                 case "Unit":
    //                 case "unit":
    //                     $unit = Unit::where('id', $area_id)->first();
    //                     if ($unit) {
    //                         $leave_count = LeaveApplication::whereHas('employeeProfile', function ($query) use ($unit) {
    //                             $query->whereHas('assignedArea', function ($q) use ($unit) {
    //                                 $q->where('unit_id', $unit->id);
    //                             });
    //                         })->count();
    //                         $areas[] = [
    //                             'id' => $area_id . '-' . strtolower($area),
    //                             'name' => $unit->name,
    //                             'sector' => $area,
    //                             'leave_count' => $leave_count,
    //                             'leave_type_name' => $leave_type ? $leave_type->name : null,
    //                             'leave_type_code' => $leave_type ? $leave_type->code : null
    //                         ];
    //                     }
    //                     break;
    //             }
    //             // Sort the areas by leave_count in descending order
    //             usort($areas, function ($a, $b) {
    //                 return $b['leave_count'] - $a['leave_count'];
    //             });
    //             return response()->json(['areas' => $areas]);
    //         } elseif ($report_format === 'employee') {
    //             // Return leave applications with areas
    //             $status = $request->status; // Assuming leave_status is passed in the request
    //             $leave_applications = LeaveApplication::where('status', $status)->get();

    //             foreach ($leave_applications as $leave_application) {
    //                 $employee_profile_id = $leave_application->employee_profile_id;

    //                 // Get employee's assigned areas
    //                 $employee = AssignArea::where('employee_profile_id', $employee_profile_id)->first();
    //                 $employee_areas = [];

    //                 if ($employee) {
    //                     switch ($area) {
    //                         case "Division":
    //                             $division = Division::where('id', $area_id)->first();
    //                             if ($division) {
    //                                 $employee_areas[] = ['id' => $area_id . '-' . strtolower($area), 'name' => $division->name, 'sector' => $area];
    //                             }
    //                             if ($area_under) {
    //                                 $departments = Department::where('division_id', $area_id)->get();
    //                                 foreach ($departments as $department) {
    //                                     $employee_areas[] = ['id' => $department->id . '-department', 'name' => $department->name, 'sector' => 'Department'];
    //                                     $sections = Section::where('department_id', $department->id)->get();
    //                                     foreach ($sections as $section) {
    //                                         $employee_areas[] = ['id' => $section->id . '-section', 'name' => $section->name, 'sector' => 'Section'];
    //                                         $units = Unit::where('section_id', $section->id)->get();
    //                                         foreach ($units as $unit) {
    //                                             $employee_areas[] = ['id' => $unit->id . '-unit', 'name' => $unit->name, 'sector' => 'Unit'];
    //                                         }
    //                                     }
    //                                 }
    //                             }
    //                             break;
    //                         case "Department":
    //                             $department = Department::where('id', $area_id)->first();
    //                             if ($department) {
    //                                 $employee_areas[] = ['id' => $area_id . '-' . strtolower($area), 'name' => $department->name, 'sector' => $area];
    //                             }
    //                             if ($area_under) {
    //                                 $sections = Section::where('department_id', $area_id)->get();
    //                                 foreach ($sections as $section) {
    //                                     $employee_areas[] = ['id' => $section->id . '-section', 'name' => $section->name, 'sector' => 'Section'];
    //                                     $units = Unit::where('section_id', $section->id)->get();
    //                                     foreach ($units as $unit) {
    //                                         $employee_areas[] = ['id' => $unit->id . '-unit', 'name' => $unit->name, 'sector' => 'Unit'];
    //                                     }
    //                                 }
    //                             }
    //                             break;
    //                         case "Section":
    //                             $section = Section::where('id', $area_id)->first();
    //                             if ($section) {
    //                                 $employee_areas[] = ['id' => $area_id . '-' . strtolower($area), 'name' => $section->name, 'sector' => $area];
    //                             }
    //                             if ($area_under) {
    //                                 $units = Unit::where('section_id', $area_id)->get();
    //                                 foreach ($units as $unit) {
    //                                     $employee_areas[] = ['id' => $unit->id . '-unit', 'name' => $unit->name, 'sector' => 'Unit'];
    //                                 }
    //                             }
    //                             break;
    //                         case "Unit":
    //                             $unit = Unit::where('id', $area_id)->first();
    //                             if ($unit) {
    //                                 $employee_areas[] = ['id' => $area_id . '-' . strtolower($area), 'name' => $unit->name, 'sector' => $area];
    //                             }
    //                             break;
    //                     }
    //                 }

    //                 // Combine leave application with employee areas
    //                 $areas[] = [
    //                     'leave_application' => $leave_application,
    //                     'employee_areas' => $employee_areas
    //                 ];
    //             }

    //             return response()->json(['areas' => $areas]);
    //         }

    //         return response()->json([
    //             'data' => $areas,
    //             'message' => 'Successfully retrieved all my areas.'
    //         ], Response::HTTP_OK);
    //     } catch (\Throwable $th) {
    //         Helpers::errorLog($this->CONTROLLER_NAME, 'myAreas', $th->getMessage());
    //         return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }
}
