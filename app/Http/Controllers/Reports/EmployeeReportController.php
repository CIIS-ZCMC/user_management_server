<?php

namespace App\Http\Controllers\Reports;
use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\DesignationReportResource;
use App\Http\Resources\EmployeesDetailsReport;
use App\Models\AssignArea;
use App\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class EmployeeReportController extends Controller
{
    private $CONTROLLER_NAME = 'Employee Reports';

    
    public function allEmployeesBloodType(Request $request)
    {
        try {
            $employee_profiles = EmployeeProfile::whereNotIn('id', [1])->whereNot('employee_id', NULL)->get();

            return response()->json([
                'data' => EmployeesDetailsReport::collection($employee_profiles),
                'count' => COUNT($employee_profiles),
                'message' => 'List of employee blood types retrieved'
            ], Response::HTTP_OK);
        } catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'employeesBloodType', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesByBloodType($type, $area_id, $sector, Request $request)
    {
        try {
            $area = strip_tags($area_id);
            $sector = Str::lower(strip_tags($sector));
            $employees = [];


            if ($area_id == 'null') {
                $employees = EmployeeProfile::select('employee_profiles.*')
                ->JOIN('assigned_areas as aa', 'aa.employee_profile_id', 'employee_profiles.id')
                ->JOIN('personal_informations as pi', 'pi.id', 'employee_profiles.personal_information_id')
                ->where('pi.blood_type', $type)
                ->whereNotIn('employee_profiles.id', [1])
                ->get();
            } else if ($type == 'null') {
                $employees = EmployeeProfile::select('employee_profiles.*')
                ->JOIN('assigned_areas as aa', 'aa.employee_profile_id', 'employee_profiles.id')
                ->JOIN('personal_informations as pi', 'pi.id', 'employee_profiles.personal_information_id')
                ->where('aa.'.$sector."_id", $area)
                ->whereNotIn('employee_profiles.id', [1])
                ->get();
            } else {
                $employees = EmployeeProfile::select('employee_profiles.*')
                ->JOIN('assigned_areas as aa', 'aa.employee_profile_id', 'employee_profiles.id')
                ->JOIN('personal_informations as pi', 'pi.id', 'employee_profiles.personal_information_id')
                ->where('aa.'.$sector."_id", $area)
                ->where('pi.blood_type', $type)
                ->whereNotIn('employee_profiles.id', [1])
                ->get();
            }
            
            return response()->json([
                'data' => EmployeesDetailsReport::collection($employees),
                'count' => COUNT($employees),
                'message' => 'List of employee blood types retrieved'
            ], Response::HTTP_OK);
        } catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'employeesByBloodType', $th->getMessage());
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
        } catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'employeesCivilStatus', $th->getMessage());
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
                ->where('aa.'.$sector."_id", $area)
                ->whereNotIn('employee_profiles.id', [1])
                ->get();
            } else {
                $employees = EmployeeProfile::select('employee_profiles.*')
                ->JOIN('assigned_areas as aa', 'aa.employee_profile_id', 'employee_profiles.id')
                ->JOIN('personal_informations as pi', 'pi.id', 'employee_profiles.personal_information_id')
                ->where('aa.'.$sector."_id", $area)
                ->where('pi.civil_status', $civilStatus)
                ->whereNotIn('employee_profiles.id', [1])
                ->get();
            }
            
            return response()->json([
                'data' => EmployeesDetailsReport::collection($employees),
                'count' => COUNT($employees),
                'message' => 'List of employee blood types retrieved'
            ], Response::HTTP_OK);
        } catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'employeesByCivilStatus', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesServiceLength(Request $request)
    {
        try {

        // CHECK SHOW IN EMPLOYEE PROFILE

        } catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'employeesServiceLength', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesEmploymentType($employment_type_id, Request $request)
    {
        try {
           
            if ($employment_type_id == 0) {
                $employees = EmployeeProfile::whereNotIn('id', [1])->whereNot('employee_id', NULL)->get();
            } else {
                $employees = EmployeeProfile::whereNotIn('id', [1])->whereNot('employee_id', NULL)->where('employment_type_id', $employment_type_id)->get();
            }


            $regular = EmployeeProfile::whereNotIn('id', [1])->whereNot('employee_id', NULL)->whereIn('employment_type_id', [1,2,3])->get();
            $temporary = EmployeeProfile::whereNotIn('id', [1])->whereNot('employee_id', NULL)->where('employment_type_id', 4)->get();
            $job_order = EmployeeProfile::whereNotIn('id', [1])->whereNot('employee_id', NULL)->where('employment_type_id', 5)->get();
            
            return response()->json([
                'data' =>  EmployeesDetailsReport::collection($employees),
                'count' => [
                    'regular' => COUNT($regular),
                    'permanent' => COUNT($temporary),
                    'job_order' => COUNT($job_order),
                ],
                'message' => 'List of employees retrieved'
            ], Response::HTTP_OK);
        } catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'employeesServiceLength', $th->getMessage());
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
            } else if ($employment_type_id == 'null') {
                $employees = EmployeeProfile::select('employee_profiles.*')
                ->JOIN('assigned_areas as aa', 'aa.employee_profile_id', 'employee_profiles.id')
                ->where('aa.'.$sector."_id", $area)
                ->whereNotIn('employee_profiles.id', [1])
                ->where('employee_id', '!=', null)
                ->get();

                $regular = $employees->filter(function ($row) {
                    return $row->employment_type_id !== 4 && $row->employment_type_id !== 5;
                });
                $temporary =  $employees->filter(function ($row) {
                    return $row->employment_type_id === 4;
                });
                $job_order =$employees->filter(function ($row) {
                    return $row->employment_type_id === 5;
                });
                
            } else {
                $employees = EmployeeProfile::select('employee_profiles.*')
                ->JOIN('assigned_areas as aa', 'aa.employee_profile_id', 'employee_profiles.id')
                ->where('aa.'.$sector."_id", $area)
                ->where('employee_profiles.employment_type_id', $employment_type_id)
                ->whereNotIn('employee_profiles.id', [1])
                ->where('employee_id', '!=', null)
                ->get();

                $regular = $employees->filter(function ($row) {
                    return $row->employment_type_id !== 4 && $row->employment_type_id !== 5;
                });
                $temporary =  $employees->filter(function ($row) {
                    return $row->employment_type_id === 4;
                });
                $job_order =$employees->filter(function ($row) {
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
        } catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'employeesServiceLength', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesPerJobPosition($designation_id, Request $request)
    {
        try {
            
            if ($designation_id == 0) {
                // $designations = EmployeeProfile::select('aa.designation_id as id', 'aa.salary_grade_id', 'aa.salary_grade_step')
                // ->join('assigned_areas as aa', 'aa.employee_profile_id', 'employee_profiles.id')
                // ->whereNotIn('employee_profiles.id', [1])
                // // ->where('employee_id', '!=', null)
                // ->selectRaw('COUNT(*) as employee_count')
                // ->groupBy('id', 'aa.salary_grade_id', 'aa.salary_grade_step') // Add other columns as needed
                // ->get();

                $designations  =  AssignArea::select('assigned_areas.designation_id', 'assigned_areas.salary_grade_id', 'assigned_areas.salary_grade_step')
                ->join('employee_profiles as ep', 'ep.id', 'assigned_areas.employee_profile_id')
                ->whereNotIn('ep.id', [1])
                ->selectRaw('COUNT(*) as employee_count')
                ->groupBy('assigned_areas.designation_id', 'assigned_areas.salary_grade_id', 'assigned_areas.salary_grade_step') // Add other columns as needed
                ->get();

            } else {
                $designations  =  AssignArea::select('assigned_areas.designation_id', 'assigned_areas.salary_grade_id', 'assigned_areas.salary_grade_step')
                ->join('employee_profiles as ep', 'ep.id', 'assigned_areas.employee_profile_id')
                ->whereNotIn('ep.id', [1])
                ->where('assigned_areas.designation_id', $designation_id)
                ->selectRaw('COUNT(*) as employee_count')
                ->groupBy('assigned_areas.designation_id', 'assigned_areas.salary_grade_id', 'assigned_areas.salary_grade_step') // Add other columns as needed
                ->get();


            }

            return response()->json([
                'data'=> DesignationReportResource::collection($designations),
                // 'data' => $designations,
                'count' => COUNT($designations),
                'message' => 'List of employees job position retrieved'
            ], Response::HTTP_OK);
        } catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'employeesPerJobPosition', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesPerJobPositionAndArea($designation_id, $area_id, $sector, Request $request)
    {
        try {

            $key = Str::lower(strip_tags($sector));

            if ($area_id == 'null') {
                $designations  =  AssignArea::select('assigned_areas.designation_id', 'assigned_areas.salary_grade_id', 'assigned_areas.salary_grade_step')
                ->join('employee_profiles as ep', 'ep.id', 'assigned_areas.employee_profile_id')
                ->whereNotIn('ep.id', [1])
                ->where('assigned_areas.designation_id', $designation_id)
                ->selectRaw('COUNT(*) as employee_count')
                ->groupBy('assigned_areas.designation_id', 'assigned_areas.salary_grade_id', 'assigned_areas.salary_grade_step') // Add other columns as needed
                ->get();
                
            } else if ($designation_id == 'null') {
                $designations  =  AssignArea::select('assigned_areas.designation_id', 'assigned_areas.salary_grade_id', 'assigned_areas.salary_grade_step')
                ->join('employee_profiles as ep', 'ep.id', 'assigned_areas.employee_profile_id')
                ->whereNotIn('ep.id', [1])
                ->where("assigned_areas.".$key."_id", $area_id)
                ->selectRaw('COUNT(*) as employee_count')
                ->groupBy('assigned_areas.designation_id', 'assigned_areas.salary_grade_id', 'assigned_areas.salary_grade_step') // Add other columns as needed
                ->get();

                

            } else {
                $designations  =  AssignArea::select('assigned_areas.designation_id', 'assigned_areas.salary_grade_id', 'assigned_areas.salary_grade_step')
                ->join('employee_profiles as ep', 'ep.id', 'assigned_areas.employee_profile_id')
                ->whereNotIn('ep.id', [1])
                ->where('assigned_areas.designation_id', $designation_id)
                ->where("assigned_areas.".$key."_id", $area_id)
                ->selectRaw('COUNT(*) as employee_count')
                ->groupBy('assigned_areas.designation_id', 'assigned_areas.salary_grade_id', 'assigned_areas.salary_grade_step') // Add other columns as needed
                ->get();

            }

            return response()->json([
                'data' => DesignationReportResource::collection($designations),
                'count' => COUNT($designations),
                'message' => 'List of employees job position retrieved'
            ], Response::HTTP_OK);
        } catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'employeesPerJobPosition', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}