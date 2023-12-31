<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\NewRolePermissionRequest;
use App\Http\Resources\DesignationAssignedSystemRolesResource;
use App\Http\Resources\DesignationWithSystemRoleResource;
use App\Http\Resources\EmployeeWithSpecialAccessResource;
use App\Http\Resources\SpecialAccessRoleAssignResource;
use App\Models\Designation;
use App\Models\EmployeeProfile;
use App\Models\Role;
use App\Models\SpecialAccessRole;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\RequestLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Requests\SystemRoleRequest;
use App\Http\Resources\SystemRoleResource;
use App\Http\Resources\SystemRolePermissionsResource;
use App\Models\RoleModulePermission;
use App\Models\ModulePermission;
use App\Models\SystemRole;
use App\Models\System;

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

            $systemRoles = SystemRole::all();

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');
            
            return response() -> json([
                'data' => SystemRoleResource::collection($systemRoles),
                'message' => 'System role list retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function employeesWithSpecialAccess(Request $request)
    {
        try{
            $employees = EmployeeProfile::all();

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in fetching employees with special access role.'.$this->PLURAL_MODULE_NAME.'.');
            
            return response() -> json([
                'data' => EmployeeWithSpecialAccessResource::collection($employees),
                'message' => 'Special access role assign successfully.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'addSpecialAccessRole', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function designationsWithSystemRoles(Request $request)
    {
        try{
            $employees = Designation::all();

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in assigning special access role.'.$this->PLURAL_MODULE_NAME.'.');
            
            return response() -> json([
                'data' => DesignationAssignedSystemRolesResource::collection($employees),
                'message' => 'Special access role assign successfully.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'addSpecialAccessRole', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    
    public function store($id, SystemRoleRequest $request)
    {
        try{  
            $system = System::find($id);

            if(!$system)
            {
                return response()->json(['message' => 'No record found for system id '.$id." ."], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];
            $cleanData['system_id'] = $system['id'];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }

            $systemRole = SystemRole::create($cleanData);

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json([
                'data' => new SystemRoleResource($systemRole),
                "message" => 'New system role added'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function addSpecialAccessRole($id, Request $request)
    {
        try{
            $failed = [];
            $special_access_roles = [];
            $system_role = SystemRole::find($id);

            if(!$system_role){
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach($request->employees as $employee_id){
                $employee_profile_id = strip_tags($employee_id); 

                $special_access_role = SpecialAccessRole::create([
                    'system_role_id' => $system_role->id,
                    'employee_profile_id' => $employee_profile_id 
                ]);

                if(!$special_access_role){
                    $failed[] = $employee_id;
                    continue;
                }
                $special_access_roles[] = $special_access_role;
            }            

            if(count($failed) !== 0){
                return response()->json([
                    'data' => SpecialAccessRoleAssignResource::collection($special_access_roles),
                    'message' => "Some employee failed to assign special access."
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in assigning special access role.'.$this->PLURAL_MODULE_NAME.'.');
            
            return response() -> json([
                'data' => SpecialAccessRoleAssignResource::collection($special_access_roles),
                'message' => 'Special access role assign successfully.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'addSpecialAccessRole', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
    public function registerNewRoleAndItsPermission($id, NewRolePermissionRequest $request)
    {
        try{
            $success_data = [];

            $system = System::find($id);

            if(!$system)
            {
                return response()->json(['message' => 'No record found for system id '.$id." ."], Response::HTTP_NOT_FOUND);
            }

            foreach($request->roles as $role_value){
                $role = Role::find($role_value['role_id']);

                if(!$role) continue;

                $system_role = SystemRole::create([
                    'system_id' => $system->id,
                    'role_id' => $role->id
                ]);

                $result['system_role'] = $system_role;
                $result['permissions'] = [];
                
                foreach($role_value['modules'] as $module_value){
                    foreach($module_value['permissions'] as $permission_value){
                        $module_permission = ModulePermission::where('system_module_id', $module_value['module_id'])
                            ->where('permission_id', $permission_value)->first();

                        if(!$module_permission) break;

                        $role_module_permission = RoleModulePermission::create([
                            'module_permission_id' => $module_permission->id,
                            'system_role_id' => $system_role->id 
                        ]);

                        $result['permissions'][] = $role_module_permission;
                    }
                }
                $success_data[] = $result;
            }

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in creating system role, attaching permissions '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json([
                'data' => $success_data,
                "message" => 'System roles and access rights registered successfully.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * This module will attach a Module Permission or System Permission in System Role
     * Validate if system exist, and if so iterate to all IDs and register as RoleModulePermission
     * if some failed record id that failed and remarks as something went wrong and return the IDs that failed.
     * Validate if ID given is an integer type if not then considered as failed.
     */
    public function addRolePermission($id, Request $request)
    {
        try{
            $failed = [];
            $module_permissions = $request->input('module_permissions');

            $system_role = SystemRole::find($id);

            $new_record = [];

            if(!$system_role){
                return response()->json(['message' => 'No record found for system role id '.$id.' .'], Response::HTTP_NOT_FOUND);
            }

            foreach ($module_permissions as $key => $value) {
                if(!is_int($value)){
                    $failed_request = [
                        'module_permission_id' => $value,
                        'remarks' => 'Invalid type.'
                    ];

                    $failed[] = $failed_request;
                    continue;
                }

                $module_permission = ModulePermission::find($value);

                try{
                    if($module_permission){
                        $new_record[] = RoleModulePermission::create([
                            'module_permission_id' => $value,
                            'system_role_id' => $id
                        ]);
                        continue;
                    }

                    $failed_request = [
                        'module_permission_id' => $value,
                        'remarks' => 'Already Exist.'
                    ];

                    $failed[] = $failed_request;
                }catch(\Throwable $th){

                    $failed_request = [
                        'module_permission_id' => $value,
                        'remarks' => 'Something went wrong.'
                    ];

                    $failed[] = $failed_request;
                }
            }

            if(count($failed) === count($module_permissions)){
                return response()->json(['message' => 'Failed to register all module permissions'], Response::HTTP_OK);
            }

            if(count($failed) > 0){
                return response()->json(['data' => $new_record,'message' => 'Failed to register some permission module.'], Response::HTTP_OK);
            }

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json(["message" => 'New permission added to system role.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'addModulePermission', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * This End Point will retrieve all Module Permission or System Permission to a target System Role.
     * First it will retrieve all Role Module Permission it has then retrieve al Module Permission or System Permission for each
     * Role Module Permission.
     */
    public function findSystemRolePermissions($id, Request $request)
    {
        try{
            $system_role = SystemRole::find($id);

            if(!$system_role){
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $module_permissions = $system_role->roleModulePermissions;

            return response()->json([
                'data' => SystemRolePermissionsResource::collection($module_permissions),
                'message' => 'System role record retrieved'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'systemRolePermission', $th->getMessage());
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

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json([
                'data' => new SystemRoleResource($systemRole),
                'message' => 'System role record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function update($id, SystemRoleRequest $request)
    {
        try{
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $systemRole = SystemRole::find($id);

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }

            $systemRole -> update($cleanData);
            
            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json([
                'data' => new SystemRoleResource($systemRole),
                "message" => 'System Role record updated'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function destroy($id, PasswordApprovalRequest $request)
    {
        try{
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $systemRole = SystemRole::findOrFail($id);

            if(!$systemRole){
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $role_module_permission = $systemRole->roleModulePermissions;

            if(count($role_module_permission) > 0)
            {
                return response()->json(['message' => "Some data is using the system role deletion is prohibited."], Response::HTTP_BAD_REQUEST);
            }

            $systemRole -> delete();

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json(['message' => 'System role record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
