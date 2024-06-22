<?php

namespace App\Http\Controllers\Reports;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Division;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Section;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class LeaveReportController extends Controller
{
    private $CONTROLLER_NAME = 'Leave Reports';

    public function filterLeave(Request $request)
    {
        try {
            $areas = [];
            $sector = $request->sector;
            $report_format = strtolower($request->report_format);
            $status = $request->status;
            $area_under = strtolower($request->area_under);
            $area_id = $request->area_id;
            $leave_type_ids = $request->leave_type_ids ? explode(',', $request->leave_type_ids) : [];
            $date_from = $request->date_from;
            $date_to = $request->date_to;
            $sort_by = $request->sort_by;
            $limit = $request->limit;

            // check type format
            switch ($report_format) {
                case 'area':
                    $areas = $this->getAreaFilter($sector, $status, $area_under, $area_id, $leave_type_ids, $date_from, $date_to, $sort_by, $limit);
                    break;
                case 'employees':
                    break;
                default:
                    return response()->json(
                        [
                            'data' => $areas,
                            'message' => 'Invalid report format'
                        ],
                        Response::HTTP_OK
                    );
            }

            return response()->json([
                'data' => $areas,
                'message' => 'Successfully retrieved areas.'
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

    private function getAreaFilter($sector, $status, $area_under, $area_id, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)
    {

        $areas = [];
        switch ($sector) {
            case 'division':
                $areas = $this->getDivisionData($area_id, $status, $area_under, $leave_type_ids, $date_from, $date_to, $sort_by,  $limit);
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

    private function result($area, $sector, $status, $leave_type_ids, $date_from, $date_to, $sort_by, $limit)
    {
        $area_data = [
            'id' => $area->id . '-' . $sector,
            'name' => $area->name,
            'sector' => ucfirst($sector),
            'leave_count' => 0, // Initialize leave count here
            'leave_types' => []
        ];

        $leave_count_total = 0;
        $leave_types = LeaveType::whereIn('id', $leave_type_ids)->get();

        $leave_applications = LeaveApplication::whereIn('leave_type_id', $leave_type_ids)->with([
            'employeeProfile' => function ($query) use ($area, $sector) {
                $query->with('assignedAreas', function ($q) use ($area, $sector) {
                    // Ensure correct column name based on your database schema
                    $q->where($sector . '_id', $area->id);
                }); // Eagerly load assigned areas within employeeProfile
            }
        ])->get();

        // Check if any LeaveApplication models were retrieved
        if ($leave_applications->count() > 0) {
            $leave_count = $leave_applications->count();
            $leave_count_total += $leave_count;
        } else {
            // Handle the case where no applications match the criteria
            // (e.g., log a message or set leave_count to 0)
            $leave_count = 0; // Or handle differently based on your needs
        }


        $area_data['leave_count'] = $leave_count_total;

        return $area_data;
    }
}
