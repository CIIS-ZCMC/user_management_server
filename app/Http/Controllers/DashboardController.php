<?php

namespace App\Http\Controllers;

use App\Helpers\Helpers;
use App\Models\EmployeeProfile;
use App\Models\EmploymentType;
use App\Models\SystemLogs;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Resources\BirthdayCelebrantResource;
use App\Models\PersonalInformation;

class DashboardController extends Controller
{
    private $CONTROLLER_NAME = 'Dashboard Controller';
    private $PLURAL_MODULE_NAME = 'Announcements';
    private $SINGULAR_MODULE_NAME = 'Announcements';

    /**
     * Current month monitoring of request handle by the system.
     */
    public function systemRequestMonitoring(Request $request)
    {
        try{
            $total_request_this_month = SystemLogs::whereMonth('created_at', Carbon::now()->month)->get();
            $types_of_request = [
                'approve' => $total_request_this_month->where('action', 'approve')->count(),
                'write' => $total_request_this_month->where('action', 'write')->count(),
                'delete' => $total_request_this_month->where('action', 'delete')->count(),
                'update' => $total_request_this_month->where('action', 'update')->count(),
                'request' => $total_request_this_month->where('action', 'request')->count(),
                'import' => $total_request_this_month->where('action', 'import')->count(),
                'download' => $total_request_this_month->where('action', 'download')->count()
            ];

            return response()->json([
                'data' => [
                    'total_request_this_month' => $total_request_this_month,
                    '$types_of_request' => $types_of_request
                ],
                'message' => 'List of birthday celebrant.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'requestMonitoring', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Annual monitoring of request handle by the system.
     */
    public function systemRequestMonitoringAnnually(Request $request)
    {
        try{
            $logs = SystemLogs::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->whereYear('created_at', Carbon::now()->year)
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $monthlyCounts = [];

            // Populate the array with counts for each month
            foreach ($logs as $log) {
                $monthlyCounts[$log->month] = $log->count;
            }
            
            // Fill in months with zero count if they're missing
            for ($month = 1; $month <= 12; $month++) {
                if (!isset($monthlyCounts[$month])) {
                    $monthlyCounts[$month] = 0;
                }
            }
            
            // Sort the array by month
            ksort($monthlyCounts);
            
            // Output the result
            foreach ($monthlyCounts as $month => $count) {
                $monthName = Carbon::create(null, $month, 1)->monthName;
                echo "$monthName: $count\n";
            }

            return response()->json([
                'data' => "",
                'message' => 'List of birthday celebrant.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'requestMonitoring', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function listOfBirthdayCelebrant(Request $request)
    {
        try{
            $personal_informations = PersonalInformation::whereMonth('date_of_birth', now()->format('m'))
                ->whereDay('date_of_birth', now()->format('d'))->get();
            
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
