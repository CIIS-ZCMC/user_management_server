<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\PasswordApprovalRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Requests\DesignationRequest;
use App\Http\Resources\DesignationResource;
use App\Http\Resources\DesignationWithSystemRoleResource;
use App\Http\Resources\DesignationTotalEmployeeResource;
use App\Http\Resources\DesignationTotalPlantillaResource;
use App\Http\Resources\DesignationEmployeesResource;
use App\Http\Resources\DesignationwPlantillaResource;
use App\Models\Designation;
use App\Models\PositionSystemRole;

class DesignationController extends Controller
{
    private $CONTROLLER_NAME = 'Designation';
    private $PLURAL_MODULE_NAME = 'designations';
    private $SINGULAR_MODULE_NAME = 'designation';


    public function test(Request $request)
    {
        $name = Helpers::checkSaveFile($request->attachment, 'test/profiles');
        return response()->json(['data' => $name], Response::HTTP_OK);
    }

    public function index(Request $request)
    {
        try {
            $cacheExpiration = Carbon::now()->addDay();

            $designations = Cache::remember('designations', $cacheExpiration, function () {
                return Designation::all();
            });

            return response()->json([
                'data' => DesignationResource::collection($designations),
                'message' => 'Designation records retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function fetchwPlantilla(Request $request)
    {
        try {
            $designations = Designation::whereIn('id', function ($query) {
                $query->select('designation_id')->from('plantillas');
            })->get();

            return response()->json([
                'data' => DesignationResource::collection($designations),
                'message' => 'Designation records retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'fetchwPlantilla', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function totalEmployeePerDesignation(Request $request)
    {
        try {
            $total_employee_per_designation = Designation::withCount('assignAreas')->get();

            return response()->json([
                'data' => DesignationTotalEmployeeResource::collection($total_employee_per_designation),
                'message' => 'Total employee per disignation retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'totalEmployeePerDesignation', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function totalPlantillaPerDesignation(Request $request)
    {
        try {
            $total_plantilla_per_designation = Designation::withCount('plantilla')->get();

            return response()->json([
                'data' => DesignationTotalPlantillaResource::collection($total_plantilla_per_designation),
                'message' => 'Total plantilla per designation retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'totalEmployeePerDesignation', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeeListInDesignation($id, Request $request)
    {
        try {
            $employee_with_designation = Designation::with('assignAreas.employeeProfile')->findOrFail($id);

            return response()->json([
                'data' => new DesignationEmployeesResource($employee_with_designation),
                'message' => 'Designation employee list retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeesOfSector', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(DesignationRequest $request)
    {
        try {
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $check_if_exist =  Designation::where('name', $cleanData['name'])->where('code', $cleanData['code'])->first();

            if ($check_if_exist !== null) {
                return response()->json(['message' => 'Department already exist.'], Response::HTTP_FORBIDDEN);
            }

            $designation = Designation::create($cleanData);

            Helpers::registerSystemLogs($request, null, true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json([
                'data' => new DesignationResource($designation),
                'message' => 'New designation added.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function assignSystemRole(Request $request)
    {
        try {
            $failed = [];
            $designations = [];
            $designation_names = [];

            foreach ($request->designations as $id) {
                $designation_id = strip_tags($id);
                $designation = Designation::find($designation_id);

                if (!$designation) {
                    $failed[] = $id;
                    continue;
                }

                foreach ($request->system_roles as $system_role) {
                    $system_role_id = strip_tags($system_role);

                    PositionSystemRole::create([
                        'system_role_id' => $system_role_id,
                        'designation_id' => $designation->id
                    ]);
                }

                Cache::forget($designation->name);
                if (!in_array($designation->name, $designation_names)) {
                    $designation_names[] = $designation->name;
                }
                $designations[] = $designation;
            }

            $this->buildSidebarDetails($designation);


            if (count($failed) > 0) {
                return response()->json([
                    'data' => DesignationWithSystemRoleResource::collection($designations),
                    'message' => "Some designation failed to assign system role."
                ], Response::HTTP_OK);
            }

            Helpers::registerSystemLogs($request, null, true, 'Success in assigned system role to designation ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json([
                'data' => DesignationWithSystemRoleResource::collection($designations),
                'message' => 'System role successfully assign to designation.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'assignSystemRole', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    private function buildSidebarDetails($designation)
    {
        $side_bar_details['designation_id'] = $designation['id'];
        $side_bar_details['designation_name'] = $designation['name'];
        $side_bar_details['system'] = [];

        /**
         * Relation of table
         * position_sytem_role
         * sytemRole
         * system
         * roleModulePermissions
         * modulePermission
         * systemModule
         * permission
         */
        $position_system_roles = PositionSystemRole::with([
            'systemRole' => function ($query) {
                $query->with([
                    'system',
                    'roleModulePermissions' => function ($query) {
                        $query->with([
                            'modulePermission' => function ($query) {
                                $query->with(['module', 'permission']);
                            }
                        ]);
                    },
                ]);
            }
        ])->where('designation_id', $designation['id'])->get();

        if (count($position_system_roles) !== 0) {
            /**
             * Convert to meet sidebar data format.
             * Iterate to every system roles.
             */
            foreach ($position_system_roles as $key => $position_system_role) {
                $system_exist = false;
                $system_role = $position_system_role['systemRole'];

                /**
                 * If side bar details system array is empty
                 */
                if (!$side_bar_details['system']) {
                    $side_bar_details['system'][] = $this->buildSystemDetails($system_role);
                    continue;
                }

                foreach ($side_bar_details['system'] as $key => $system) {
                    if ($system['id'] === $system_role->system['id']) {
                        $system_exist = true;

                        $build_role_details = $this->buildRoleDetails($system_role);
    
                        /** Convert the array of object to array of string retrieving only the names of role */
                        $new_array_roles = collect($system['roles'])->pluck('name')->toArray();

                        /** Validate if the role exist in the array of not then a new role will be added to system roles. */
                        if (!in_array($build_role_details['name'], $new_array_roles)) {
                            $system['roles'][] = [
                                'id' => $build_role_details['id'],
                                'name' => $build_role_details['name'],
                            ];
                        }

                        // Convert the array of objects to a collection
                        $collection = collect($system['modules']);

                        foreach($build_role_details['modules'] as $role_module){
                            // Find the module with code "UMIS-SM" and modify it in the collection
                            $collection->transform(function ($module) use ($role_module) {
                                if ($module['code'] === $role_module['code']) {
                                    /** Iterate new permissions of other system role */
                                    foreach($role_module['permissions'] as $permission){
                                        /** If permission doesn't exist in current module then it will be added to the module permissions.*/
                                        if (!in_array($permission, $module['permissions'])) {
                                            $module['permissions'][] = $permission;
                                        }
                                    }
                                }
                                return $module;
                            });
                        }
                        break;
                    }
                }

                if (!$system_exist) {
                    $side_bar_details['system'][] = $this->buildSystemDetails($system_role);
                }
            }

            $cacheExpiration = Carbon::now()->addYear();
            Cache::put($designation['name'], $side_bar_details, $cacheExpiration);
        }
    }


    private function buildSystemDetails($system_role)
    {
        $build_role_details = $this->buildRoleDetails($system_role);
        
        $role = [
            'id' => $build_role_details['id'],
            'name' => $build_role_details['name'],
        ];

        return [
            'id' => $system_role->system['id'],
            'name' => $system_role->system['name'],
            'code' => $system_role->system['code'],
            'roles' => [$role],
            'modules' => $build_role_details['modules']
        ];
    }

    private function buildRoleDetails($system_role)
    {
        $modules = [];

        $role_module_permissions = $system_role->roleModulePermissions;

        foreach ($role_module_permissions as $role_module_permission) {
            $module_name = $role_module_permission->modulePermission->module->name;
            $module_code = $role_module_permission->modulePermission->module->code;
            $permission_action = $role_module_permission->modulePermission->permission->action;

            if (!isset($modules[$module_name])) {
                $modules[$module_name] = ['name' => $module_name, 'code' => $module_code, 'permissions' => []];
            }

            if (!in_array($permission_action, $modules[$module_name]['permissions'])) {
                $modules[$module_name]['permissions'][] = $permission_action;
            }
        }

        return [
            'id' => $system_role->id,
            'name' => $system_role->role->name,
            'modules' => array_values($modules), // Resetting array keys
        ];
    }

    public function show($id, Request $request)
    {
        try {
            $designation = Designation::find($id);

            if (!$designation) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => new DesignationResource($designation),
                'message' => 'Designation record retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id, DesignationRequest $request)
    {
        try {
            $designation = Designation::find($id);

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $designation->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(
                [
                    'data' => new DesignationResource($designation),
                    'message' => 'Designation details updated.'
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id, PasswordApprovalRequest $request)
    {
        try {
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password . env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $designation = Designation::findOrFail($id);

            if (!$designation) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            if (count($designation->plantila ?? []) > 0 || count($designation->positionSystemRoles ?? []) > 0) {
                return response()->json(['message' => 'Some data is using this designation record deletion is prohibited.'], Response::HTTP_BAD_REQUEST);
            }

            $designation->delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(['message' => 'Designation record deleted.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
