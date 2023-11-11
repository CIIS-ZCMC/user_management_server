<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Services\RequestLogger;
use App\Http\Resources\InActiveEmployeeResource;
use App\Http\Resources\AssignAreaTrailResource;
use App\Models\InActiveEmployeeTrail;
use App\Models\AssignAreaTrail;
use App\Models\SystemLogs;

class InActiveEmployeeController extends Controller
{
    private $CONTROLLER_NAME = 'In Active Employee  Module';
    private $PLURAL_MODULE_NAME = 'In active employee modules';
    private $SINGULAR_MODULE_NAME = 'In active employee module';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function index(Request $request)
    {
        try{
            $in_active_employees = InActiveEmployeeTrail::all();

            $this->registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json(['data' => InActiveEmployeeResource::collection($in_active_employees), 'message' => 'Record of in-active employee history retrieved.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByEmployeeID($id, Request $request)
    {
        try{
            $employe_profile = EmployeeProfile::where('employee_id', $id)->first();

            if(!$employe_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $in_active_employee = InActiveEmployeeTrail::where('employee_profile_id',$employe_profile['id'])->first();

            if(!$in_active_employee)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new InActiveEmployeeResource($in_active_employee), 'message' => 'Employee profile found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByAssignedAreaEmployeeID($id, Request $request)
    {
        try{
            $employe_profile = EmployeeProfile::where('employee_id',$id)->first();

            if(!$employe_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $in_active_employee = AssignAreaTrail::where('employee_profile_id',$employe_profile['id'])->get();

            if(!$in_active_employee)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new AssignAreaTrailResource($in_active_employee), 'message' => 'Employee assigned area record trail found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $in_active_employee = InActiveEmployeeTrail::find($id);

            if(!$in_active_employee)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new InActiveEmployeeResource($in_active_employee), 'message' => 'Employee in-active record found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $in_active_employee = InActiveEmployeeTrail::findOrFail($id);

            if(!$in_active_employee)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $in_active_employee->delete();
            
            $this->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['data' => 'Employee in-active record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function registerSystemLogs($request, $moduleID, $status, $remarks)
    {
        $ip = $request->ip();
        $user = $request->user;
        $in_active_employee = $request->in_active_employee;
        list($action, $module) = explode(' ', $in_active_employee);

        SystemLogs::create([
            'employee_profile_id' => $user->id,
            'module_id' => $moduleID,
            'action' => $action,
            'module' => $module,
            'status' => $status,
            'remarks' => $remarks,
            'ip_in_active_employee' => $ip
        ]);
    }
}
