<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Department;
use App\Models\Division;
use App\Models\Section;
use App\Models\Unit;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\AssignArea;
use App\Helpers\Helpers;
use Illuminate\Support\Facades\DB;

class LeaveApplicationReportController extends Controller
{
    private $CONTROLLER_NAME = "Leave Management Reports";

    public function filterLeaveApplication(Request $request)
    {
        try {
            $area = $request->sector;
            $report_format = $request->report_format;
            $status = $request->status;
            $area_under = $request->area_under;
            $area_id = $request->area_id;
            $leave_type_ids = $request->leave_type_ids ? explode(',', $request->leave_type_ids) : [];
            $date_from = $request->date_from;
            $date_to = $request->date_to;
            $sort_by = $request->sort_by;
            $limit = $request->limit;
            $areas = [];


            // Determine if all other parameters are empty
            $all_params_empty = empty($area) && empty($status) && empty($area_under) && empty($area_id) && empty($leave_type_ids) && empty($date_from) && empty($date_to) && empty($sort_by) && empty($limit);

            if ($all_params_empty) {
                $areas = $this->getAllAppropriateData($report_format);
            } else {
                if (strtolower($report_format) === 'area') {
                    $areas = $this->getAreaReport($area, $area_id, $leave_type_ids, $area_under, $status, $date_from, $date_to);
                } elseif (strtolower($report_format) === 'employee') {
                    $areas = $this->getEmployeeReport($status, $area, $area_id, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                } else {
                    return response()->json(['message' => 'Invalid report format'], Response::HTTP_OK);
                }
            }

            return response()->json([
                'data' => $areas,
                'message' => 'Successfully retrieved areas.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterLeaveApplication', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getAllAppropriateData($report_format)
    {
        $areas = [];

        if ($report_format === 'area') {
            // Fetch all data for area report
            $divisions = Division::all();
            foreach ($divisions as $division) {
                $areas = array_merge($areas, $this->getDivisionReport($division->id, [], 'all', null, null, null));
            }
        } elseif ($report_format === 'employee') {
            // Fetch all data for employee report
            $leave_applications = LeaveApplication::all();
            foreach ($leave_applications as $leave_application) {
                $areas[] = [
                    'leave_application' => $leave_application,
                    'employee_areas' => $this->getEmployeeAreas(Division::class, $leave_application->employeeProfile->division_id, 'division', 'all')
                ];
            }
        }

        return $areas;
    }

    private function getAreaReport($area, $area_id, $leave_type_ids, $area_under, $status, $date_from, $date_to)
    {
        $areas = [];

        switch (strtolower($area)) {
            case "division":
                $areas = $this->getDivisionReport($area_id, $leave_type_ids, $area_under, $status, $date_from, $date_to);
                break;
            case "department":
                $areas = $this->getDepartmentReport($area_id, $leave_type_ids, $area_under, $status, $date_from, $date_to);
                break;
            case "section":
                $areas = $this->getSectionReport($area_id, $leave_type_ids, $area_under, $status, $date_from, $date_to);
                break;
            case "unit":
                $areas = $this->getUnitReport($area_id, $leave_type_ids, $status, $date_from, $date_to);
                break;
        }

        usort($areas, function ($a, $b) {
            return $b['leave_count'] - $a['leave_count'];
        });

        return $areas;
    }

    private function getDivisionReport($division_id, $leave_type_ids, $area_under, $status, $date_from, $date_to)
    {
        $areas = [];
        if (!$division_id || empty($division_id)) {
            $divisions = Division::all();
            foreach ($divisions as $division) {
                $areas = array_merge($areas, $this->getDivisionReport($division->id, [], '', null, null, null));
            }

            return $areas;
        }

        $division = Division::find($division_id);
        $areas = [$this->calculateLeaveCounts($division, 'division', $leave_type_ids, $status, $date_from, $date_to)];

        if (strtolower($area_under) === 'all') {
            $departments = Department::where('division_id', $division_id)->get();
            foreach ($departments as $department) {
                $areas[] = $this->calculateLeaveCounts($department, 'department', $leave_type_ids, $status, $date_from, $date_to);
                $sections = Section::where('department_id', $department->id)->get();
                foreach ($sections as $section) {
                    $areas[] = $this->calculateLeaveCounts($section, 'section', $leave_type_ids, $status, $date_from, $date_to);
                    $units = Unit::where('section_id', $section->id)->get();
                    foreach ($units as $unit) {
                        $areas[] = $this->calculateLeaveCounts($unit, 'unit', $leave_type_ids, $status, $date_from, $date_to);
                    }
                }
            }
            // Get sections directly under the division (if any) that are not under any department
            $sections = Section::where('division_id', $division_id)->whereNull('department_id')->get();
            foreach ($sections as $section) {
                $areas[] = $this->calculateLeaveCounts($section, 'section', $leave_type_ids, $status, $date_from, $date_to);
                // Get all units directly under the section
                $units = Unit::where('section_id', $section->id)->get();
                foreach ($units as $unit) {
                    $areas[] = $this->calculateLeaveCounts($unit, 'unit', $leave_type_ids, $status, $date_from, $date_to);
                }
            }
        }

        return $areas;
    }

    private function getDepartmentReport($department_id, $leave_type_ids, $area_under, $status, $date_from, $date_to)
    {
        $areas = [];
        if (!$department_id || empty($department_id)) {
            $departments = Division::all();
            foreach ($departments as $department) {
                $areas = array_merge($areas, $this->getDepartmentReport($department->id, [], '', null, null, null));
            }

            return $areas;
        }

        $department = Department::find($department_id);
        $areas = [$this->calculateLeaveCounts($department, 'department', $leave_type_ids, $status, $date_from, $date_to)];

        if (strtolower($area_under) === 'all') {
            $sections = Section::where('department_id', $department_id)->get();
            foreach ($sections as $section) {
                $areas[] = $this->calculateLeaveCounts($section, 'section', $leave_type_ids, $status, $date_from, $date_to);
                $units = Unit::where('section_id', $section->id)->get();
                foreach ($units as $unit) {
                    $areas[] = $this->calculateLeaveCounts($unit, 'unit', $leave_type_ids, $status, $date_from, $date_to);
                }
            }
        }

        return $areas;
    }

    private function getSectionReport($section_id, $leave_type_ids, $area_under, $status, $date_from, $date_to)
    {
        $areas = [];
        if (!$section_id || empty($section_id)) {
            $sections = Section::all();
            foreach ($sections as $section) {
                $areas = array_merge($areas, $this->getSectionReport($section->id, [], '', null, null, null));
            }

            return $areas;
        }

        $section = Section::find($section_id);
        $areas = [$this->calculateLeaveCounts($section, 'section', $leave_type_ids, $status, $date_from, $date_to)];

        if (strtolower($area_under) === 'all') {
            $units = Unit::where('section_id', $section_id)->get();
            foreach ($units as $unit) {
                $areas[] = $this->calculateLeaveCounts($unit, 'unit', $leave_type_ids, $status, $date_from, $date_to);
            }
        }

        return $areas;
    }

    private function getUnitReport($unit_id, $leave_type_ids, $status, $date_from, $date_to)
    {
        $areas = [];
        if (!$unit_id || empty($unit_id)) {
            $units = Unit::all();
            foreach ($units as $unit) {
                $areas = array_merge($areas, $this->getUnitReport($unit->id, [], '', null, null, null));
            }

            return $areas;
        }
        $unit = Unit::find($unit_id);
        return [$this->calculateLeaveCounts($unit, 'unit', $leave_type_ids, $status, $date_from, $date_to)];
    }

    private function calculateLeaveCounts($area, $sector, $leave_type_ids, $status, $date_from, $date_to)
    {
        $areaData = [
            'id' => $area->id . '-' . $sector,
            'name' => $area->name,
            'sector' => ucfirst($sector),
            'leave_with_pay_count' => 0,
            'leave_without_pay_count' => 0,
            'leave_count' => 0,
            'leave_types' => []
        ];

        $leave_with_pay_count_total = 0;
        $leave_without_pay_count_total = 0;
        $leave_count_total = 0;

        if (empty($leave_type_ids)) {
            // Fetch all leave types if no specific leave type IDs are provided
            $leave_types = LeaveType::all();
        } else {
            $leave_types = LeaveType::whereIn('id', $leave_type_ids)->get();
        }

        foreach ($leave_types as $leave_type) {
            $query = LeaveApplication::where('leave_type_id', $leave_type->id)
                ->whereHas('employeeProfile', function ($query) use ($area, $sector) {
                    $query->whereHas('assignedArea', function ($q) use ($area, $sector) {
                        $q->where($sector . '_id', $area->id);
                    });
                });

            if ($status) {
                $query->where('status', $status);
            }

            if ($date_from && $date_to) {
                $query->whereBetween('date_from', [$date_from, $date_to]);
            }

            $leave_count = $query->count();

            $leave_with_pay_count = $query->clone()->where('without_pay', 0)->count();
            $leave_without_pay_count = $query->clone()->where('without_pay', 1)->count();

            $leave_with_pay_count_total += $leave_with_pay_count;
            $leave_without_pay_count_total += $leave_without_pay_count;
            $leave_count_total += $leave_count;

            $areaData['leave_types'][] = [
                'leave_type_id' => $leave_type->id,
                'leave_type_name' => $leave_type->name,
                'leave_type_code' => $leave_type->code,
                'leave_type_count' => $leave_count,
            ];
        }

        $areaData['leave_with_pay_count'] = $leave_with_pay_count_total;
        $areaData['leave_without_pay_count'] = $leave_without_pay_count_total;
        $areaData['leave_count'] = $leave_count_total;

        return $areaData;
    }

    private function getEmployeeReport($status, $area, $area_id, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)
    {
        $leave_applications = LeaveApplication::query();

        if ($status) {
            $leave_applications->where('status', $status);
        }

        if ($date_from && $date_to) {
            $leave_applications->whereBetween('date_from', [$date_from, $date_to]);
        }

        if ($sort_by) {
            $leave_applications->orderBy($sort_by);
        }

        if ($limit) {
            $leave_applications->limit($limit);
        }

        $leave_applications = $leave_applications->get();

        $areas = [];

        foreach ($leave_applications as $leave_application) {
            $employee_profile_id = $leave_application->employee_profile_id;

            // Get employee's assigned areas
            $employee = AssignArea::where('employee_profile_id', $employee_profile_id)->first();
            $employee_areas = [];

            if ($employee) {
                switch (strtolower($area)) {
                    case "division":
                        $employee_areas = $this->getEmployeeAreas(Division::class, $area_id, 'division', $area_under);
                        break;
                    case "department":
                        $employee_areas = $this->getEmployeeAreas(Department::class, $area_id, 'department', $area_under);
                        break;
                    case "section":
                        $employee_areas = $this->getEmployeeAreas(Section::class, $area_id, 'section', $area_under);
                        break;
                    case "unit":
                        $employee_areas = $this->getEmployeeAreas(Unit::class, $area_id, 'unit', $area_under);
                        break;
                }
            }

            $areas[] = [
                'leave_application' => $leave_application,
                'employee_areas' => $employee_areas
            ];
        }

        return $areas;
    }

    private function getEmployeeAreas($model, $area_id, $sector, $area_under)
    {
        $areas = [];
        $area = $model::find($area_id);
        if ($area) {
            $areas[] = ['id' => $area_id . '-' . $sector, 'name' => $area->name, 'sector' => ucfirst($sector)];
            if (strtolower($area_under) === 'all') {
                switch ($sector) {
                    case 'division':
                        $departments = Department::where('division_id', $area_id)->get();
                        foreach ($departments as $department) {
                            $areas = array_merge($areas, $this->getEmployeeAreas(Department::class, $department->id, 'department', 'all'));
                        }
                        break;
                    case 'department':
                        $sections = Section::where('department_id', $area_id)->get();
                        foreach ($sections as $section) {
                            $areas = array_merge($areas, $this->getEmployeeAreas(Section::class, $section->id, 'section', 'all'));
                        }
                        break;
                    case 'section':
                        $units = Unit::where('section_id', $area_id)->get();
                        foreach ($units as $unit) {
                            $areas = array_merge($areas, $this->getEmployeeAreas(Unit::class, $unit->id, 'unit', 'all'));
                        }
                        break;
                }
            }
        }
        return $areas;
    }
}
