<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\RequestLogger;
use App\Http\Resources\ModulePermissionResource;
use App\Http\Resources\SystemModuleResource;
use App\Http\Requests\SystemModuleRequest;
use App\Models\ModulePermission;
use App\Models\Permission;
use App\Models\System;
use App\Models\SystemModule;

class SystemModuleController extends Controller
{
    private $CONTROLLER_NAME = 'System Module';
    private $PLURAL_MODULE_NAME = 'system modules';
    private $SINGULAR_MODULE_NAME = 'system module';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function index($id, Request $request)
    {
        try{
            $system_modules = SystemModule::where('system_id', $id)->get();

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in system module fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => SystemModuleResource::collection($system_modules),
                'message' => 'System module list retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store($id, SystemModuleRequest $request)
    {
        try{
            $system = System::find($id);

            if(!$system)
            {
                return response()->json(['messsage' => 'No system found for id '.$id. ' .'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];
            $cleanData['system_id'] = $system->id;

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $system_module = SystemModule::create($cleanData);

            $this->requestLogger->registerSystemLogs($request, $system_module['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new SystemModuleResource($system_module),
                'message' => 'New system module record added'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * This request expect an array of Permission ID
     * In Which it will Iterate Each ID and Validate if Module Permission already exist
     * base on Module ID and Permission ID
     * if already exist it will considered as failed registration with remarks of already registered
     * if encountered an error it is also a failed registration but logging the error in error logs for
     * debugging later on with also remarks of Something went wrong for client side.
     * if it doesn't exist retrieving the system_module and permission
     * will trigger and validating of there is an record with its ID given 
     * if has record then will register new Module Permission with code of combination of System Module code and Permission code
     * in which it is applied in the API END point for authorization purposes.
     */
    public function addPermission($id, Request $request)
    {
        try{
            $new_module_permission = [];

            $system_module = SystemModule::find($id);
            
            if(!$system_module)
            {
                return response()->json(['message' => 'No record found for system module id '.$id.' .'], Response::HTTP_NOT_FOUND);
            }

            $permissions = $request->input('permissions');
            
            $failed = [];

            foreach ($permissions as $key => $value) {
                $module_permission = ModulePermission::where('system_module_id',$id)->where('permission_id', $value)->first();

                try{
                    $permission = Permission::find($value);

                    if(!$permission)
                    {
                        $fail_registration = [
                            'permission_id' => $value,
                            'remarks' => 'No record found for permission with id '.' .'
                        ];

                        $failed[] = $fail_registration;
                        continue;
                    }

                    if(!$module_permission){
    
                        $code = $system_module['code'].' '.$permission['action'];
    
                        $new_module_permission[] =  ModulePermission::create([
                            'system_module_id' => $system_module['id'],
                            'permission_id' => $permission['id'],
                            'code' => $code
                        ]);
                        continue;
                    }

                    $fail_registration = [
                        'permission_id' => $value,
                        'remarks' => 'Already Exist.'
                    ];

                    $failed[] = $fail_registration;
                }catch(\Throwable $th){
                    $this->requestLogger->errorLog($this->CONTROLLER_NAME,'addPermission', $th->getMessage());

                    $fail_registration = [
                        'permission_id' => $value,
                        'remarks' => 'Something went wrong.'
                    ];

                    $failed[] = $fail_registration;
                }
            }

            if(count($failed) === count($permissions))
            {
                $this->requestLogger->registerSystemLogs($request, $id, false, 'Failed in creating module permission '.$this->SINGULAR_MODULE_NAME.'.');

                return response()->json(['message' => "Failed to register all permissions for this module id ".$id." ."], Response::HTTP_OK);
            }

            if(count($failed) > 0)
            {
                $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in creating module permission but some failed '.$this->SINGULAR_MODULE_NAME.'.');

                return response()->json(['data' => ModulePermissionResource::collection($new_module_permission) , 'failed'  => $failed, 'message' => "Some permission did not register."], Response::HTTP_OK);
            }


            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in creating module permission '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => ModulePermissionResource::collection($new_module_permission),
                'message' => 'New permission added to system module.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'addPermission', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $system_module = SystemModule::find($id);

            if(!$system_module)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new SystemModuleResource($system_module),
                'message' => 'System module record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, SystemModuleRequest $request)
    {
        try{
            $system_module = SystemModule::find($id);

            if(!$system_module)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $system_module->update($cleanData);

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new SystemModuleResource($system_module),
                'message' => 'System module record updated'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $system_module = SystemModule::findOrFail($id);

            if(!$system_module)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $module_permissions = $system_module->modulePermissions;

            if(count($module_permissions)>0){    
                $this->requestLogger->registerSystemLogs($request, $id, true, 'Failed in deleting cause this data in use with by other record '.$this->SINGULAR_MODULE_NAME.'.');
                return response()->json(['message' => "There are data using this system module, can't, delete this record."], Response::HTTP_OK); 
            }

            $system_module->delete();
            
            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'System module record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}