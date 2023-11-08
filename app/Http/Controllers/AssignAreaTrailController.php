<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Services\RequestLogger;
use App\Http\Resources\AssignAreaResource;
use App\Models\AssignedAreaTrail;
use App\Models\SystemLogs;

class AssignAreaTrail extends Controller
{
    private $CONTROLLER_NAME = 'AssignedArea Module';
    private $PLURAL_MODULE_NAME = 'assign_area_trail modules';
    private $SINGULAR_MODULE_NAME = 'assign_area_trail module';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $assign_area_trails = AssignedAreaTrail::all();

            $this->registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json(['data' => AssignAreaResource::collection($assign_area_trails), 'message' => 'Record of employee assigned area trail retrieved.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByEmployeeID(Request $request)
    {
        try{
            $employe_profile = EmployeeProfile::where('employee_id')->first();

            if(!$employe_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }


            $assign_area_trail = AssignedAreaTrail::where('employee_profile_id',$employe_profile['id'])->first();

            if(!$assign_area_trail)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => AssignAreaResource::collection($assign_area_trail), 'message' => 'Employee assigned area trail found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $assign_area_trail = AssignedAreaTrail::findOrFail($id);

            if(!$assign_area_trail)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new AssignAreaResource($assign_area_trail), 'message' => 'Assigned area trail record found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $assign_area_trail = AssignedAreaTrail::findOrFail($id);

            if(!$assign_area_trail)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $assign_area_trail->delete();
            
            $this->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['data' => 'Assigned area record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function registerSystemLogs($request, $moduleID, $status, $remarks)
    {
        $ip = $request->ip();
        $user = $request->user;
        $assign_area_trail = $request->assign_area_trail;
        list($action, $module) = explode(' ', $assign_area_trail);

        SystemLogs::create([
            'employee_profile_id' => $user->id,
            'module_id' => $moduleID,
            'action' => $action,
            'module' => $module,
            'status' => $status,
            'remarks' => $remarks,
            'ip_assign_area_trail' => $ip
        ]);
    }
}
