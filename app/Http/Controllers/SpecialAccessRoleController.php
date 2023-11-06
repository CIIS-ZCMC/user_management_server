<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\RequestLogger;
use App\Http\Resources\SpecialAccessRoleResource;
use App\Models\SpecialAccessRole;
use App\Models\SystemLogs;

class SpecialAccessRoleController extends Controller
{
    private $CONTROLLER_NAME = 'Special Access Role';
    private $PLURAL_MODULE_NAME = 'special access roles';
    private $SINGULAR_MODULE_NAME = 'special access role';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $special_access_roles = SpecialAccessRole::all();

            $this->registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response() -> json(['data' => SpecialAccessRoleResource::collection($special_access_roles)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(SpecialAccessRoleRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }

            $employee_profile = EmployeeProfile::find($cleanData['employee_profile_id']);
            
            if (!$employee_profile) 
            {
                return response()->json(['message' => 'No record found for Employe.'], Response::HTTP_NOT_FOUND);
            }

            $system_role = SystemRole::find($cleanData['system_role_id']);

            if (!$system_role) 
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $special_access_role = SpecialAccessRole::create($cleanData);
            
            $this->registerSystemLogs($request, $special_access_role['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response() -> json(['data' => new SpecialAccessRoleResource($special_access_role),'message' => 'New special role added.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id, Request $request)
    {
        try{
            $special_access_role = SpecialAccessRole::find($id);

            if (!$special_access_role) 
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response() -> json(['data' => new SpecialAccessRoleResource($special_access_role), 'message' => 'Special access role details found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id, Request $request)
    {
        try{
            $special_access_role = SpecialAccessRole::findOrFail($id);

            if(!$special_access_role)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $special_access_role -> delete();

            $this->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response() -> json(['message' => 'Success'], Response::HTTP_OK);
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
