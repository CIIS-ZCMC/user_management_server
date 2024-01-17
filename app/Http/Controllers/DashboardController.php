<?php

namespace App\Http\Controllers;

use App\Models\EmployeeProfile;
use App\Models\EmploymentType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Resources\BirthdayCelebrantResource;
use App\Models\PersonalInformation;

class DashboardController extends Controller
{
    public function listOfBirthdayCelebrant(Request $request)
    {
        try{
            $personal_informations = PersonalInformation::whereDate('date_of_birth', now()->format('Y-m-d'))->get();
            
            return response()->json([
                'data' => BirthdayCelebrantResource::collection($personal_informations),
                'message' => 'List of birthday celebrant.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function humanResource(Request $request)
    {
        try{
            $total_employees = EmployeeProfile::all();
            $current_year_employees =  EmployeeProfile::whereYear('created_at', Carbon::now()->year)->get();
            
            $total_employee_per_employment_type = EmploymentType::with('employees')->get();
 
            $employment_types_details = [];

            foreach($total_employee_per_employment_type as $employment_type){
                $employment_types_details[] = [
                    'id' => $employment_type->id,
                    'label' => $employment_type->name,
                    'percentage_of_employees' => (count($employment_type['employees'])/count($total_employees))*100
                ];
            }
            
            return response()->json([
                'data' => [
                    'total_employees' => count($total_employees),
                    'percentage_of_new_employee' => (count($current_year_employees)/count($total_employees))*100,
                    'employment_types_details' => $employment_types_details
                ],
                'message' => 'Human resource dashboard records.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
