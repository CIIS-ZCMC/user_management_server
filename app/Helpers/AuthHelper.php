<?php

namespace App\Helpers;

use App\Http\Resources\ContactResource;
use App\Models\LoginTrail;
use App\Models\PositionSystemRole;
use App\Models\Role;
use App\Models\SpecialAccessRole;
use App\Models\SystemRole;
use App\Models\WorkExperience;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class AuthHelper{

    public static function generateEmployeeProfileDetails($employee_profile)
    {
        $personal_information = $employee_profile->personalInformation;
        $assigned_area = $employee_profile->assignedArea;
        $area_assigned = $employee_profile->assignedArea->findDetails();

        $position = $employee_profile->position();

        $designation = null;

        if ($assigned_area['plantilla_id'] === null) {
            $designation = $assigned_area->designation;
        } else {
            //Employment is plantilla retrieve the plantilla and its designation.
            $plantilla = $assigned_area->plantilla;
            $designation = $plantilla->designation;
        }

        $last_login = LoginTrail::where('employee_profile_id', $employee_profile->id)->orderByDesc('created_at')->first();

        $special_access_role = SpecialAccessRole::where('employee_profile_id', $employee_profile->id)->where('system_role_id', 1)->first();
        $profile_url = $employee_profile->profile_url === null ? null : config('app.server_domain') . "/photo/profiles/" . $employee_profile->profile_url;


        $work_experiences = WorkExperience::where('personal_information_id', $personal_information->id)->where('government_office', "Yes")->get();

        $totalMonths = 0; // Initialize total months variable
        $totalYears = 0; // Initialize total months variable
        $totalZcmc = 0;

        foreach ($work_experiences as $work) {
            $dateFrom = Carbon::parse($work->date_from);
            $dateTo = Carbon::parse($work->date_to);
            $months = $dateFrom->diffInMonths($dateTo);
            if ($work->company == "Zamboanga City Medical Center") {
                $totalZcmcMonths = $dateFrom->diffInMonths($dateTo);
                $totalZcmc += $totalZcmcMonths;
            }

            $totalMonths += $months;
        }

        $currentServiceMonths = 0;
        if ($employee_profile->employmentType->id !== 5) {
            $dateHired = Carbon::parse($employee_profile->date_hired);
            $currentServiceMonths = $dateHired->diffInMonths(Carbon::now());
        }

        $total = $currentServiceMonths + $totalMonths;
        $totalYears = floor($total / 12);

        // Total service in ZCMC
        $totalMonthsInZcmc = $totalZcmc + $currentServiceMonths;
        $totalYearsInZcmc = floor($totalMonthsInZcmc / 12);

        $employee = [
            'head' => $employee_profile->employeeHeadOfficer(),
            'profile_url' => $profile_url,
            'employee_id' => $employee_profile->employee_id,
            'position' => $position,
            'is_2fa' => $employee_profile->is_2fa,
            'job_position' => $designation->name,
            'salary_grade' => $employee_profile->assignedArea->designation->salaryGrade->salary_grade_number,
            'date_hired' => $employee_profile->date_hired,
            'job_type' => $employee_profile->employmentType->name,
            'years_of_service' => $employee_profile->personalInformation->years_of_service,
            'last_login' => $last_login === null ? null : $last_login->created_at,
            'biometric_id' => $employee_profile->biometric_id,
            'total_months' => $total - ($totalYears * 12),
            'total_years' => $totalYears,
            'zcmc_service_years' => $totalYearsInZcmc,
            'zcmc_service_months' => $totalMonthsInZcmc - ($totalYearsInZcmc * 12),
            'is_admin' => $special_access_role !== null ? true : false,
            'is_allowed_ta' => $employee_profile->allow_time_adjustment,
            'shifting' => $employee_profile->shifting
        ];

        $personal_information_data = [
            'personal_information_id' => $personal_information->id,
            'full_name' => $personal_information->nameWithSurnameFirst(),
            'first_name' => $personal_information->first_name,
            'last_name' => $personal_information->last_name,
            'middle_name' => $personal_information->middle_name === null ? ' ' : $personal_information->middle_name,
            'name_extension' => $personal_information->name_extension === null ? null : $personal_information->name_extension,
            'employee_id' => $employee_profile->employee_id,
            'years_of_service' => $employee_profile->personalInformation->years_of_service === null ? null : $personal_information->years_of_service,
            'name_title' => $personal_information->name_title === null ? null : $personal_information->name_title,
            'sex' => $personal_information->sex,
            'date_of_birth' => $personal_information->date_of_birth,
            'date_hired' => $employee_profile->date_hired,
            'place_of_birth' => $personal_information->place_of_birth,
            'civil_status' => $personal_information->civil_status,
            'citizenship' => $personal_information->citizenship,
            'date_of_marriage' => $personal_information->date_of_marriage === null ? null : $personal_information->date_of_marriage,
            'agency_employee_no' => $employee_profile->agency_employee_no === null ? null : $personal_information->agency_employee_no,
            'blood_type' => $personal_information->blood_type === null ? null : $personal_information->blood_type,
            'height' => $personal_information->height,
            'weight' => $personal_information->weight,
        ];

        return [
            'employee_profile_id' => $employee_profile['id'],
            'profile_url' => $employee_profile['profile_url'],
            'employee_id' => $employee_profile['employee_id'],
            'name' => $personal_information->employeeName(),
            'password_expiration_at' => $employee_profile->password_expiration_at,
            'password_updated_at' => $employee_profile->password_created_at,
            'pin_created_at' => $employee_profile->pin_created_at,
            'designation' => $designation['name'],
            'designation_code' => $designation['code'],
            'plantilla_number_id' => $assigned_area['plantilla_number_id'],
            'plantilla_number' => $assigned_area['plantilla_number_id'] === NULL ? NULL : $assigned_area->plantillaNumber['number'],
            'shifting' => $employee_profile->shifting,
            'employee_details' => [
                'employee' => $employee,
                'personal_information' => $personal_information_data,
                'contact' => $personal_information->contact === null ? null : new ContactResource($personal_information->contact)
            ],
            'area_assigned' => $area_assigned['details']->name,
            'area_assigned_code' => $area_assigned['details']->code,
            'area_assigned_area_id' => $area_assigned['details']->area_id,
            'area_sector' => $area_assigned['sector'],
            'area_id' => $area_assigned['details']->id
        ];
    }
    
    public static function buildSidebarDetails($employee_profile, $designation, $special_access_roles, $api_id)
    {
        $sidebar_cache = Cache::forget($designation['name']);
        $sidebar_cache = Cache::get($designation['name']);
        $side_bar_details['system'] = [];

        if ($sidebar_cache === null) {
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
            ])
            ->whereHas('systemRole', function ($query) use ($api_id) {
                $query->where('system_id', $api_id);
            })
            ->where('designation_id', $designation['id'])
            ->get();


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
                        $side_bar_details['system'][] = self::buildSystemDetails($system_role);
                        continue;
                    }

                    foreach ($side_bar_details['system'] as &$system) {
                        if ($system['id'] === $system_role->system['id']) {
                            $system_exist = true;

                            $build_role_details = self::buildRoleDetails($system_role);

                            // Convert the array of object to array of string retrieving only the names of role
                            $new_array_roles = collect($system['roles'])->pluck('name')->toArray();

                            // Validate if the role exist in the array, if not, then add a new role to system roles
                            if (!in_array($build_role_details['name'], $new_array_roles)) {
                                $system['roles'][] = [
                                    'id' => $build_role_details['id'],
                                    'name' => $build_role_details['name'],
                                ];
                            }

                            // Convert the array of objects to a collection
                            $collection = collect($system['modules']);

                            foreach ($build_role_details['modules'] as $role_module) {
                                // Check if the module with the given code exists in the collection
                                $moduleIndex = $collection->search(function ($module) use ($role_module) {
                                    return $module['code'] === $role_module['code'];
                                });

                                if ($moduleIndex !== false) {
                                    // If the module exists, modify its permissions
                                    $collection->transform(function ($module) use ($role_module) {
                                        if ($module['code'] === $role_module['code']) {
                                            // Iterate new permissions of other system role
                                            foreach ($role_module['permissions'] as $permission) {
                                                // If permission doesn't exist in current module then it will be added to the module permissions.
                                                if (!in_array($permission, $module['permissions'])) {
                                                    $module['permissions'][] = $permission;
                                                }
                                            }
                                        }
                                        return $module;
                                    });
                                } else {
                                    // If the module doesn't exist, add it to the collection
                                    $collection->push($role_module);
                                }
                            }

                            // Update the modules in the system array
                            $system['modules'] = $collection->toArray();

                            // Break out of the loop once the system is found and updated
                            break;
                        }
                    }

                    if (!$system_exist) {
                        $side_bar_details['system'][] = self::buildSystemDetails($system_role);
                    }
                }

                $cacheExpiration = Carbon::now()->addYear();
                Cache::put($designation['name'], $side_bar_details, $cacheExpiration);
            }
        } else {
            $side_bar_details = $sidebar_cache;
        }
        
        /**
         * For Empoyee with Special Access Roles
         * Validate if employee has Special Access Roles
         * Update Sidebar Details.
         */
        if (!empty($special_access_roles)) {

            // Convert to a Laravel collection
            $collection = collect($special_access_roles);

            // Retrieve all ids and store them in id_array
            $id_array = $collection->pluck('id')->toArray();

            $special_access_permissions = SpecialAccessRole::with([
                'systemRole' => function ($query) {
                    $query->with([
                        'system',
                        'roleModulePermissions' => function ($query) {
                            $query->with([
                                'modulePermission' => function ($query) {
                                    $query->with(['module', 'permission']);
                                }
                            ]);
                        }
                    ]);
                }
            ])->whereIn('id', $id_array)->get();

            if (count($special_access_permissions) > 0) {
                foreach ($special_access_permissions as $key => $special_access_permission) {
                    $system_exist = false;
                    $system_role = $special_access_permission['systemRole'];

                    $exists = array_search($system_role->system_id, array_column($side_bar_details['system'], 'id')) !== false;

                    /**
                     * If side bar details system array is empty
                     */
                    if (!$side_bar_details['system']) {
                        $side_bar_details['system'][] = self::buildSystemDetails($system_role);
                        continue;
                    }
                    

                    foreach ($side_bar_details['system'] as &$system) {
                        if ($system['id'] === $system_role->system['id']) {
                            $system_exist = true;

                            $build_role_details = self::buildRoleDetails($system_role);

                            // Convert the array of object to array of string retrieving only the names of role
                            $new_array_roles = collect($system['roles'])->pluck('name')->toArray();

                            // Validate if the role exist in the array, if not, then add a new role to system roles
                            if (!in_array($build_role_details['name'], $new_array_roles)) {
                                $system['roles'][] = [
                                    'id' => $build_role_details['id'],
                                    'name' => $build_role_details['name'],
                                ];
                            }

                            // Convert the array of objects to a collection
                            $collection = collect($system['modules']);

                            foreach ($build_role_details['modules'] as $role_module) {
                                // Check if the module with the given code exists in the collection
                                $moduleIndex = $collection->search(function ($module) use ($role_module) {
                                    return $module['code'] === $role_module['code'];
                                });

                                if ($moduleIndex !== false) {
                                    // If the module exists, modify its permissions
                                    $collection->transform(function ($module) use ($role_module) {
                                        if ($module['code'] === $role_module['code']) {
                                            // Iterate new permissions of other system role
                                            foreach ($role_module['permissions'] as $permission) {
                                                // If permission doesn't exist in current module then it will be added to the module permissions.
                                                if (!in_array($permission, $module['permissions'])) {
                                                    $module['permissions'][] = $permission;
                                                }
                                            }
                                        }
                                        return $module;
                                    });
                                } else {
                                    // If the module doesn't exist, add it to the collection
                                    $collection->push($role_module);
                                }
                            }

                            // Update the modules in the system array
                            $system['modules'] = $collection->toArray();

                            // Break out of the loop once the system is found and updated
                            break;
                        }
                    }

                    /** 
                     * On empty system this will direct insert the system
                     * Or
                     * when system is not empty but the target system doesn't exist this will append to it
                     */
                    if (count($side_bar_details['system']) === 0 || !$exists) {
                        $side_bar_details['system'][] = self::buildSystemDetails($system_role);
                    }
                }

                $cacheExpiration = Carbon::now()->addYear();
                Cache::put($employee_profile['employee_id'], $side_bar_details, $cacheExpiration);
            }
        }

        if (($side_bar_details['system']) > 0) {
            $employment_type = $employee_profile->employmentType;

            if ($employment_type->name === "Permanent Full-time" || $employment_type->name === "Permanent CTI" || $employment_type->name === "Permanent Part-time" || $employment_type->name === 'Temporary') {
                $role = Role::where('code', "COMMON-REG")->first();
                $reg_system_role = SystemRole::where('role_id', $role->id)->where("system_id", $api_id)->first();

                if($reg_system_role !== null){
                    $exists = array_search($reg_system_role->system_id, array_column($side_bar_details['system'], 'id')) !== false;
                    
                    foreach ($side_bar_details['system'] as &$system) {
                        if ($system['id'] === $reg_system_role->system_id && $system['id'] === $api_id) {
                            $system_role_exist = false;

                            foreach ($system['roles'] as $value) {
                                if ($value['name'] === $role->name) {
                                    $system_role_exist = true;
                                    break; // No need to continue checking once the role is found
                                }
                            }

                            if (!$system_role_exist) {
                                $reg_system_roles_data = self::buildRoleDetails($reg_system_role);

                                $cacheExpiration = Carbon::now()->addYear();
                                Cache::put("COMMON-REG", $reg_system_roles_data, $cacheExpiration);

                                $system['roles'][] = [
                                    'id' => $reg_system_roles_data['id'],
                                    'name' => $reg_system_roles_data['name']
                                ];

                                // Convert the array of objects to a collection
                                $modulesCollection = collect($system['modules']);

                                foreach ($reg_system_roles_data['modules'] as $role_module) {
                                    // Check if the module with the code exists in the collection
                                    $existingModuleIndex = $modulesCollection->search(function ($module) use ($role_module) {
                                        return $module['code'] === $role_module['code'];
                                    });

                                    if ($existingModuleIndex !== false) {
                                        // If the module exists, modify its permissions
                                        $existingModule = $modulesCollection->get($existingModuleIndex);
                                        foreach ($role_module['permissions'] as $permission) {
                                            // If permission doesn't exist in the current module then it will be added to the module permissions.
                                            if (!in_array($permission, $existingModule['permissions'])) {
                                                $existingModule['permissions'][] = $permission;
                                            }
                                        }
                                        $modulesCollection->put($existingModuleIndex, $existingModule);
                                    } else {
                                        // If the module doesn't exist, add it to the collection
                                        $modulesCollection->push($role_module);
                                    }
                                }

                                // Assign back the modified modules collection to the system
                                $system['modules'] = $modulesCollection->toArray();
                            }
                        }
                    }

                    /** 
                     * On empty system this will direct insert the system
                     * Or
                     * when system is not empty but the target system doesn't exist this will append to it
                     */
                    if (count($side_bar_details['system']) === 0 || !$exists) {
                        $side_bar_details['system'][] = self::buildSystemDetails($reg_system_role);
                    }
                }
            }

            if ($employment_type->name == "Job Order") {
                $role = Role::where("code", "COMMON-JO")->first();
                $jo_system_role = SystemRole::where('role_id', $role->id)->where("system_id", $api_id)->first();
                
                // ignore system that is not the target api
                if($jo_system_role !== null) {
                    /**
                     * If bug happens that user has rights but for JOB ORDER is unll on Cache Uncomment this code.
                     *
                     * $jo_system_roles_data = $this->buildRoleDetails($jo_system_role);
                     * $cacheExpiration = Carbon::now()->addYear();
                     * Cache::put("COMMON-JO", $jo_system_roles_data, $cacheExpiration);
                     */

                    $exists = array_search($jo_system_role->system_id, array_column($side_bar_details['system'], 'id')) !== false;

                    if($exists){
                        foreach ($side_bar_details['system'] as &$system) {
                            if ($system['id'] === $jo_system_role->system_id  && $system['id'] === $api_id) {
                                $system_role_exist = false;
        
                                // Check if role exist in the system
                                foreach ($system['roles'] as $value) {
                                    if ($value['name'] === $role->name) {
                                        $system_role_exist = true;
                                        break; // No need to continue checking once the role is found
                                    }
                                }
        
                                if (!$system_role_exist) {
                                    $jo_system_roles_data = self::buildRoleDetails($jo_system_role);
        
                                    $cacheExpiration = Carbon::now()->addYear();
                                    Cache::put("COMMON-JO", $jo_system_roles_data, $cacheExpiration);
        
                                    $system['roles'][] = [
                                        'id' => $jo_system_roles_data['id'],
                                        'name' => $jo_system_roles_data['name']
                                    ];
        
                                    // Convert the array of objects to a collection
                                    $modulesCollection = collect($system['modules']);
        
                                    foreach ($jo_system_roles_data['modules'] as $role_module) {
                                        // Check if the module with the code exists in the collection
                                        $existingModuleIndex = $modulesCollection->search(function ($module) use ($role_module) {
                                            return $module['code'] === $role_module['code'];
                                        });
        
                                        if ($existingModuleIndex !== false) {
                                            // If the module exists, modify its permissions
                                            $existingModule = $modulesCollection->get($existingModuleIndex);
                                            foreach ($role_module['permissions'] as $permission) {
                                                // If permission doesn't exist in the current module then it will be added to the module permissions.
                                                if (!in_array($permission, $existingModule['permissions'])) {
                                                    $existingModule['permissions'][] = $permission;
                                                }
                                            }
                                            $modulesCollection->put($existingModuleIndex, $existingModule);
                                        } else {
                                            // If the module doesn't exist, add it to the collection
                                            $modulesCollection->push($role_module);
                                        }
                                    }
        
                                    // Assign back the modified modules collection to the system
                                    $system['modules'] = $modulesCollection->toArray();
                                }
                            }
                        }
                    }
    
                    /** 
                     * On empty system this will direct insert the system
                    * Or
                    * when system is not empty but the target system doesn't exist this will append to it
                    */
                    if (count($side_bar_details['system']) === 0 || !$exists) {
                        $side_bar_details['system'][] = self::buildSystemDetails($jo_system_role);
                    }
                }
            }
        }
        
        $public_system_roles = PositionSystemRole::where('is_public', 1)->get();

        foreach($public_system_roles as $public_system_role)
        {
            $system_role_value = $public_system_role->systemRole;
            $exists = array_search($system_role_value->system_id, array_column($side_bar_details['system'], 'id')) !== false;

            if(count($side_bar_details['system']) === 0 || !$exists){
                $side_bar_details['system'][] = self::buildSystemDetails($public_system_role->systemRole);
                continue;
            }

            foreach ($side_bar_details['system'] as &$system) {
                if ($system['id'] === $system_role_value->system_id) {
                    $system_role_exist = false;
                    $role = $system_role_value->role;

                    // Check if role exist in the system
                    foreach ($system['roles'] as $value) {
                        if ($value['name'] === $role->name) {
                            $system_role_exist = true;
                            break; // No need to continue checking once the role is found
                        }
                    }

                    if (!$system_role_exist) {
                        $public_system_roles_data = self::buildRoleDetails($public_system_role->systemRole);

                        $system['roles'][] = [
                            'id' => $public_system_roles_data['id'],
                            'name' => $public_system_roles_data['name']
                        ];

                        // Convert the array of objects to a collection
                        $modulesCollection = collect($system['modules']);

                        foreach ($public_system_roles_data['modules'] as $role_module) {
                            // Check if the module with the code exists in the collection
                            $existingModuleIndex = $modulesCollection->search(function ($module) use ($role_module) {
                                return $module['code'] === $role_module['code'];
                            });

                            if ($existingModuleIndex !== false) {
                                // If the module exists, modify its permissions
                                $existingModule = $modulesCollection->get($existingModuleIndex);
                                foreach ($role_module['permissions'] as $permission) {
                                    // If permission doesn't exist in the current module then it will be added to the module permissions.
                                    if (!in_array($permission, $existingModule['permissions'])) {
                                        $existingModule['permissions'][] = $permission;
                                    }
                                }
                                $modulesCollection->put($existingModuleIndex, $existingModule);
                            } else {
                                // If the module doesn't exist, add it to the collection
                                $modulesCollection->push($role_module);
                            }
                        }

                        // Assign back the modified modules collection to the system
                        $system['modules'] = $modulesCollection->toArray();
                    }
                }
            }
        }

        return $side_bar_details['system'][0];
    }

    private static function buildSystemDetails($system_role)
    {
        $build_role_details = self::buildRoleDetails($system_role);

        $role = [
            'id' => $build_role_details['id'],
            'name' => $build_role_details['name']
        ];        

        return [
            'id' => $system_role->system['id'],
            'name' => $system_role->system['name'],
            'code' => $system_role->system['code'],
            'domain' => $system_role->system['domain'] !== null? Crypt::decrypt($system_role->system['domain']): null,
            'roles' => [$role],
            'modules' => $build_role_details['modules']
        ];
    }

    private static function buildRoleDetails($system_role)
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
}