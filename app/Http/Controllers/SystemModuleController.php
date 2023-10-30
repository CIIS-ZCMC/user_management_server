<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Services\RequestLogger;
use App\Http\Resources\SystemModuleResource;
use App\Http\Requests\SystemModuleRequest;
use App\Models\SystemModule;
use App\Models\SystemLogs;

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

    public function index(Request $request)
    {
        try{
            $system_modules = SystemModule::all();

            $this->registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json(['data' => SystemModuleResource::collection($system_modules)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(SystemModuleRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if (is_bool($value) || $value === null) {
                    $cleanData[$key] = $value;
                } else {
                    $cleanData[$key] = strip_tags($value);
                }
            }

            $system_module = SystemModule::create($cleanData);

            $this->registerSystemLogs($request, $system_module['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
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
            $permissions = $request->input('permissions');
            
            $failed = [];

            foreach ($permissions as $key => $value) {
                $module_permission = ModulePermission::where('system_module_id',$id)->where('permission_id', $value)->first();

                try{
                    if(!$module_permission){
                        $system_module = SystemModule::find($id);
                        $permission = Permission::find($value);
    
                        $code = $system_module['code'].' '.$permission['action'];
    
                        ModulePermission::create([
                            'system_module_id' => $system_module['id'],
                            'permission_id' => $permission['id'],
                            'code' => $code
                        ]);
                    }

                    $fail_registration = [
                        'permission_id' => $value,
                        'remarks' => 'Already Exist.'
                    ];

                    $failed[] = $fail_registration;
                }catch(\Thorwable $th){
                    $this->requestLogger->errorLog($this->CONTROLLER_NAME,'addPermission', $th->getMessage());

                    $fail_registration = [
                        'permission_id' => $value,
                        'remarks' => 'Something went wrong.'
                    ];

                    $failed[] = $fail_registration;
                }
            }

            if(count($failed) > 0)
            {
                $this->registerSystemLogs($request, $id, true, 'Success in creating module permission but some failed '.$this->SINGULAR_MODULE_NAME.'.');

                return response()->json(['data' => $failed, 'message' => "Some permission did not register."], Response::HTTP_OK);
            }


            $this->registerSystemLogs($request, $id, true, 'Success in creating module permission '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'addPermission', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $system_module = SystemModule::findOrFail($id);

            if(!$system_module)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => $system_module], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
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
                if (is_bool($value) || $value === null) {
                    $cleanData[$key] = $value;
                } else {
                    $cleanData[$key] = strip_tags($value);
                }
            }

            $system_module->update($cleanData);

            $this->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
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

            $system_module->delete();
            
            $this->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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
            'ip_system_module' => $ip
        ]);
    }
}
