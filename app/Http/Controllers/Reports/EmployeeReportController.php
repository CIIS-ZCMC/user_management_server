<?php

namespace App\Http\Controllers\Reports;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
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
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeesBloodType', $th->getMessage());
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
            } else {
                $employees = EmployeeProfile::select('employee_profiles.*')
                    ->JOIN('assigned_areas as aa', 'aa.employee_profile_id', 'employee_profiles.id')
                    ->JOIN('personal_informations as pi', 'pi.id', 'employee_profiles.personal_information_id')
                    ->where('aa.' . $sector . "_id", $area)
                    ->where('pi.blood_type', $type)
                    ->whereNotIn('employee_profiles.id', [1])
                    ->get();
            }

            return response()->json([
                'data' => EmployeesDetailsReport::collection($employees),
                'count' => COUNT($employees),
                'message' => 'List of employee blood types retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeesByBloodType', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesServiceLength(Request $request)
    {
        try {

            // CHECK SHOW IN EMPLOYEE PROFILE

        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeesServiceLength', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesEmploymentType($employment_type_id, Request $request)
    {
        try {

            $employees = EmployeeProfile::whereNotIn('id', [1])->whereNot('employee_id', NULL)->where('employment_type_id', $employment_type_id)->get();

            return response()->json([
                'data' =>  EmployeesDetailsReport::collection($employees),
                'count' => COUNT($employees),
                'message' => 'List of employees retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeesServiceLength', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesByEmploymentType($employment_type_id, $area_id, $sector, Request $request)
    {
        try {

            $area = strip_tags($area_id);
            $sector = Str::lower(strip_tags($sector));
            $employees = [];

            $employees = EmployeeProfile::select('employee_profiles.*')
                ->JOIN('assigned_areas as aa', 'aa.employee_profile_id', 'employee_profiles.id')
                ->where('aa.' . $sector . "_id", $area)
                ->where('employee_profiles.employment_type_id', $employment_type_id)
                ->whereNotIn('employee_profiles.id', [1])
                ->where('employee_id', '!=', null)
                ->get();

            return response()->json([
                'data' => EmployeesDetailsReport::collection($employees),
                'count' => COUNT($employees),
                'message' => 'List of employees retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeesServiceLength', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesPerJobPosition($designation_id, Request $request)
    {
        try {

            $employees = AssignArea::with('employeeProfile')->where('designation_id', $designation_id)->get();

            return response()->json([
                'data' => EmployeesDetailsReport::collection($employees),
                'count' => COUNT($employees),
                'message' => 'List of employees job position retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeesPerJobPosition', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesPerJobPositionAndArea($designation_id, $area_id, $sector, Request $request)
    {
        try {

            $key = Str::lower(strip_tags($sector));

            $employees = AssignArea::with('employeeProfile')->where('designation_id', $designation_id)->where($key . "_id", $area_id)->get();

            return response()->json([
                'data' => EmployeesDetailsReport::collection($employees),
                'count' => COUNT($employees),
                'message' => 'List of employees job position retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeesPerJobPosition', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
