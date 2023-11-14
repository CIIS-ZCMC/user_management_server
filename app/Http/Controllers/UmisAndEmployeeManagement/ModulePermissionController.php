<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\RequestLogger;
use App\Http\Resources\ModulePermissionResource;
use App\Http\Resources\SystemModulePermissionResource;
use App\Models\ModulePermission;
use App\Models\System;

class ModulePermissionController extends Controller
{
    private $CONTROLLER_NAME = 'Module Permission';
    private $PLURAL_MODULE_NAME = 'module_permissions';
    private $SINGULAR_MODULE_NAME = 'module_permission';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $module_permissions = Cache::remember('module_permissions', $cacheExpiration, function(){
                return ModulePermission::all();
            });

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => ModulePermissionResource::collection($module_permissions),
                'message' => 'Module permission list retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * This End Point expect an System ID
     * it will validate of System ID is associated with any system record.
     * If True then it will retrieve all module permission.
     */
    public function systemModulePermission($id, Request $request)
    {
        try{
            $system = System::find($id);

            if(!$system)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $module_permissions = $system->modules()->with('modulePermissions')->get();

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in retrieving system permissions '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => SystemModulePermissionResource::collection($module_permissions),
                'message' => 'System module permission retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'systemModulePermission', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $module_permission = ModulePermission::find($id);

            if(!$module_permission)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new ModulePermissionResource($module_permission), 'message' => 'Module permission record found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $module_permission = ModulePermission::findOrFail($id);

            if(!$module_permission)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $module_permission->delete();
            
            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Module permission record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
