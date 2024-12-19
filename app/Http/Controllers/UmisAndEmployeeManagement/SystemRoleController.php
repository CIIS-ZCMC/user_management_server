<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\NewRolePermissionRequest;
use App\Http\Resources\DesignationAssignedSystemRolesResource;
use App\Http\Resources\EmployeeWithSpecialAccessResource;
use App\Http\Resources\PositionSystemRoleOnlyResource;
use App\Http\Resources\SpecialAccessRoleAssignResource;
use App\Models\Designation;
use App\Models\EmployeeProfile;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SpecialAccessRole;
use App\Models\SystemModule;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\Helpers;
use App\Http\Requests\SystemRoleRequest;
use App\Http\Resources\SystemRoleResource;
use App\Http\Resources\SystemRolePermissionsResource;
use App\Models\RoleModulePermission;
use App\Models\ModulePermission;
use App\Models\SystemRole;
use App\Models\System;
use Illuminate\Support\Facades\DB;

class SystemRoleController extends Controller
{
    private $CONTROLLER_NAME = 'System Role';
    private $PLURAL_MODULE_NAME = 'system roles';
    private $SINGULAR_MODULE_NAME = 'system role';

    public function index(Request $request)
    {
        try {
            $systemRoles = SystemRole::all();

            return response()->json([
                'data' => SystemRoleResource::collection($systemRoles),
                'message' => 'System role list retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /** API [system-roles-rights/{id}] */
    public function systemRoleAccessRights($id, Request $request)
    {
        try {
            $systemRoles = SystemRole::find($id);

            if(!$systemRoles){
                return response()->json(['message' => "No record found."], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => $this->buildRoleDetails($systemRoles),
                'message' => 'System role list retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'systemRoleAccessRights', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /** API [system-roles-rights/{id}]  UPDATE System role access rights */
    public function systemRoleAccessRightsUpdate($id, Request $request)
    {
        try {
            $system_roles = SystemRole::find($id);

            if(!$system_roles){
                return response()->json(['message' => "No record found."], Response::HTTP_NOT_FOUND);
            }

            RoleModulePermission::where('system_role_id', $system_roles->id)->delete();

            $failed = [];

            foreach($request->modules as $module){
                foreach($module['permissions'] as $permission){
                    $module_permission = ModulePermission::where('system_module_id', $module['module_id'])
                        ->where('permission_id', $permission)->first();

                    if(!$module_permission){
                        $failed[] = [
                            'module_id' => $module['module_id'],
                            'permission_id' => $permission,
                            'reason' => "Failed module permission doesn't exist." 
                        ];
                        continue;
                    }
                    
                    RoleModulePermission::create([
                        'system_role_id' => $id,
                        'module_permission_id' => $module_permission->id
                    ]);
                }
            }

            if(count($failed) > 0){
                return response()->json([
                    "data" => $this->buildRoleDetails($system_roles),
                    "failed" => $failed,
                    "message" => "System role rights has been successfully updated some had failed."
                ],Response::HTTP_OK);
            }

            return response()->json([
                "data" => $this->buildRoleDetails($system_roles),
                'message' => 'System role rights has been successfully updated'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'systemRoleAccessRightsUpdate', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    private function buildRoleDetails($system_role)
    {
        $modules = [];

        $role_module_permissions = $system_role->roleModulePermissions;

        foreach ($role_module_permissions as $role_module_permission) {
            $module_id = $role_module_permission->modulePermission->module->id;
            $module_name = $role_module_permission->modulePermission->module->name;
            $module_code = $role_module_permission->modulePermission->module->code;
            $permission_action = $role_module_permission->modulePermission->permission->action;
            $permission = $role_module_permission->modulePermission->permission;

            if (!isset($modules[$module_name])) {
                $modules[$module_name] = ['id' => $module_id,'name' => $module_name, 'code' => $module_code, 'permissions' => []];
            }

            if (!in_array($permission_action, $modules[$module_name]['permissions'])) {
                $modules[$module_name]['permissions'][] = [
                    'id' => $permission->id,
                    'action' => $permission->action,
                    'name' => $permission->name
                ];
            }
        }

        return [
            'id' => $system_role->id,
            'name' => $system_role->role->name,
            'modules' => array_values($modules), // Resetting array keys
        ];
    }

    public function employeesWithSpecialAccess(Request $request)
    {
        try {
            $employees = EmployeeProfile::whereHas('specialAccessRole')->get();

            return response()->json([
                'data' => EmployeeWithSpecialAccessResource::collection($employees),
                'message' => 'Special access role assign successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'addSpecialAccessRole', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeeWithSpecialAccess($id, Request $request)
    {
        try {
            $employee = EmployeeProfile::find($id);

            return response()->json([
                'data' => PositionSystemRoleOnlyResource::collection($employee->specialAccessRole),
                'message' => 'Employee Special access role assign successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'addSpecialAccessRole', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function designationsWithSystemRoles(Request $request)
    {
        try {
            $employees = Designation::all();

            return response()->json([
                'data' => DesignationAssignedSystemRolesResource::collection($employees),
                'message' => 'Special access role assign successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'addSpecialAccessRole', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function store($id, SystemRoleRequest $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }
            
            $system = System::find($id);

            if (!$system) {
                return response()->json(['message' => 'No record found for system id ' . $id . " ."], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];
            $cleanData['system_id'] = $system['id'];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $check_if_exist =  System::where('role_id', $cleanData['role_id'])->where('system_id', $cleanData['system_id'])->first();

            if($check_if_exist !== null){
                return response()->json(['message' => 'System role already exist.'], Response::HTTP_FORBIDDEN);
            }

            $systemRole = SystemRole::create($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json([
                'data' => new SystemRoleResource($systemRole),
                "message" => 'New system role added'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function addSpecialAccessRole($id, Request $request)
    {
        try {
            $failed = [];
            $special_access_roles = [];
            $system_role = SystemRole::find($id);

            if (!$system_role) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach ($request->employees as $employee_id) {
                $employee_profile_id = strip_tags($employee_id);

                $special_access_role = SpecialAccessRole::create([
                    'system_role_id' => $system_role->id,
                    'employee_profile_id' => $employee_profile_id
                ]);

                if (!$special_access_role) {
                    $failed[] = $employee_id;
                    continue;
                }
                $special_access_roles[] = $special_access_role;
            }

            if (count($failed) !== 0) {
                return response()->json([
                    'data' => SpecialAccessRoleAssignResource::collection($special_access_roles),
                    'message' => "Some employee failed to assign special access."
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            Helpers::registerSystemLogs($request, null, true, 'Success in assigning special access role.' . $this->PLURAL_MODULE_NAME . '.');

            return response()->json([
                'data' => SpecialAccessRoleAssignResource::collection($special_access_roles),
                'message' => 'Special access role assign successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'addSpecialAccessRole', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function registerNewRoleAndItsPermission($id, NewRolePermissionRequest $request)
    {
        try {
            $success_data = [];

            DB::beginTransaction();
            $system = System::find($id);

            if (!$system) {
                return response()->json(['message' => 'No record found for system id ' . $id . " ."], Response::HTTP_NOT_FOUND);
            }

            foreach ($request->roles as $role_value) {
                $role = Role::where('name', $role_value['role_name'])->first();

                if (!$role) continue;

                $system_role = SystemRole::create([
                    'system_id' => $system->id,
                    'role_id' => $role->id
                ]);

                $result['system_role'] = $system_role;
                $result['permissions'] = [];

                foreach ($role_value['modules'] as $module_value) {
                    foreach ($module_value['permissions'] as $permission_value) {
                        $module_permission = null; 
                        $permission_id = $permission_value;

                        // Check if the existing module and permission has relation
                        $is_exist = ModulePermission::where('system_module_id', $module_value['module_id'])
                            ->where('permission_id', $permission_id)->first();

                        // If does not exist register a new module permission data
                        if (!$is_exist){
                            $module = SystemModule::find($module_value['module_id']);
                            $permission = Permission::find($permission_id);

                            $module_permission = ModulePermission::create([
                                "active" => 1,
                                "code" => $module['code']." ".$permission['action'],
                                "system_module_id" => $module['id'],
                                "permission_id" => $permission['id']
                            ]);
                        }else{
                            // Assign the existing module permission on variable to be use
                            $module_permission = $is_exist;
                        }
                        
                        $role_module_permission = RoleModulePermission::create([
                            'module_permission_id' => $module_permission['id'],
                            'system_role_id' => $system_role->id
                        ]);

                        $result['permissions'][] = $role_module_permission;
                    }
                }
                $success_data[] = $result;
            }

            Helpers::registerSystemLogs($request, $id, true, 'Success in creating system role, attaching permissions ' . $this->SINGULAR_MODULE_NAME . '.');
            DB::commit();

            return response()->json([
                'data' => $success_data,
                "message" => 'System roles and access rights registered successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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
        try {
            $failed = [];
            $module_permissions = $request->input('module_permissions');

            $system_role = SystemRole::find($id);

            $new_record = [];

            if (!$system_role) {
                return response()->json(['message' => 'No record found for system role id ' . $id . ' .'], Response::HTTP_NOT_FOUND);
            }

            foreach ($module_permissions as $key => $value) {
                if (!is_int($value)) {
                    $failed_request = [
                        'module_permission_id' => $value,
                        'remarks' => 'Invalid type.'
                    ];

                    $failed[] = $failed_request;
                    continue;
                }

                $module_permission = ModulePermission::find($value);

                try {
                    if ($module_permission) {
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
                } catch (\Throwable $th) {

                    $failed_request = [
                        'module_permission_id' => $value,
                        'remarks' => 'Something went wrong.'
                    ];

                    $failed[] = $failed_request;
                }
            }

            if (count($failed) === count($module_permissions)) {
                return response()->json(['message' => 'Failed to register all module permissions'], Response::HTTP_OK);
            }

            if (count($failed) > 0) {
                return response()->json(['data' => $new_record, 'message' => 'Failed to register some permission module.'], Response::HTTP_OK);
            }

            Helpers::registerSystemLogs($request, $id, true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(["message" => 'New permission added to system role.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'addModulePermission', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * This End Point will retrieve all Module Permission or System Permission to a target System Role.
     * First it will retrieve all Role Module Permission it has then retrieve al Module Permission or System Permission for each
     * Role Module Permission.
     */
    public function findSystemRolePermissions($id, Request $request)
    {
        try {
            $system_role = SystemRole::find($id);

            if (!$system_role) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $module_permissions = $system_role->roleModulePermissions;

            return response()->json([
                'data' => SystemRolePermissionsResource::collection($module_permissions),
                'message' => 'System role record retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'systemRolePermission', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id, Request $request)
    {
        try {
            $systemRole = SystemRole::find($id);

            if (!$systemRole) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $data = [
                'system_role' => new SystemRoleResource($systemRole),
                'modules' => $this->buildRoleDetails($systemRole)
            ];

            return response()->json([
                'data' => $data,
                'message' => 'System role record retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function update($id, SystemRoleRequest $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $systemRole = SystemRole::find($id);

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $systemRole->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json([
                'data' => new SystemRoleResource($systemRole),
                "message" => 'System Role record updated'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id, Request $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $systemRole = SystemRole::findOrFail($id);

            if (!$systemRole) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $role_module_permission = $systemRole->roleModulePermissions;

            if (count($role_module_permission) > 0) {
                return response()->json(['message' => "Some data is using the system role deletion is prohibited."], Response::HTTP_BAD_REQUEST);
            }

            $systemRole->delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(['message' => 'System role record deleted.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
