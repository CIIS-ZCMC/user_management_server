<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\RequestLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\SystemRoleRequest;
use App\Http\Resources\SystemRoleResource;
use App\Models\SystemRole;
use App\Models\SystemLogs;

class SystemRoleController extends Controller
{   
    private $CONTROLLER_NAME = 'System Role';
    private $PLURAL_MODULE_NAME = 'system roles';
    private $SINGULAR_MODULE_NAME = 'system role';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }
    
    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $systemRoles = Cache::remember('system_roles', $cacheExpiration, function(){
                return SystemRole::all();
            });

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');
            
            return response() -> json(['data' => SystemRoleResource::collection($systemRoles)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(SystemRoleRequest $request)
    {
        try{  
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }

            $systemRole = SystemRole::create($cleanData);

            $this->registerSystemLogs($request, $id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json(['data' => "Success"], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function show($id, Request $request)
    {
        try{
            $systemRole = SystemRole::find($id);

            if(!$systemRole){
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json(['data' => $systemRole], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function update($id, SystemRoleRequest $request)
    {
        try{
            $systemRole = SystemRole::find($id);

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }

            $systemRole -> update($cleanData);
            
            $this->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json(['data' => "Success"], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function destroy($id, Request $request)
    {
        try{
            $systemRole = SystemRole::findOrFail($id);

            if(!$systemRole){
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $data -> delete();

            $this->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function registerSystemLogs($request, $moduleID, $status, $remarks)
    {
        $ip = $request->ip();
        $user = $request->user;
        $permission = $request->permission;
        list($action, $module) = explode(' ', $permission);

        SystemLogs::create([
            'employee_profile_id' => $user->id,
            'module_id' => $moduleID,
            'action' => $action,
            'module' => $module,
            'status' => $status,
            'remarks' => $remarks,
            'ip_address' => $ip
        ]);
    }
}
