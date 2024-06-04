<?php

namespace App\Http\Controllers;

use App\Helpers\Helpers;
use App\Http\Resources\NotificationResource;
use App\Models\EmployeeLeaveCredit;
use App\Models\EmployeeLeaveCreditLogs;
use App\Models\EmployeeOvertimeCredit;
use App\Models\EmployeeProfile;
use App\Models\EmploymentType;
use App\Models\LeaveType;
use App\Models\Notifications;
use App\Models\SystemLogs;
use App\Models\UserNotifications;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Resources\BirthdayCelebrantResource;
use App\Models\PersonalInformation;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private $CONTROLLER_NAME = 'Dashboard Controller';
    private $PLURAL_MODULE_NAME = 'Announcements';
    private $SINGULAR_MODULE_NAME = 'Announcements';

    /**
     * Current month monitoring of request handle by the system.
     */
    public function test(Request $request)
    {
        try{

            return response()->json(['message' => 'PASSED'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'requestMonitoring', $th->getMessage());
            return response()->json(['data' => $th->getMessage(), 'message' => 'FAILED'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


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
                'message' => 'Current month statistic for request monitoring.'
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
            $logs = SystemLogs::selectRaw('MONTH(created_at) as month, action, COUNT(*) as count')
                ->whereYear('created_at', Carbon::now()->year)
                ->groupBy('month', 'action')
                ->orderBy('month')
                ->orderBy('action')
                ->get();

            $monthlyData = [];

            foreach ($logs as $log) {
                $monthName = Carbon::create(null, $log->month, 1)->monthName;
                
                if (!isset($monthlyData[$monthName])) {
                    $monthlyData[$monthName]['total'] = 0;
                    $monthlyData[$monthName]['action_type'] = [];
                }

                $monthlyData[$monthName]['total'] += $log->count;
                $monthlyData[$monthName]['action_type'][$log->action] = $log->count;
            }

            $result = json_encode($monthlyData, JSON_PRETTY_PRINT);

            return response()->json([
                'data' => $result,
                'message' => 'Annual statistic monitoring.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'requestMonitoring', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function listOfBirthdayCelebrant(Request $request)
    {
        try{
            $personal_informations = PersonalInformation::whereNotIn('id', [1])->whereMonth('date_of_birth', now()->format('m'))
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
