<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Support\Facades\DB;
use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Helpers\ReportHelpers;
use App\Http\Resources\AttendanceReportResource;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\AssignArea;
use App\Models\DailyTimeRecords;
use App\Models\Division;
use App\Models\Department;
use App\Models\DeviceLogs;
use App\Models\EmployeeProfile;
use App\Models\EmployeeSchedule;
use App\Models\EmploymentType;
use App\Models\LeaveType;
use App\Models\Section;
use App\Models\Unit;
use PhpParser\Node\Expr\Assign;;

use App\Http\Controllers\DTR\DeviceLogsController;
use App\Http\Controllers\DTR\DTRcontroller;
use App\Http\Controllers\PayrollHooks\ComputationController;
use App\Models\Devices;
use SebastianBergmann\CodeCoverage\Report\Xml\Report;

/**
 * Class AttendanceReportController
 * @package App\Http\Controllers\Reports
 * 
 * Controller for handling attendance reports.
 */
class AttendanceReportController extends Controller
{
    private $CONTROLLER_NAME = "Attendance Reports";
    protected $helper;
    protected $computed;

    protected $DeviceLog;

    protected $dtr;
    public function __construct()
    {
        $this->helper = new Helpers();
        $this->computed = new ComputationController();
        $this->DeviceLog = new DeviceLogsController();
        $this->dtr = new DTRcontroller();
    }

    private function retrieveAllEmployees()
    {
        // Fetch all assigned areas, excluding where employee_profile_id is 1
        $assign_areas = AssignArea::where('employee_profile_id', '!=', 1)->get();

        // Extract employee profiles from the assigned areas and return as a collection
        return $assign_areas->map(function ($assign_area) {
            return $assign_area->employeeProfile;
        })->flatten();
    }

    private function retrieveEmployees($key, $id)
    {
        // Fetch assigned areas where key matches id and exclude where employee_profile_id is 1
        $assign_areas = AssignArea::where($key, $id)
            ->where('employee_profile_id', '!=', 1)
            ->get();

        // Extract employee profiles from the assigned areas and return as a collection
        return $assign_areas->map(function ($assign_area) {
            return $assign_area->employeeProfile;
        })->flatten();
    }

    public function report(Request $request)
    {
        $area_id = $request->area_id;
        $sector = ucfirst($request->sector);
        $area_under = strtolower($request->area_under);
        $month_of = (int) $request->month_of;
        $year_of = (int) $request->year_of;

        $employees = collect();
        $division_employees = collect();
        $department_employees = collect();
        $section_employees = collect();
        $unit_employees = collect();

        switch ($sector) {
            case 'Division':
                switch ($area_under) {
                    case 'all':
                        $division_employees = $this->retrieveEmployees('division_id', $area_id);
                        $employees = $division_employees;
                        $departments = Department::where('division_id', $area_id)->get();
                        foreach ($departments as $department) {
                            $department_employees = $this->retrieveEmployees('department_id', $department->id);
                            $employees = $employees->merge($department_employees);
                            $sections = Section::where('department_id', $department->id);
                            foreach ($sections as $section) {
                                $section_employees = $this->retrieveEmployees('section_id', $section->id);
                                $employees = $employees->merge($section_employees);
                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $unit_employees = $this->retrieveEmployees('unit_id', $unit->id);
                                    $employees = $employees->merge($unit_employees);
                                }
                            }
                        }
                        $sections = Section::where('division_id', $area_id)->whereNull('department_id')->get();
                        foreach ($sections as $section) {
                            $section_employees = $this->retrieveEmployees('section_id', $section->id);
                            $employees = $employees->merge($section_employees);
                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $unit_employees = $this->retrieveEmployees('unit_id', $unit->id);
                                $employees = $employees->merge($unit_employees);
                            }
                        }
                        break;
                    case 'staff':
                        $division_employees = $this->retrieveEmployees('division_id', $area_id);
                        $employees = $division_employees;
                        break;
                }
                break;
            case 'Department':
                switch ($area_under) {
                    case 'all':
                        $department_employees = $this->retrieveEmployees('department_id', $area_id);
                        $employees = $department_employees;
                        $sections = Section::where('department_id', $area_id)->get();
                        foreach ($sections as $section) {
                            $section_employees = $this->retrieveEmployees('section_id', $section->id);
                            $employees = $employees->merge($section_employees);
                            $units = Unit::where('unit_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $unit_employees = $this->retrieveEmployees('unit_id', $unit->id);
                                $employees = $employees->merge($unit_employees);
                            }
                        }
                        break;
                    case 'staff':
                        $department_employees = $this->retrieveEmployees('department_id', $area_id);
                        $employees = $department_employees;
                        break;
                }
                break;
            case 'Section':
                switch ($area_under) {
                    case 'all':
                        $section_employees = $this->retrieveEmployees('section_id', $area_id);
                        $employees = $section_employees;
                        $units = Unit::where('section_id', $area_id)->get();
                        foreach ($units as $unit) {
                            $unit_employees = $this->retrieveEmployees('unit_id', $unit->id);
                            $employees = $employees->merge($unit_employees);
                        }
                        break;
                    case 'staff':
                        $section_employees = $this->retrieveEmployees('section_id', $area_id);
                        $employees = $section_employees;
                        break;
                }
                break;
            case 'Unit':
                $unit_employees = $this->retrieveEmployees('unit_id', $area_id);
                $employees = $unit_employees;
                break;
            default:
                $employees = $this->retrieveAllEmployees();
        }

        return COUNT($employees);
    }
}
