<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\PasswordApprovalRequest;
use App\Models\PersonalInformation;
use Illuminate\Support\Str;
use App\Http\Controllers\DTR\TwoFactorAuthController;
use App\Http\Resources\ChildResource;
use App\Http\Resources\CivilServiceEligibilityResource;
use App\Http\Resources\ContactResource;
use App\Http\Resources\EducationalBackgroundResource;
use App\Http\Resources\FamilyBackGroundResource;
use App\Http\Resources\IdentificationNumberResource;
use App\Http\Resources\OtherInformationResource;
use App\Http\Resources\PlantillaNumberResource;
use App\Http\Resources\TrainingResource;
use App\Http\Resources\VoluntaryWorkResource;
use App\Http\Resources\WorkExperienceResource;
use App\Methods\MailConfig;
use App\Models\AccessToken;
use App\Models\AssignAreaTrail;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Division;
use App\Models\EmployeeLeaveCredit;
use App\Models\EmployeeOvertimeCredit;
use App\Models\InActiveEmployee;
use App\Models\LeaveType;
use App\Models\PasswordTrail;
use App\Models\PlantillaNumber;
use App\Models\Role;
use App\Models\Section;
use App\Models\SystemRole;
use App\Models\Unit;
use App\Rules\StrongPassword;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Requests\SignInRequest;
use App\Http\Requests\EmployeeProfileRequest;
use App\Http\Resources\EmployeeProfileResource;
use App\Http\Resources\EmployeesByAreaAssignedResource;
use App\Http\Resources\EmployeeDTRList;
use App\Models\AssignArea;
use App\Models\DefaultPassword;
use App\Models\EmployeeProfile;
use App\Models\LoginTrail;
use App\Models\PositionSystemRole;
use App\Models\SpecialAccessRole;
use App\Http\Requests\PromotionRequest;
use Illuminate\Support\Facades\DB;

class EmployeeProfileController extends Controller
{
    private $CONTROLLER_NAME = 'Employee Profile';
    private $PLURAL_MODULE_NAME = 'employee profiles';
    private $SINGULAR_MODULE_NAME = 'employee profile';

    private $mail;
    private $two_auth;

    public function __construct()
    {
        $this->mail = new MailConfig();
        $this->two_auth = new TwoFactorAuthController();
    }

    /**
     * Validate if Employee ID is legitimate
     * Validate if Account is Deactivated
     * Decrypt Password from Database
     * Validate Password Legitemacy
     * Create Access Token
     * Retrieve Personal Information
     * Job Details (Plantilla or Not)
     *
     */
    public function signIn(SignInRequest $request)
    {
        try {

            /**
             * Fields Needed:
             *  employee_id
             *  password
             *  persist_password: for reuse of password
             */
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $employee_profile = EmployeeProfile::where('employee_id', $cleanData['employee_id'])->first();

            /**
             * For Persist password even when it expired for set months of expiration.
             */
            if ($request->persist_password !== null && $request->persist_password === 1) {
                $fortyDaysFromNow = Carbon::now()->addDays(40);
                $fortyDaysExpiration = $fortyDaysFromNow->toDateTimeString();

                $employee_profile->update(['password_expiration_at' => $fortyDaysExpiration]);
            }

            if (!$employee_profile) {
                return response()->json(['message' => "Employee id or password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            if (!$employee_profile->isDeactivated()) {
                return response()->json(['message' => "Account is deactivated."], Response::HTTP_FORBIDDEN);
            }

            $decryptedPassword = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($cleanData['password'] . env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Employee id or password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $agent = new Agent();
            $device = [
                'is_desktop' => $agent->isDesktop(),
                'is_mobile' => $agent->isMobile(),
                'platform' => $agent->platform(),
                'browser' => $agent->browser(),
                'version' => $agent->version($agent->browser())
            ];

            /**
             * For new account need to reset the password
             */
            if ($employee_profile->authorization_pin === null) {
                return response()->json(['message' => 'New account'], Response::HTTP_TEMPORARY_REDIRECT)
                    ->cookie('employee_details', json_encode(['employee_id' => $employee_profile->employee_id]), 60, '/', env('SESSION_DOMAIN'), false); //status 307
            }

            /**
             * For password expired.
             */
            if (Carbon::now()->startOfDay()->gte($employee_profile->password_expiration_at)) {
                // Mandatory change password for annual.
                if (Carbon::now()->year > Carbon::parse($employee_profile->password_expiration_at)->year) {
                    return response()->json(['message' => 'expired-required'], Response::HTTP_TEMPORARY_REDIRECT)
                        ->cookie('employee_details', json_encode(['employee_id' => $employee_profile->employee_id]), 60, '/', env('SESSION_DOMAIN'), false); //status 307
                }

                //Optional change password
                return response()->json(['message' => 'expired-optional'], Response::HTTP_TEMPORARY_REDIRECT)
                    ->cookie('employee_details', json_encode(['employee_id' => $employee_profile->employee_id]), 60, '/', env('SESSION_DOMAIN'), false); //status 307
            }

            /**
             * If account is login to other device
             * notify user for that and allow user to choose to cancel or proceed to signout account to other device
             * return employee profile id when user choose to proceed signout in other device
             * for server to be able to determine which account it want to sign out.
             * If account is singin with in the same machine like ip and device and platform continues
             * signin without signout to current signined of account.
             * Reuse the created token of first instance of signin to have single access token.
             */

            // $access_token = AccessToken::where('employee_profile_id', $employee_profile->id)->orderBy('token_exp')->first();

            // if ($access_token !== null && Carbon::parse(Carbon::now())->startOfDay()->lte($access_token->token_exp)) {
            //     $ip = $request->ip();

            //     $login_trail = LoginTrail::where('employee_profile_id', $employee_profile->id)->first();

            //     if ($login_trail !== null) {
            //         if ($login_trail->ip_address !== $ip) {
            //             $data = Helpers::generateMyOTP($employee_profile);

            //             if ($this->mail->send($data)) {
            //                 return response()->json(['message' => "You are currently logged on to other device. An OTP has been sent to your registered email. If you want to signout from that device, submit the OTP."], Response::HTTP_FOUND)
            //                     ->cookie('employee_details', json_encode(['employee_id' => $employee_profile->employee_id]), 60, '/', env('SESSION_DOMAIN'), false);
            //             }

            //             return response()->json(['message' => "Your account is currently logged on to other device, sending otp to your email has failed please try again later."], Response::HTTP_INTERNAL_SERVER_ERROR);
            //         }
            //     }
            // }

            // if ($access_token !== null) {
                AccessToken::where('employee_profile_id', $employee_profile->id)->delete();
            // }

            /**
             * Validate for 2FA
             * if 2FA is activated send OTP to email to validate ownership
             */
            if ((bool) $employee_profile->is_2fa) {
                $data = Helpers::generateMyOTP($request);

                if ($this->mail->send($data)) {
                    return response()->json(['message' => "OTP has sent to your email, submit the OTP to verify that this is your account."], Response::HTTP_OK)
                        ->cookie('employee_details', json_encode(['employee_id' => $employee_profile->employee_id]), 60, '/', env('SESSION_DOMAIN'), false);
                }

                return response()->json([
                    'message' => 'Failed to send OTP to your email.'
                ], Response::HTTP_BAD_REQUEST);
            }

            $token = $employee_profile->createToken();

            $assigned_area = $employee_profile->assignedArea;
            $plantilla = null;
            $designation = null;

            if ($assigned_area['plantilla_id'] === null) {
                $designation = $assigned_area->designation;
            } else {
                //Employment is plantilla retrieve the plantilla and its designation.
                $plantilla = $assigned_area->plantilla;
                $designation = $plantilla->designation;
            }

            $special_access_roles = $employee_profile->specialAccessRole;

            $side_bar_details = null;
            $trials = 2;

            //Retrieve Sidebar Details for the employee base on designation.
            do {
                $side_bar_details = $this->buildSidebarDetails($employee_profile, $designation, $special_access_roles);

                if (count($side_bar_details['system']) === 0) {
                    Cache::forget($designation['name']);
                    break;
                };

                $trials--;
            } while ($trials !== 0);

            if ($side_bar_details === null || count($side_bar_details['system']) === 0) {
                return response()->json([
                    'data' => $side_bar_details,
                    'message' => "Please be inform that your account currently doesn't have access to the system."
                ], Response::HTTP_UNAUTHORIZED);
            }

            $data = $this->generateEmployeeProfileDetails($employee_profile, $side_bar_details);

            LoginTrail::create([
                'signin_at' => now(),
                'ip_address' => $request->ip(),
                'device' => ($device['is_desktop'] ? 'Desktop' : $device['is_mobile']) ? 'Mobile' : 'Unknown',
                'platform' => is_bool($device['platform']) ? 'Postman' : $device['platform'],
                'browser' => is_bool($device['browser']) ? 'Postman' : $device['browser'],
                'browser_version' => is_bool($device['version']) ? 'Postman' : $device['version'],
                'employee_profile_id' => $employee_profile['id']
            ]);

            return response()
                ->json(["data" => $data, 'message' => "Success login."], Response::HTTP_OK)
                ->cookie(Helpers::Cookie_Name(), json_encode(['token' => $token]), 60, '/', env('SESSION_DOMAIN'), false);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'signIn', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function buildSidebarDetails($employee_profile, $designation, $special_access_roles)
    {
        $sidebar_cache = Cache::forget($designation['name']);
        $sidebar_cache = Cache::get($designation['name']);

        $side_bar_details['designation_id'] = $designation['id'];
        $side_bar_details['designation_name'] = $designation['name'];
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

                    foreach ($side_bar_details['system'] as &$system) {
                        if ($system['id'] === $system_role->system['id']) {
                            $system_exist = true;

                            $build_role_details = $this->buildRoleDetails($system_role);

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
                        $side_bar_details['system'][] = $this->buildSystemDetails($system_role);
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
            ])->where('employee_profile_id', $employee_profile['id'])->get();

            if (count($special_access_permissions) > 0) {
                foreach ($special_access_permissions as $key => $special_access_permission) {
                    $system_exist = false;
                    $system_role = $special_access_permission['systemRole'];

                    /**
                     * If side bar details system array is empty
                     */
                    if (!$side_bar_details['system']) {
                        $side_bar_details['system'][] = $this->buildSystemDetails($system_role);
                        continue;
                    }

                    foreach ($side_bar_details['system'] as &$system) {
                        if ($system['id'] === $system_role->system['id']) {
                            $system_exist = true;

                            $build_role_details = $this->buildRoleDetails($system_role);

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
                        $side_bar_details->system[] = $this->buildSystemDetails($system_role);
                    }
                }

                $cacheExpiration = Carbon::now()->addYear();
                Cache::put($employee_profile['employee_id'], $side_bar_details, $cacheExpiration);
            }
        }

        if (($side_bar_details['system']) > 0) {
            $employment_type = $employee_profile->employmentType;

            if ($employment_type->name === "Permanent" || $employment_type->name === 'Temporary') {
                $role = Role::where('code', "COMMON-REG")->first();
                $reg_system_role = SystemRole::where('role_id', $role->id)->first();

                foreach ($side_bar_details['system'] as &$system) {
                    if ($system['id'] === $reg_system_role->system_id) {
                        $system_role_exist = false;

                        foreach ($system['roles'] as $value) {
                            if ($value['name'] === $role->name) {
                                $system_role_exist = true;
                                break; // No need to continue checking once the role is found
                            }
                        }

                        if (!$system_role_exist) {
                            $reg_system_roles_data = $this->buildRoleDetails($reg_system_role);

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

                if(count($side_bar_details['system'])  === 0){
                    $side_bar_details['system'][] = $this->buildSystemDetails($reg_system_role);
                }
            }

            if ($employment_type->name == "Job order") {
                $role = Role::where("code", "COMMON-JO")->first();
                $jo_system_role = SystemRole::where('role_id', $role->id)->first();

                foreach ($side_bar_details['system'] as &$system) {
                    if ($system['id'] === $jo_system_role->system_id) {
                        $system_role_exist = false;

                        foreach ($system['roles'] as $value) {
                            if ($value['name'] === $role->name) {
                                $system_role_exist = true;
                                break; // No need to continue checking once the role is found
                            }
                        }

                        if (!$system_role_exist) {
                            $jo_system_roles_data = $this->buildRoleDetails($jo_system_role);

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

                if(count($side_bar_details['system'])  === 0){
                    $side_bar_details['system'][] = $this->buildSystemDetails($jo_system_role);
                }
            }
        }

        return $side_bar_details;
    }

    private function buildSystemDetails($system_role)
    {
        $build_role_details = $this->buildRoleDetails($system_role);

        $role = [
            'id' => $build_role_details['id'],
            'name' => $build_role_details['name']
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

    public function generateEmployeeProfileDetails($employee_profile, $side_bar_details)
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

        $employee = [
            'profile_url' => env('SERVER_DOMAIN') . "/photo/profiles/" . $employee_profile->profile_url,
            'employee_id' => $employee_profile->employee_id,
            'position' => $position,
            'job_position' => $designation->name,
            'date_hired' => $employee_profile->date_hired,
            'job_type' => $employee_profile->employmentType->name,
            'years_of_service' => $employee_profile->personalInformation->years_of_service,
            'last_login' => $last_login === null ? null : $last_login->created_at,
            'biometric_id' => $employee_profile->biometric_id,
            'is_admin' => $special_access_role !== null ? true : false
        ];

        $personal_information_data = [
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

        $address = [
            'residential_address' => null,
            'residential_zip_code' => null,
            'residential_telephone_no' => null,
            'permanent_address' => null,
            'permanent_zip_code' => null,
            'permanent_telephone_no' => null
        ];

        $addresses = $personal_information->addresses;

        foreach ($addresses as $value) {

            if ($value->is_residential_and_permanent) {
                $address['residential_address'] = $value->address;
                $address['residential_zip_code'] = $value->zip_code;
                $address['residential_telephone_no'] = $value->telephone_no === null ? null : $value->telephone_no;
                $address['permanent_address'] = $value->address;
                $address['permanent_zip_code'] = $value->zip_code;
                $address['permanent_telephone_no'] = $value->telephone_no === null ? null : $value->telephone_no;
                break;
            }

            if ($value->is_residential) {
                $address['residential_address'] = $value->address;
                $address['residential_zip_code'] = $value->zip_code;
                $address['residential_telephone_no'] = $value->telephone_no === null ? null : $value->telephone_no;
            } else {
                $address['permanent_address'] = $value->address;
                $address['permanent_zip_code'] = $value->zip_code;
                $address['permanent_telephone_no'] = $value->telephone_no === null ? null : $value->telephone_no;
            }
        }

        return [
            'employee_profile_id' => $employee_profile['id'],
            'employee_id' => $employee_profile['employee_id'],
            'name' => $personal_information->employeeName(),
            'designation' => $designation['name'],
            'employee_details' => [
                'employee' => $employee,
                'personal_information' => $personal_information_data,
                'contact' => new ContactResource($personal_information->contact),
                'address' => $address,
                'family_background' => new FamilyBackGroundResource($personal_information->familyBackground),
                'children' => ChildResource::collection($personal_information->children),
                'education' => EducationalBackgroundResource::collection($personal_information->educationalBackground),
                'affiliations_and_others' => [
                    'civil_service_eligibility' => CivilServiceEligibilityResource::collection($personal_information->civilServiceEligibility),
                    'work_experience' => WorkExperienceResource::collection($personal_information->workExperience),
                    'voluntary_work_or_involvement' => VoluntaryWorkResource::collection($personal_information->voluntaryWork),
                    'training' => TrainingResource::collection($personal_information->training),
                    'other' => OtherInformationResource::collection($personal_information->otherInformation),
                ],
                'issuance' => $employee_profile->issuanceInformation,
                'reference' => $employee_profile->personalInformation->references,
                'legal_information' => $employee_profile->personalInformation->legalInformation,
                'identification' => new IdentificationNumberResource($employee_profile->personalInformation->identificationNumber)
            ],
            'area_assigned' => $area_assigned['details']->name,
            'area_sector' => $area_assigned['sector'],
            'area_id' =>  $area_assigned['details']->id,
            'side_bar_details' => $side_bar_details
        ];
    }

    //**Require employee id *
    public function signOutFromOtherDevice(Request $request)
    {
        try {
            $employee_details = json_decode($request->cookie('employee_details'));

            $employee_profile = EmployeeProfile::where('employee_id', $employee_details->employee_id)->first();

            if (!$employee_profile) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $otp = strip_tags($request->otp);
            $otpExpirationMinutes = 5;
            $currentDateTime = Carbon::now();
            $otp_expiration = Carbon::parse($employee_profile->otp_expiration);

            if ($currentDateTime->diffInMinutes($otp_expiration) > $otpExpirationMinutes) {
                return response()->json(['message' => 'OTP has expired.'], Response::HTTP_BAD_REQUEST);
            }

            if ((int)$otp !== $employee_profile->otp) {
                return response()->json(['message' => 'Invalid OTP.'], Response::HTTP_BAD_REQUEST);
            }

            $employee_profile->update([
                'otp' => null,
                'otp_expiration' => null
            ]);

            AccessToken::where('employee_profile_id', $employee_profile->id)->delete();

            $agent = new Agent();
            $device = [
                'is_desktop' => $agent->isDesktop(),
                'is_mobile' => $agent->isMobile(),
                'platform' => $agent->platform(),
                'browser' => $agent->browser(),
                'version' => $agent->version($agent->browser())
            ];

            $token = $employee_profile->createToken();
            $assigned_area = $employee_profile->assignedArea;
            $plantilla = null;
            $designation = null;

            if ($assigned_area['plantilla_id'] === null) {
                $designation = $assigned_area->designation;
            } else {
                //Employment is plantilla retrieve the plantilla and its designation.
                $plantilla = $assigned_area->plantilla;
                $designation = $plantilla->designation;
            }

            $special_access_roles = $employee_profile->specialAccessRole;

            //Retrieve Sidebar Details for the employee base on designation.
            $side_bar_details = null;
            $trials = 2;

            //Retrieve Sidebar Details for the employee base on designation.
            do {
                $side_bar_details = $this->buildSidebarDetails($employee_profile, $designation, $special_access_roles);

                if (count($side_bar_details['system']) === 0) {
                    Cache::forget($designation['name']);
                    break;
                };

                $trials--;
            } while ($trials !== 0);

            if ($side_bar_details === null || count($side_bar_details['system']) === 0) {
                return response()->json([
                    'data' => $side_bar_details,
                    'message' => "Please be inform that your account currently doesn't have access to the system."
                ], Response::HTTP_UNAUTHORIZED);
            }

            $data = $this->generateEmployeeProfileDetails($employee_profile, $side_bar_details);

            LoginTrail::create([
                'signin_at' => now(),
                'ip_address' => $request->ip(),
                'device' => ($device['is_desktop'] ? 'Desktop' : $device['is_mobile']) ? 'Mobile' : 'Unknown',
                'platform' => is_bool($device['platform']) ? 'Postman' : $device['platform'],
                'browser' => is_bool($device['browser']) ? 'Postman' : $device['browser'],
                'browser_version' => is_bool($device['version']) ? 'Postman' : $device['version'],
                'employee_profile_id' => $employee_profile['id']
            ]);

            return response()
                ->json(['data' => $data, 'message' => "Success signout to other device you are now login."], Response::HTTP_OK)
                ->cookie(Helpers::Cookie_Name(), json_encode(['token' => $token]), 60, '/', env('SESSION_DOMAIN'), false);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'signOutFromOtherDevice', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function revalidateAccessToken(Request $request)
    {
        try {
            $employee_profile = $request->user;

            $agent = new Agent();
            $device = [
                'is_desktop' => $agent->isDesktop(),
                'is_mobile' => $agent->isMobile(),
                'platform' => $agent->platform(),
                'browser' => $agent->browser(),
                'version' => $agent->version($agent->browser())
            ];

            $assigned_area = $employee_profile->assignedArea;
            $plantilla = null;
            $designation = null;

            if ($assigned_area['plantilla_id'] === null) {
                $designation = $assigned_area->designation;
            } else {
                //Employment is plantilla retrieve the plantilla and its designation.
                $plantilla = $assigned_area->plantilla;
                $designation = $plantilla->designation;
            }

            $special_access_roles = $employee_profile->specialAccessRole;

            //Retrieve Sidebar Details for the employee base on designation.
            $side_bar_details = null;
            $trials = 2;

            //Retrieve Sidebar Details for the employee base on designation.
            do {
                $side_bar_details = $this->buildSidebarDetails($employee_profile, $designation, $special_access_roles);

                if (count($side_bar_details['system']) === 0) {
                    Cache::forget($designation['name']);
                    break;
                };

                $trials--;
            } while ($trials !== 0);

            if ($side_bar_details === null || count($side_bar_details['system']) === 0) {
                return response()->json([
                    'data' => $side_bar_details,
                    'message' => "Please be inform that your account currently doesn't have access to the system."
                ], Response::HTTP_UNAUTHORIZED);
            }

            $data = $this->generateEmployeeProfileDetails($employee_profile, $side_bar_details);

            LoginTrail::create([
                'signin_at' => now(),
                'ip_address' => $request->ip(),
                'device' => ($device['is_desktop'] ? 'Desktop' : $device['is_mobile']) ? 'Mobile' : 'Unknown',
                'platform' => is_bool($device['platform']) ? 'Postman' : $device['platform'],
                'browser' => is_bool($device['browser']) ? 'Postman' : $device['browser'],
                'browser_version' => is_bool($device['version']) ? 'Postman' : $device['version'],
                'employee_profile_id' => $employee_profile['id']
            ]);

            return response()
                ->json(["data" => $data, 'message' => "Success login."], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'revalidateAccessToken', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function signOut(Request $request)
    {
        try {
            $user = $request->user;

            $accessToken = $user->accessToken;

            foreach ($accessToken as $token) {
                $token->delete();
            }

            return response()->json(['message' => 'User signout.'], Response::HTTP_OK)->cookie(Helpers::Cookie_Name(), '', -1);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'signOut', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function verifyEmailAndSendOTP(Request $request)
    {
        try {
            $email = strip_tags($request->email);
            $contact = Contact::where('email_address', $email)->first();

            if (!$contact) {
                return response()->json(['message' => "Email doesn't exist."], Response::HTTP_UNAUTHORIZED);
            }

            $employee = $contact->personalInformation->employeeProfile;

            $data = Helpers::generateMyOTP($employee);

            if ($this->mail->send($data)) {
                return response()->json(['message' => 'Please check your email address for OTP.'], Response::HTTP_OK)
                    ->cookie('employee_details', json_encode(['email' => $email, 'employee_id' => $employee->employee_id]), 60, '/', env('SESSION_DOMAIN'), false);
            }

            return response()->json([
                'message' => 'Failed to send OTP to your email.'
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'signOut', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function signInWithOTP(Request $request)
    {
        try {
            $otp = strip_tags($request->otp);

            $employee_details = json_decode($request->cookie('employee_details'));

            $employee_profile = EmployeeProfile::where('employee_id', $employee_details->employee_id)->first();

            $message = Helpers::validateOTP($otp, $employee_profile);

            if ($message !== null) {
                return response()->json(['message' => $message], Response::HTTP_BAD_REQUEST);
            }

            $employee_profile->update([
                'otp' => null,
                'otp_expiration' => null
            ]);

            $agent = new Agent();
            $device = [
                'is_desktop' => $agent->isDesktop(),
                'is_mobile' => $agent->isMobile(),
                'platform' => $agent->platform(),
                'browser' => $agent->browser(),
                'version' => $agent->version($agent->browser())
            ];

            $token = $employee_profile->createToken();
            $assigned_area = $employee_profile->assignedArea;
            $plantilla = null;
            $designation = null;

            if ($assigned_area['plantilla_id'] === null) {
                $designation = $assigned_area->designation;
            } else {
                //Employment is plantilla retrieve the plantilla and its designation.
                $plantilla = $assigned_area->plantilla;
                $designation = $plantilla->designation;
            }

            $special_access_roles = $employee_profile->specialAccessRole;

            //Retrieve Sidebar Details for the employee base on designation.
            $side_bar_details = null;
            $trials = 2;

            //Retrieve Sidebar Details for the employee base on designation.
            do {
                $side_bar_details = $this->buildSidebarDetails($employee_profile, $designation, $special_access_roles);

                if (count($side_bar_details['system']) === 0) {
                    Cache::forget($designation['name']);
                    break;
                };

                $trials--;
            } while ($trials !== 0);

            if ($side_bar_details === null || count($side_bar_details['system']) === 0) {
                return response()->json([
                    'data' => $side_bar_details,
                    'message' => "Please be inform that your account currently doesn't have access to the system."
                ], Response::HTTP_UNAUTHORIZED);
            }

            $data = $this->generateEmployeeProfileDetails($employee_profile, $side_bar_details);

            LoginTrail::create([
                'signin_at' => now(),
                'ip_address' => $request->ip(),
                'device' => ($device['is_desktop'] ? 'Desktop' : $device['is_mobile']) ? 'Mobile' : 'Unknown',
                'platform' => is_bool($device['platform']) ? 'Postman' : $device['platform'],
                'browser' => is_bool($device['browser']) ? 'Postman' : $device['browser'],
                'browser_version' => is_bool($device['version']) ? 'Postman' : $device['version'],
                'employee_profile_id' => $employee_profile['id']
            ]);

            return response()
                ->json(['data' => $data, 'message' => "Success signin with two factor authentication."], Response::HTTP_OK)
                ->cookie(Helpers::Cookie_Name(), json_encode(['token' => $token]), 60, '/', env('SESSION_DOMAIN'), false);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'signOut', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function verifyOTP(Request $request)
    {
        try {
            $otp = strip_tags($request->otp);
            $employee_details = json_decode($request->cookie('employee_details'));

            $employee_profile = EmployeeProfile::where('employee_id', $employee_details->employee_id)->first();

            $message = Helpers::validateOTP($otp, $employee_profile);

            if ($message !== null) {
                return response()->json(['message' => $message], Response::HTTP_BAD_REQUEST);
            }

            $employee_profile->update([
                'otp' => null,
                'otp_expiration' => null
            ]);

            return response()->json(['message' => "Valid OTP, redirecting to new password form."], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'signOut', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updatePin(Request $request)
    {
        try{
            $employee_profile = $request->user;
            $password = strip_tags($request->password);
            $pin = strip_tags($request->pin);

            $decryptedPassword = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password . env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Employee id or password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $employee_profile->update(['authorization_pin' => $pin]);

            return response()->json([ 
                'data' => new EmployeeProfileResource($employee_profile),
                'message' => "Pin updated."], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'updatePin', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updatePassword(Request $request)
    {
        try{
            $employee_profile = $request->user;
            $password = strip_tags($request->password);
            $new_password = strip_tags($request->new_password);

            if ($this->CheckPasswordRepetition($password, 3, $employee_profile)) {
                return response()->json(['message' => "Please consider changing your password, as it appears you have reused an old password."], Response::HTTP_BAD_REQUEST);
            }

            $decryptedPassword = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password . env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_UNAUTHORIZED);
            }
            
            $hashPassword = Hash::make($new_password . env('SALT_VALUE'));
            $encryptedPassword = Crypt::encryptString($hashPassword);

            $threeMonths = Carbon::now()->addMonths(3);

            $old_password = PasswordTrail::create([
                'old_password' => $employee_profile->password_encrypted,
                'password_created_at' => $employee_profile->password_created_at,
                'expired_at' => $employee_profile->password_expiration_at,
                'employee_profile_id' => $employee_profile->id
            ]);

            if (!$old_password) {
                return response()->json(['message' => "A problem encounter while trying to register new password."], Response::HTTP_BAD_REQUEST);
            }

            $employee_profile->update([
                'password_encrypted' => $encryptedPassword,
                'password_created_at' => now(),
                'password_expiration_at' => $threeMonths
            ]);

            return response()->json(['message' => 'Password updated.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'updatePassword', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update2fa(Request $request)
    {
        try{
            $employee_profile = $request->user;
            $password = strip_tags($request->password);
            $status = strip_tags($request->status);

            $decryptedPassword = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password . env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_UNAUTHORIZED);
            }

            $employee_profile->update(['is_2fa' => $status]);

            return response()->json(['message' => '2fa updated.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'updatePassword', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function newPassword(Request $request)
    {
        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'password' => ['required', new StrongPassword],
            ]);

            // If validation fails
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
            }


            $employee_details = json_decode($request->cookie('employee_details'));

            $employee_profile = EmployeeProfile::where('employee_id', $employee_details->employee_id)->first();
            $new_password = strip_tags($request->password);

            $cleanData['password'] = strip_tags($request->input('password'));

            if ($this->CheckPasswordRepetition($cleanData, 3, $employee_profile)) {
                return response()->json(['message' => "Please consider changing your password, as it appears you have reused an old password."], Response::HTTP_BAD_REQUEST);
            }

            $hashPassword = Hash::make($new_password . env('SALT_VALUE'));
            $encryptedPassword = Crypt::encryptString($hashPassword);

            $threeMonths = Carbon::now()->addMonths(3);

            $old_password = PasswordTrail::create([
                'old_password' => $employee_profile->password_encrypted,
                'password_created_at' => $employee_profile->password_created_at,
                'expired_at' => $employee_profile->password_expiration_at,
                'employee_profile_id' => $employee_profile->id
            ]);

            if (!$old_password) {
                return response()->json(['message' => "A problem encounter while trying to register new password."], Response::HTTP_BAD_REQUEST);
            }

            $employee_profile->update([
                'password_encrypted' => $encryptedPassword,
                'password_created_at' => now(),
                'password_expiration_at' => $threeMonths,
                'is_2fa' => $request->two_factor ?? false,
                'authorization_pin' => strip_tags($request->pin)
            ]);

            $agent = new Agent();
            $device = [
                'is_desktop' => $agent->isDesktop(),
                'is_mobile' => $agent->isMobile(),
                'platform' => $agent->platform(),
                'browser' => $agent->browser(),
                'version' => $agent->version($agent->browser())
            ];

            $token = $employee_profile->createToken();
            $assigned_area = $employee_profile->assignedArea;
            $plantilla = null;
            $designation = null;

            if ($assigned_area['plantilla_id'] === null) {
                $designation = $assigned_area->designation;
            } else {
                //Employment is plantilla retrieve the plantilla and its designation.
                $plantilla = $assigned_area->plantilla;
                $designation = $plantilla->designation;
            }

            $special_access_roles = $employee_profile->specialAccessRole;

            //Retrieve Sidebar Details for the employee base on designation.
            $side_bar_details = null;
            $trials = 2;

            //Retrieve Sidebar Details for the employee base on designation.
            do {
                $side_bar_details = $this->buildSidebarDetails($employee_profile, $designation, $special_access_roles);

                if (count($side_bar_details['system']) === 0) {
                    Cache::forget($designation['name']);
                    break;
                };

                $trials--;
            } while ($trials !== 0);

            if ($side_bar_details === null || count($side_bar_details['system']) === 0) {
                return response()->json([
                    'data' => $side_bar_details,
                    'message' => "Please be inform that your account currently doesn't have access to the system."
                ], Response::HTTP_UNAUTHORIZED);
            }

            $data = $this->generateEmployeeProfileDetails($employee_profile, $side_bar_details);

            LoginTrail::create([
                'signin_at' => now(),
                'ip_address' => $request->ip(),
                'device' => ($device['is_desktop'] ? 'Desktop' : $device['is_mobile']) ? 'Mobile' : 'Unknown',
                'platform' => is_bool($device['platform']) ? 'Postman' : $device['platform'],
                'browser' => is_bool($device['browser']) ? 'Postman' : $device['browser'],
                'browser_version' => is_bool($device['version']) ? 'Postman' : $device['version'],
                'employee_profile_id' => $employee_profile['id']
            ]);

            return response()
                ->json(["data" => $data, 'message' => "Success login."], Response::HTTP_OK)
                ->cookie(Helpers::Cookie_Name(), json_encode(['token' => $token]), 60, '/', env('SESSION_DOMAIN'), false);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'newPassword', $th->getMessage());
        }
    }

    public function CheckPasswordRepetition($cleanData, $minimumReq, $employee_profile)
    {

        $my_old_password_collection = PasswordTrail::where('employee_profile_id', $employee_profile->id)->orderBy('created_at', 'desc')->limit(3)->get();

        foreach ($my_old_password_collection as $my_old_password) {
            $decryptedLastPassword = Crypt::decryptString($my_old_password->old_password);
            if (Hash::check($cleanData['password'] . env("SALT_VALUE"), $decryptedLastPassword)) {
                return true;
            }
        }

        return false;

        /** [SIMPLIFY PROBLEM] */
        // foreach ($getPasswordTrails as $key => $groupofPass) {
        //     $count = count($getPasswordTrails);
        //     $lastData = $getPasswordTrails[$count - 1];

        //     $decryptedLastPassword = Crypt::decryptString($lastData->old_password);
        //     if (Hash::check($cleanData['password'] . env("SALT_VALUE"), $decryptedLastPassword)) {
        //         return true;
        //     }
        //     if ($count <= $minimumReq) {
        //         $disallow1 = false;
        //         $limit = $count - 1;
        //         $otherpass = DB::select("SELECT * FROM `password_trails` where employee_profile_id ={$employee_profile->id} limit {$limit} OFFSET 0 ");
        //         if (count($otherpass) >= 1) {
        //             $disallow1 = false;
        //             foreach ($otherpass as $check) {
        //                 $level1Pass = Crypt::decryptString($check->old_password);
        //                 if (Hash::check($cleanData['password'] . env("SALT_VALUE"), $level1Pass)) {
        //                     $disallow1 = true;
        //                     break;
        //                 }
        //             }
        //             if ($disallow1) {
        //                 return true;
        //             }
        //         }
        //     }
        //     $limit = $minimumReq - 1;
        //     $offset = floor($count - ($limit + 1));
        //     $setofpassminReq = DB::select("SELECT * FROM `password_trails` where employee_profile_id ={$employee_profile->id} limit {$limit} OFFSET {$offset} ");
        //     if (count($setofpassminReq) >= 1) {
        //         $disallow2 = false;
        //         foreach ($setofpassminReq as $check) {
        //             $level2Pass = Crypt::decryptString($check->old_password);
        //             if (Hash::check($cleanData['password'] . env("SALT_VALUE"), $level2Pass)) {
        //                 $disallow2 = true;
        //                 break;
        //             }
        //         }
        //         if ($disallow2) {
        //             return true;
        //         }
        //     }
        // }
    }

    public function resetPassword($id, Request $request)
    {
        try {
            $user = $request->user;
            $cleanData['password'] = strip_tags($request->password);

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'] . env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_UNAUTHORIZED);
            }

            $employee_id = strip_tags($request->employee_id);

            $employee_profile = EmployeeProfile::find($id);

            if (!$employee_profile) {
                return response()->json(['message' => "Employee doesn't exist with id given."], Response::HTTP_NOT_FOUND);
            }

            $last_password = DefaultPassword::orderBy('effective_at', 'desc')->first();

            $hashPassword = Hash::make($last_password->password . env('SALT_VALUE'));
            $encryptedPassword = Crypt::encryptString($hashPassword);

            $employee_profile->update([
                'password_encrypted' => $encryptedPassword,
                'password_created_at' => Carbon::now(),
                'password_expiration_at' => Carbon::now()->addSeconds(10),
                'is_2fa' => false
            ]);

            return response()->json([
                'data' => $last_password->password,
                'message' => "Password has successfully reset."
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'resetPassword', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function reAssignArea($id, Request $request)
    {
        try {
            /**
             * REQUIREMENTS:
             *  id = employee_profile_id
             *  area = area id (division, department, section, unit)
             *  sector = area sector
             *  designation_id = job position id
             *  effective_date = effective
             */
            $employee_profile = EmployeeProfile::find($id);

            if (!$employee_profile) {
                return response()->json(['message' => "No employee record found with id " . $id], Response::HTTP_NOT_FOUND);
            }

            $area_details = null;
            $key_details = null;
            $designation_details = null;

            if ($request->designation_id !== null) {
                $designation = Designation::find($request->designation_id);

                if (!$designation) {
                    return response()->json(['message' => "No job position record found with id " . $id], Response::HTTP_NOT_FOUND);
                }

                $designation_details = $designation;
            }

            if ($request->area !== null) {
                $area = strip_tags($request->area);
                $sector = strip_tags($request->sector);

                switch ($sector) {
                    case "division":
                        $area_details = Division::find((int) $area);
                        if (!$area_details) {
                            return response()->json(['message' => 'No record found for division with id ' . $id], Response::HTTP_NOT_FOUND);
                        }
                        $key_details = 'division_id';
                        break;
                    case "department":
                        $area_details = Department::find((int) $area);
                        if (!$area_details) {
                            return response()->json(['message' => 'No record found for department with id ' . $id], Response::HTTP_NOT_FOUND);
                        }
                        $key_details = 'department_id';
                        break;
                    case "section":
                        $area_details = Section::find((int) $area);
                        if (!$area_details) {
                            return response()->json(['message' => 'No record found for section with id ' . $id], Response::HTTP_NOT_FOUND);
                        }
                        $key_details = 'section_id';
                        break;
                    case "unit":
                        $area_details = Unit::find((int) $area);
                        if (!$area_details) {
                            return response()->json(['message' => 'No record found for unit with id ' . $id], Response::HTTP_NOT_FOUND);
                        }
                        $key_details = 'unit_id';
                        break;
                    default:
                        return response()->json(['message' => 'Undefined area.'], Response::HTTP_BAD_REQUEST);
                }
            }

            $employee_previous_assign_area = $employee_profile->assignedArea;

            $area_new_data = [];
            $sector_list = ['division_id', 'department_id', 'section_id', 'unit_id'];

            foreach ($sector_list as $sector) {
                if ($sector !== $key_details) {
                    $area_new_data[$sector] = null;
                    continue;
                }
                $area_new_data[$key_details] = $area_details->id;
            }

            $employee_profile->assignedArea->update([
                ...$area_new_data,
                'designation_id' => $designation_details !== null ? $designation_details->id : $employee_profile->assignedArea->designation_id,
                'effective_date' => $request->effective_date
            ]);

            $new_trail = [];

            foreach ($employee_previous_assign_area as $key => $value) {
                if ($key === 'created_at' || $key === 'updated_at') continue;
                $new_trail[$key] = $value;
            }

            $new_trail['started_at'] = $employee_previous_assign_area['effective_at'];
            $new_trail['end_at'] = now();
            if (!isset($request->promotion)) {
                AssignAreaTrail::create($new_trail);
            }

            return response()->json([
                'data' => new EmployeeProfileResource($employee_profile),
                'message' => 'Designation records retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'reAssignArea', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updatePasswordExpiration($id, $sector, Request $request)
    {
        try {
            $employee_profile = $request->user;

            $now = Carbon::now();
            $threeMonths = $now->addMonths(3);

            $employee_profile->update(["password_expiration_at" => $threeMonths]);

            $agent = new Agent();
            $device = [
                'is_desktop' => $agent->isDesktop(),
                'is_mobile' => $agent->isMobile(),
                'platform' => $agent->platform(),
                'browser' => $agent->browser(),
                'version' => $agent->version($agent->browser())
            ];

            $access_token = AccessToken::where('employee_profile_id', $employee_profile->id)->orderBy('token_exp')->first();

            if ($access_token !== null && Carbon::parse(Carbon::now())->startOfDay()->lte($access_token->token_exp)) {
                $ip = $request->ip();

                $login_trail = LoginTrail::where('employee_profile_id', $employee_profile->id)->first();

                if ($login_trail->ip_address !== $ip) {
                    $data = Helpers::generateMyOTP($employee_profile);

                    if ($this->mail->send($data)) {
                        return response()->json(['message' => "You are currently logged on to other device. An OTP has been sent to your registered email. If you want to signout from that device, submit the OTP."], Response::HTTP_FOUND)
                            ->cookie('employee_details', json_encode(['employee_id' => $employee_profile->employee_id]), 60, '/', env('SESSION_DOMAIN'), false);
                    }

                    return response()->json(['message' => "Your account is currently logged on to other device, sending otp to your email has failed please try again later."], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            if ($access_token !== null) {
                AccessToken::where('employee_profile_id', $employee_profile->id)->delete();
            }

            /**
             * Validate for 2FA
             * if 2FA is activated send OTP to email to validate ownership
             */
            if ((bool) $employee_profile->is_2fa) {
                $data = Helpers::generateMyOTP($employee_profile);

                if ($this->mail->send($data)) {
                    return response()->json(['message' => "OTP has sent to your email, submit the OTP to verify that this is your account."], Response::HTTP_OK)
                        ->cookie('employee_details', json_encode(['employee_id' => $employee_profile->employee_id]), 60, '/', env('SESSION_DOMAIN'), false);
                }

                return response()->json([
                    'message' => 'Failed to send OTP to your email.'
                ], Response::HTTP_BAD_REQUEST);
            }

            $token = $employee_profile->createToken();
            $assigned_area = $employee_profile->assignedArea;
            $plantilla = null;
            $designation = null;

            if ($assigned_area['plantilla_id'] === null) {
                $designation = $assigned_area->designation;
            } else {
                //Employment is plantilla retrieve the plantilla and its designation.
                $plantilla = $assigned_area->plantilla;
                $designation = $plantilla->designation;
            }

            $special_access_roles = $employee_profile->specialAccessRole;

            $side_bar_details = null;
            $trials = 2;

            //Retrieve Sidebar Details for the employee base on designation.
            do {
                $side_bar_details = $this->buildSidebarDetails($employee_profile, $designation, $special_access_roles);

                if (count($side_bar_details['system']) === 0) {
                    Cache::forget($designation['name']);
                    break;
                };

                $trials--;
            } while ($trials !== 0);

            if ($side_bar_details === null || count($side_bar_details['system']) === 0) {
                return response()->json([
                    'data' => $side_bar_details,
                    'message' => "Please be inform that your account currently doesn't have access to the system."
                ], Response::HTTP_UNAUTHORIZED);
            }

            $data = $this->generateEmployeeProfileDetails($employee_profile, $side_bar_details);

            LoginTrail::create([
                'signin_at' => now(),
                'ip_address' => $request->ip(),
                'device' => ($device['is_desktop'] ? 'Desktop' : $device['is_mobile']) ? 'Mobile' : 'Unknown',
                'platform' => is_bool($device['platform']) ? 'Postman' : $device['platform'],
                'browser' => is_bool($device['browser']) ? 'Postman' : $device['browser'],
                'browser_version' => is_bool($device['version']) ? 'Postman' : $device['version'],
                'employee_profile_id' => $employee_profile['id']
            ]);

            return response()
                ->json(["data" => $data, 'message' => "Success login."], Response::HTTP_OK)
                ->cookie(Helpers::Cookie_Name(), json_encode(['token' => $token]), 60, '/', env('SESSION_DOMAIN'), false);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'updatePasswordExpiration', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesByAreaAssigned($id, $sector, Request $request)
    {
        try {
            $area = strip_tags($id);
            $sector = strip_tags($sector);
            $employees = [];
            $key = '';

            switch ($sector) {
                case 'division':
                    $key = 'division_id';
                    break;
                case 'department':
                    $key = 'department_id';
                    break;
                case 'section':
                    $key = 'section_id';
                    break;
                default:
                    $key = 'unit_id';
                    break;
            }

            $employees = AssignArea::with('employeeProfile')->where($key, $area)->get();

            Helpers::registerSystemLogs($request, null, true, 'Success in fetching a ' . $this->PLURAL_MODULE_NAME . '.');

            return response()->json([
                'data' => EmployeesByAreaAssignedResource::collection($employees),
                'message' => 'list of employees retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesDTRList(Request $request)
    {
        try {
            $employment_type_id = $request->employment_type_id;
            if ($employment_type_id !== null) {
                $employee_profiles = EmployeeProfile::where('employment_type_id', $employment_type_id)
                    ->get();

                return response()->json([
                    'data' => EmployeeDTRList::collection($employee_profiles),
                    'message' => 'list of employees retrieved.'
                ], Response::HTTP_OK);
            }

            $employee_profiles = EmployeeProfile::whereNotIn('id', [1,2,3,4,5])->get();
            Helpers::registerSystemLogs($request, null, true, 'Success in fetching a ' . $this->PLURAL_MODULE_NAME . '.');

            return response()->json([
                'data' => EmployeeDTRList::collection($employee_profiles),
                'message' => 'list of employees retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function reEmploy($id, Request $request)
    {
        try {

            $in_valid_file = false;
            $in_active_employee = InActiveEmployee::find($id);

            if (!$in_active_employee) {
                return response()->json(['message' => "No in active employee with id " . $id], Response::HTTP_NOT_FOUND);
            }

            $previous_employee_profile_id = $in_active_employee->employee_profile_id;

            $dateString = $request->date_hired;
            $carbonDate = Carbon::parse($dateString);
            $date_hired_string = $carbonDate->format('Ymd');

            $total_registered_this_day = EmployeeProfile::whereDate('date_hired', $carbonDate)->get();
            $employee_id_random_digit = 50 + count($total_registered_this_day);

            $employee_data = $in_active_employee;
            $employee_data['employee_id'] = $employee_id_random_digit;

            $last_registered_employee = EmployeeProfile::orderBy('biometric_id', 'desc')->first();
            $last_password = DefaultPassword::orderBy('effective_at', 'desc')->first();

            $hashPassword = Hash::make($last_password->password . env('SALT_VALUE'));
            $encryptedPassword = Crypt::encryptString($hashPassword);
            $now = Carbon::now();
            $fortyDaysFromNow = $now->addDays(40);
            // $fortyDaysExpiration = $now->addMinutes(5)->toDateTimeString();

            $new_biometric_id = $last_registered_employee->biometric_id + 1;
            $new_employee_id = $date_hired_string . $employee_id_random_digit;

            $employee_data['employee_id'] = $new_employee_id;
            $employee_data['biometric_id'] = $new_biometric_id;
            $employee_data['employment_type_id'] = strip_tags($request->employment_type_id);
            $employee_data['personal_information_id'] = $in_active_employee->personal_information_id;

            try {
                $fileName = Helpers::checkSaveFile($request->attachment, 'photo/profiles');
                if (is_string($fileName)) {
                    $employee_data['profile_url'] = $request->attachment === null  || $request->attachment === 'null' ? null : $fileName;
                }

                if (is_array($fileName)) {
                    $in_valid_file = true;
                    $employee_data['profile_url'] = null;
                }
            } catch (\Throwable $th) {
            }

            $employee_data['allow_time_adjustment'] = strip_tags($request->allow_time_adjustment) === 1 ? true : false;
            $employee_data['password_encrypted'] = $encryptedPassword;
            $employee_data['password_created_at'] = now();
            $employee_data['password_expiration_at'] = $fortyDaysFromNow;
            $employee_data['salary_grade_step'] = strip_tags($request->salary_grade_step);
            $employee_data['date_hired'] = $request->date_hired;
            $employee_data['designation_id'] = $request->designation_id;
            $employee_data['effective_at'] = $request->date_hired;


            $plantilla_number_id = $request->plantilla_number_id === "null"  || $request->plantilla_number_id === null ? null : $request->plantilla_number_id;
            $sector_key = '';

            switch (strip_tags($request->sector)) {
                case "division":
                    $sector_key = 'division_id';
                    break;
                case "department":
                    $sector_key = 'department_id';
                    break;
                case "section":
                    $sector_key = 'section_id';
                    break;
                case "unit":
                    $sector_key = 'unit_id';
                    break;
                default:
                    $sector_key = null;
            }

            if ($sector_key === null) {
                return response()->json(['message' => 'Invalid sector area.'], Response::HTTP_BAD_REQUEST);
            }

            $employee_data[$sector_key] = strip_tags($request->sector_id);

            if ($plantilla_number_id !== null) {
                $plantilla_number = PlantillaNumber::find($plantilla_number_id);



                if (!$plantilla_number) {
                    return response()->json(['message' => 'No record found for plantilla number ' . $plantilla_number_id], Response::HTTP_NOT_FOUND);
                }

                $plantilla = $plantilla_number->plantilla;
                $designation = $plantilla->designation;
                $employee_data['designation_id'] = $designation->id;
                $employee_data['plantilla_number_id'] = $plantilla_number->id;
            }


            $employee_profile = EmployeeProfile::create($employee_data);

            $employee_data['employee_profile_id'] = $employee_profile->id;
            AssignArea::create($employee_data);

            if ($plantilla_number_id !== null) {
                $plantilla_number = PlantillaNumber::find($plantilla_number_id);
                $plantilla_number->update(['employee_profile_id' => $employee_profile->id, 'is_vacant' => false, 'assigned_at' => now()]);
            }


            if ($plantilla_number_id !== null) {
                $leave_types = LeaveType::where('is_special', 0)->get();

                foreach ($leave_types as $leave_type) {
                    EmployeeLeaveCredit::create([
                        'employee_profile_id' => $employee_profile->id,
                        'leave_type_id' => $leave_type->id,
                        'total_leave_credits' => 0,
                        'used_leave_credits' => 0
                    ]);
                }
            }

            AssignAreaTrail::where(['employee_profile_id', $previous_employee_profile_id])->update(['employee_profile_id', $employee_profile->id]);

            Helpers::registerSystemLogs($request, $employee_profile->id, true, 'Success in creating a ' . $this->SINGULAR_MODULE_NAME . '.');


            if ($in_valid_file) {
                return response()->json(
                    [
                        'data' => new EmployeeProfileResource($employee_profile),
                        'message' => 'Newly employee registered.',
                        'other' => "Invalid attachment."
                    ],
                    Response::HTTP_OK
                );
            }

            return response()->json(
                [
                    'data' => new EmployeeProfileResource($employee_profile),
                    'message' => 'Newly employee registered.'
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'reEmploy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function index(Request $request)
    {
        try {
            $cacheExpiration = Carbon::now()->addDay();

            $employee_profiles = Cache::remember('employee_profiles', $cacheExpiration, function () {
                return EmployeeProfile::whereNotIn('id', [1,2,3,4,5])->get();
            });

            return EmployeeProfileResource::collection($employee_profiles);

            // return response()->json([
            //     'data' => EmployeeProfileResource::collection($employee_profiles),
            //     'message' => 'list of employees retrieved.'
            // ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function retrieveEmployees($employees, $key, $id, $myId){
        
        $assign_areas = AssignArea::where($key, $id)
            ->whereNotIn('employee_profile_id', $myId)->get();

        $new_employee_list = $assign_areas->map(function ($assign_area) {
            return $assign_area->employeeProfile;
        })->flatten()->all();
        
        return [...$employees, ...$new_employee_list];
    }

    public function myAllEmployees(Request $request)
    {
        try {
            $user = $request->user;
            $position = $user->position();
            $employees = [];

            if(!$position){
                return response()->json(['message' => "You don't have authorization as a supervisor of area."], Response::HTTP_UNAUTHORIZED);
            }

            $my_assigned_area = $user->assignedArea->findDetails();

            $employees = $this->retrieveEmployees($employees, Str::lower($my_assigned_area['sector'])."_id", $my_assigned_area['details']->id, [$user->id, 1,2,3,4,5]);
            
            /** Retrieve entire employees of Division to Unit if it has  unit */
            if($my_assigned_area['sector'] === 'Division'){
                $departments = Department::where('division_id', $my_assigned_area['details']->id)->get();

                foreach($departments as $department){
                    $employees = $this->retrieveEmployees($employees, 'department_id', $department->id, [$user->id, 1,2,3,4,5]);
                    $sections = Section::where('department_id', $department->id)->get();
                    foreach($sections as $section){
                        $employees = $this->retrieveEmployees($employees, 'section_id', $section->id, [$user->id, 1,2,3,4,5]);
                        $units = Unit::where('section_id', $section->id)->get();
                        foreach($units as $unit){
                            $employees = $this->retrieveEmployees($employees, 'unit_id', $unit->id, [$user->id, 1,2,3,4,5]);
                        }
                    }
                }
                
                $sections = Section::where('division_id', $my_assigned_area['details']->id)->get();
                foreach($sections as $section){
                    $employees = $this->retrieveEmployees($employees, 'section_id', $section->id, [$user->id, 1,2,3,4,5]);
                    $units = Unit::where('section_id', $section->id)->get();
                    foreach($units as $unit){
                        $employees = $this->retrieveEmployees($employees, 'unit_id', $unit->id, [$user->id, 1,2,3,4,5]);
                    }
                }
            }

            /** Retrieve entire emplyoees of Department to Unit */
            if($my_assigned_area['sector'] === 'Department'){
                $sections = Section::where('department_id', $my_assigned_area['details']->id)->get();
                foreach($sections as $section){
                    $employees = $this->retrieveEmployees($employees, 'section_id', $section->id, [$user->id, 1,2,3,4,5]);
                    $units = Unit::where('section_id', $section->id)->get();
                    foreach($units as $unit){
                        $employees = $this->retrieveEmployees($employees, 'unit_id', $unit->id, [$user->id, 1,2,3,4,5]);
                    }
                }
            }
            
            /** Retrieve entire employees of Section to Unit if it has Unit */
            if($my_assigned_area['sector'] === 'Section'){
                $units = Unit::where('section_id', $my_assigned_area['details']->id)->get();
                foreach($units as $unit){
                    $employees = $this->retrieveEmployees($employees, 'unit_id', $unit->id, [$user->id, 1,2,3,4,5]);
                }
            }

            return response()->json([
                'data' => EmployeeProfileResource::collection($employees),
                'message' => 'list of employees retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'myEmployees', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function myEmployees(Request $request)
    {
        try {
            $user = $request->user;
            $position = $user->position();

            if(!$position){
                return response()->json(['message' => "You don't have authorization as a supervisor of area."], Response::HTTP_UNAUTHORIZED);
            }

            $my_assigned_area = $user->assignedArea->findDetails();
            $key = Str::lower($my_assigned_area['sector'])."_id";

            $assign_areas = AssignArea::where($key, $my_assigned_area['details']->id)
                ->where('employee_profile_id', "<>",  $user->id)->get();

            $employees = $assign_areas->map(function ($assign_area) {
                return $assign_area->employeeProfile;
            })->flatten()->all();

            return response()->json([
                'data' => EmployeeProfileResource::collection($employees),
                'message' => 'list of employees retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'myEmployees', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function areasEmployees($id, Request $request)
    {
        try {
            $user = $request->user;
            $position = $user->position();
            $sector = strip_tags($request->sector);
            $key = Str::lower($sector)."_id";

            if(!$position){
                return response()->json(['message' => "You don't have authorization as a supervisor of area."], Response::HTTP_UNAUTHORIZED);
            }

            $assign_areas = AssignArea::where($key, $id)->get();

            $employees = $assign_areas->map(function ($assign_area) {
                return $assign_area->employeeProfile;
            })->flatten()->all();

            return response()->json([
                'data' => EmployeeProfileResource::collection($employees),
                'message' => 'list of employees retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'areasEmployees', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getEmployeeListByEmployementTypes(Request $request)
    {
        try {
            $cacheExpiration = Carbon::now()->addDay();

            $employee_profiles = Cache::remember('employee_profiles', $cacheExpiration, function () {
                return EmployeeProfile::whereNotIn('id', [1,2,3,4,5])->get();
            });

            $temp_perm = EmployeeProfileResource::collection($employee_profiles->filter(function ($profile) {
                return $profile->employment_type_id  == 1 || $profile->employment_type_id == 2;
            }) ?? []);

            $joborder = EmployeeProfileResource::collection($employee_profiles->filter(function ($profile) {
                return $profile->employment_type_id  == 3;
            }) ?? []);


            return response()->json([
                'data' => [
                    'permanent' => $temp_perm,
                    'joborder' => $joborder,
                ],
                'message' => 'list of employees retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'getEmployeeListByEmployementTypes', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $in_valid_file = false;

            $cleanData = [];
            $dateString = $request->date_hired;
            $carbonDate = Carbon::parse($dateString);
            $date_hired_string = $carbonDate->format('Ymd');

            $total_registered_this_day = EmployeeProfile::whereDate('date_hired', $carbonDate)->get();
            $employee_id_random_digit = 50 + count($total_registered_this_day);

            $last_registered_employee = EmployeeProfile::orderBy('biometric_id', 'desc')->first();
            $last_password = DefaultPassword::orderBy('effective_at', 'desc')->first();

            $hashPassword = Hash::make($last_password->password . env('SALT_VALUE'));
            $encryptedPassword = Crypt::encryptString($hashPassword);
            $now = Carbon::now();

            $twominutes = $now->addMinutes(2)->toDateTimeString();

            $new_biometric_id = $last_registered_employee->biometric_id + 1;
            $new_employee_id = $date_hired_string . $employee_id_random_digit;
            
            /** Create Authorization Pin */
            $personal_information = PersonalInformation::find(strip_tags($request->personal_information_id));


            $cleanData['employee_id'] = $new_employee_id;
            $cleanData['biometric_id'] = $new_biometric_id;
            $cleanData['employment_type_id'] = strip_tags($request->employment_type_id);
            $cleanData['personal_information_id'] = strip_tags($request->personal_information_id);
            try {
                $fileName = Helpers::checkSaveFile($request->attachment, 'photo/profiles');
                if (is_string($fileName)) {
                    $cleanData['profile_url'] = $request->attachment === null  || $request->attachment === 'null' ? null : $fileName;
                }

                if (is_array($fileName)) {
                    $in_valid_file = true;
                    $cleanData['profile_url'] = null;
                }
            } catch (\Throwable $th) {
            }
            $cleanData['allow_time_adjustment'] = strip_tags($request->allow_time_adjustment) === 1 ? true : false;
            $cleanData['password_encrypted'] = $encryptedPassword;
            $cleanData['password_created_at'] = now();
            $cleanData['password_expiration_at'] = $twominutes;
            $cleanData['salary_grade_step'] = strip_tags($request->salary_grade_step);
            $cleanData['date_hired'] = $request->date_hired;
            $cleanData['designation_id'] = $request->designation_id;
            $cleanData['effective_at'] = $request->date_hired;

            $plantilla_number_id = $request->plantilla_number_id === "null"  || $request->plantilla_number_id === null ? null : $request->plantilla_number_id;
            $sector_key = '';

            switch (strip_tags($request->sector)) {
                case "division":
                    $sector_key = 'division_id';
                    break;
                case "department":
                    $sector_key = 'department_id';
                    break;
                case "section":
                    $sector_key = 'section_id';
                    break;
                case "unit":
                    $sector_key = 'unit_id';
                    break;
                default:
                    $sector_key = null;
            }

            if ($sector_key === null) {
                return response()->json(['message' => 'Invalid sector area.'], Response::HTTP_BAD_REQUEST);
            }

            $cleanData[$sector_key] = strip_tags($request->sector_id);

            if ($plantilla_number_id !== null) {
                $plantilla_number = PlantillaNumber::find($plantilla_number_id);



                if (!$plantilla_number) {
                    return response()->json(['message' => 'No record found for plantilla number ' . $plantilla_number_id], Response::HTTP_NOT_FOUND);
                }

                $plantilla = $plantilla_number->plantilla;
                $designation = $plantilla->designation;
                $cleanData['designation_id'] = $designation->id;
                $cleanData['plantilla_number_id'] = $plantilla_number->id;
                $plantilla->update(['total_used_plantilla_no' => $plantilla->total_used_plantilla_no + 1]);
            }


            $employee_profile = EmployeeProfile::create($cleanData);

            $cleanData['employee_profile_id'] = $employee_profile->id;
            AssignArea::create($cleanData);

            if ($plantilla_number_id !== null) {
                $plantilla_number = PlantillaNumber::find($plantilla_number_id);
                $plantilla_number->update(['employee_profile_id' => $employee_profile->id, 'is_vacant' => false, 'assigned_at' => now()]);
            }


            if ($plantilla_number_id !== null) {
                $leave_types = LeaveType::where('is_special', 0)->get();

                foreach ($leave_types as $leave_type) {
                    EmployeeLeaveCredit::create([
                        'employee_profile_id' => $employee_profile->id,
                        'leave_type_id' => $leave_type->id,
                        'total_leave_credits' => 0,
                        'used_leave_credits' => 0
                    ]);
                }

                EmployeeOvertimeCredit::create([
                    'employee_profile_id' => $employee_profile->id,
                    'earned_credit_by_hour' => 0,
                    'used_credit_by_hour' => 0,
                    'max_credit_monthly' => 40,
                    'max_credit_annual' => 120
                ]);
            }

            Helpers::registerSystemLogs($request, $employee_profile->id, true, 'Success in creating a ' . $this->SINGULAR_MODULE_NAME . '.');


            if ($in_valid_file) {
                return response()->json(
                    [
                        'data' => new EmployeeProfileResource($employee_profile),
                        'message' => 'Newly employee registered.',
                        'other' => "Invalid attachment."
                    ],
                    Response::HTTP_OK
                );
            }

            return response()->json(
                [
                    'data' => new EmployeeProfileResource($employee_profile),
                    'message' => 'Newly employee registered.'
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // API [employee-profile-picture/{id}]
    public function updateEmployeeProfilePicture($id, Request $request)
    {
        try {
            $employee_profile = EmployeeProfile::find($id);

            if (!$employee_profile) {
                return response()->json(['message' => "No employee exist."], Response::HTTP_NOT_FOUND);
            }

            $profile_path = null;

            $fileName = Helpers::checkSaveFile($request->attachment, 'photo/profiles');
            if (is_string($fileName)) {
                $profile_path = $request->attachment === null  || $request->attachment === 'null' ? null : $fileName;
            }

            $employee_profile->update(['profile_url' => $profile_path]);

            return response()->json([
                "data" => new EmployeeProfileResource($employee_profile),
                "message" => "Successfully update employee profile."
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'updateEmployeeProfilePicture', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createEmployeeAccount($id, Request $request)
    {
        try {
            $employee_profile = EmployeeProfile::find($id);

            if (!$employee_profile) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $dateFromRequest = $employee_profile['date_hired'];
            $dateToRetrieve = Carbon::createFromFormat('Y-m-d', $dateFromRequest);
            $list_of_employee_by_date_hired = EmployeeProfile::whereDate('date_hired', $dateToRetrieve)->orderBy('created_at', 'desc')->get();

            /**
             * Convert date hired to a string format to use in Employee ID
             */
            $carbonDate = Carbon::createFromFormat('Y-m-d', $dateFromRequest);
            $date_hired_in_string = $carbonDate->format('Ymd');


            /**
             * Retrieve employee ID the last registered employee base on date
             * get the excess data it has, example if employee ID is 2023061152 the excess data is 52
             * then increment it with 1 to use to new employee as part of employee ID
             */
            $last_employee_id_registered_by_date = (int)substr($list_of_employee_by_date_hired, 8);
            $employee_id = $date_hired_in_string . $last_employee_id_registered_by_date++;

            /**
             * Generating default password for employee acount
             * Retrieve Default password assigned in the system
             * has the password with salt then encrypt the password
             */
            $currentDate = Carbon::now();
            $default_password = DefaultPassword::whereDate('effective_at', '<=', $currentDate)
                ->whereDate('end_at', '>=', $currentDate)
                ->whereDate('status', 1)
                ->get();

            $hashPassword = Hash::make($default_password['password'] . env('SALT_VALUE'));
            $password_encrypted = Crypt::encryptString($hashPassword);

            $password_created_at = Carbon::now();
            $password_expiration_at = Carbon::now()->addDays(60);

            $cleanData = [];
            $cleanData['employee_id'] = $employee_id;
            $cleanData['password_created_at'] = $password_created_at;
            $cleanData['password_encrypted'] = $password_encrypted;
            $cleanData['password_expiration_at'] = $password_expiration_at;

            $employee_profile->update($cleanData);

            /**
             * Trigger Email send here
             * Subject Your Employee Account
             * Employee IO and Default Password.
             * Additional content advice employee to register biometrics in IHOMP
             */

            Helpers::registerSystemLogs($request, $employee_profile->id, true, 'Success in creating a ' . $this->SINGULAR_MODULE_NAME . ' account.');

            return response()->json(['message' => 'Employee account created.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'createEmployeeAccount', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateEmployeeToInActiveEmployees($id, Request $request)
    {
        try {
            $user = $request->user;
            $cleanData['password'] = strip_tags($request->input('password'));

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'] . env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_UNAUTHORIZED);
            }

            $employee_profile = EmployeeProfile::find($id);

            if (!$employee_profile) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $in_active_employee = InActiveEmployee::create([
                'personal_information_id' => $employee_profile->personal_information_id,
                'employment_type_id' => $request->employment_type_id,
                'employee_id' => $employee_profile->employee_id,
                'profile_url' => $employee_profile->profile_url,
                'date_hired' => $employee_profile->date_hired,
                'biometric_id' => $employee_profile->biometric_id,
                'employment_end_at' => now()
            ]);

            $employee_profile->issuanceInformation->update([
                'employee_profile_id' => null,
                'in_active_employee_id' => $in_active_employee->id
            ]);

            $assign_area = $employee_profile->assignedArea;
            $assign_area_trail = AssignAreaTrail::create([
                'employee_profile_id' => null,
                'in_active_employee_id' => $in_active_employee->id,
                'designation_id' => $assign_area->designation_id,
                'plantilla_id' => $assign_area->plantilla_id,
                'division_id' => $assign_area->division_id,
                'department_id' => $assign_area->department_id,
                'section_id' => $assign_area->section_id,
                'unit_id' => $assign_area->unit_id,
                'plantilla_number_id' => $assign_area->plantilla_number_id,
                'salary_grade_step' => $assign_area->salary_grade_step,
                'started_at' => $assign_area->effective_at,
                'end_at' => now()
            ]);

            $assign_area->delete();
            $employee_profile->delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in fetching a ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json([
                'data' => $in_active_employee,
                'message' => 'Employee record transfer to in active employees.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'updateEmployeeToInActiveEmployees', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id, Request $request)
    {
        try {

            $employee_profile = EmployeeProfile::find($id);

            if (!$employee_profile) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            Helpers::registerSystemLogs($request, $id, true, 'Success in fetching a ' . $this->SINGULAR_MODULE_NAME . '.');

            $personal_information = $employee_profile->personalInformation;

            $assigned_area = $employee_profile->assignedArea;
            $plantilla = null;
            $designation = null;

            if ($assigned_area['plantilla_id'] === null) {
                $designation = $assigned_area->designation;
            } else {
                //Employment is plantilla retrieve the plantilla and its designation.
                $plantilla = $assigned_area->plantilla;
                $designation = $plantilla->designation;
            }


            $area_assigned = $employee_profile->assignedArea->findDetails();

            $position = $employee_profile->position();

            $last_login = LoginTrail::where('employee_profile_id', $employee_profile->id)->orderByDesc('created_at')->first();

            $employee = [
                'profile_url' => env('SERVER_DOMAIN') . "/photo/profiles/" . $employee_profile->profile_url,
                'employee_id' => $employee_profile->employee_id,
                'position' => $position,
                'job_position' => $designation->name,
                'date_hired' => $employee_profile->date_hired,
                'job_type' => $employee_profile->employmentType->name,
                'employment_type_id' => $employee_profile->employmentType->id,
                'years_of_service' => $employee_profile->personalInformation->years_of_service,
                'last_login' => $last_login === null ? null : $last_login->created_at,
                'biometric_id' => $employee_profile->biometric_id
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

            $address = [
                'residential_address' => null,
                'residential_zip_code' => null,
                'residential_telephone_no' => null,
                'permanent_address' => null,
                'permanent_zip_code' => null,
                'permanent_telephone_no' => null
            ];

            $addresses = $personal_information->addresses;

            foreach ($addresses as $value) {

                if ($value->is_residential_and_permanent) {
                    $address['residential_address'] = $value->address;
                    $address['residential_zip_code'] = $value->zip_code;
                    $address['residential_telephone_no'] = $value->telephone_no === null ? null : $value->telephone_no;
                    $address['permanent_address'] = $value->address;
                    $address['permanent_zip_code'] = $value->zip_code;
                    $address['permanent_telephone_no'] = $value->telephone_no === null ? null : $value->telephone_no;
                    break;
                }

                if ($value->is_residential) {
                    $address['residential_address'] = $value->address;
                    $address['residential_zip_code'] = $value->zip_code;
                    $address['residential_telephone_no'] = $value->telephone_no === null ? null : $value->telephone_no;
                } else {
                    $address['permanent_address'] = $value->address;
                    $address['permanent_zip_code'] = $value->zip_code;
                    $address['permanent_telephone_no'] = $value->telephone_no === null ? null : $value->telephone_no;
                }
            }

            $data = [
                'employee_profile_id' => $employee_profile['id'],
                'employee_id' => $employee_profile['employee_id'],
                'name' => $personal_information->employeeName(),
                'designation' => $designation['name'],
                'designation_code' => $designation['code'],
                'plantilla_number_id' => $assigned_area['plantilla_number_id'],
                'plantilla_number' => $assigned_area['plantilla_number_id'] === NULL ? NULL : $assigned_area->plantillaNumber['number'],
                'employee_details' => [
                    'employee' => $employee,
                    'personal_information' => $personal_information_data,
                    'contact' =>  new ContactResource($personal_information->contact),
                    'address' => $address,
                    'family_background' => new FamilyBackGroundResource($personal_information->familyBackground),
                    'children' => ChildResource::collection($personal_information->children),
                    'education' => EducationalBackgroundResource::collection($personal_information->educationalBackground),
                    'affiliations_and_others' => [
                        'civil_service_eligibility' => CivilServiceEligibilityResource::collection($personal_information->civilServiceEligibility),
                        'work_experience' => WorkExperienceResource::collection($personal_information->workExperience),
                        'voluntary_work_or_involvement' => VoluntaryWorkResource::collection($personal_information->voluntaryWork),
                        'training' => TrainingResource::collection($personal_information->training),
                        'other' => OtherInformationResource::collection($personal_information->otherInformation),
                    ],
                    'issuance' => $employee_profile->issuanceInformation,
                    'reference' => $employee_profile->personalInformation->references,
                    'legal_information' => $employee_profile->personalInformation->legalInformation,
                    'identification' => new IdentificationNumberResource($employee_profile->personalInformation->identificationNumber)
                ],
                'area' => $area_assigned,
                'area_assigned' => $area_assigned['details']->name,
                'area_sector' => $area_assigned['sector'],
            ];
            return response()->json(['data' => $data, 'message' => 'Employee details found.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByEmployeeID(Request $request)
    {
        try {
            $employee_id = $request->input('employee_id');
            $employee_profile = EmployeeProfile::where('employee_id', $employee_id)->first();

            if (!$employee_profile) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['data' => new EmployeeProfileResource($employee_profile), 'message' => 'Employee details found.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update needed as this will require approval of HR to update account
     * data to update of employee and attachment getting from Profile Update Request Table
     */
    public function update($id, Request $request)
    {
        try {
            $user = $request->user;
            $cleanData['password'] = strip_tags($request->input('password'));

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'] . env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_UNAUTHORIZED);
            }

            $employee_profile = EmployeeProfile::find($id);

            if (!$employee_profile) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if ($key === 'profile_image' && $value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }
                if ($key === 'profile_image') {
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $employee_profile->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating a ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(['data' => new EmployeeProfileResource($employee_profile), 'message' => 'Employee details updated.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateEmployeeProfile($id, EmployeeProfileRequest $request)
    {
        try {
            $user = $request->user;
            $cleanData['password'] = strip_tags($request->input('password'));

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'] . env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_UNAUTHORIZED);
            }

            $employee_profile = EmployeeProfile::find($id);

            if (!$employee_profile) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $file_value = $this->file_validation_and_upload->check_save_file($request->file('profile_image'), "employee/profiles");

            $employee_profile->update(['profile_url' => $file_value]);

            Helpers::registerSystemLogs($request, $employee_profile->id, true, 'Success in changing profile picture of an employee profile.');

            return response()->json(['data' => new EmployeeProfileResource($employee_profile), 'message' => 'Employee profile picture updated.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function promotion($id, PromotionRequest $request)
    {
        // password
        // effective_date
        // designation_id
        // period
        // area_assigned
        try {
            // $user = $request->user;
            // $cleanData['password'] = strip_tags($request->input('password'));
            // $decryptedPassword = Crypt::decryptString($user['password_encrypted']);
            // if (!Hash::check($cleanData['password'] . env("SALT_VALUE"), $decryptedPassword)) {
            //     return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_UNAUTHORIZED);
            // }


            $employee_profile = EmployeeProfile::findOrFail($id);
            $effective_date = $request->effective_date;
            $designation_id = $request->designation_id;
            $period = $request->period;
            $area_assigned = $request->area_assigned;
            $end_at = date('Y-m-d', strtotime("+" . $period . " months", strtotime($effective_date)));

            $parts = explode('-', $area_assigned);

            $areaid = trim($parts[0]);
            $sector = trim($parts[1]);


            $assigned = $employee_profile->assignedArea;

            $AssignareaRequest = new Request([
                'area' => $areaid,
                'sector' => $sector,
                'designation_id' => $designation_id,
                'effective_date' => $effective_date,
                'promotion' => true
            ]);
            $this->reAssignArea($id, $AssignareaRequest);
            $Promotion = [
                'designation_id' => $designation_id,
                'effective_at' => $effective_date,
                'end_date' => $end_at
            ];
            $trails = [
                'salary_grade_step' => $assigned->salary_grade_step,
                'employee_profile_id' => $assigned->employee_profile_id,
                'division_id' => $assigned->division_id,
                'department_id' => $assigned->department_id,
                'section_id' => $assigned->section_id,
                'unit_id' => $assigned->unit_id,
                'designation_id' => $assigned->designation_id,
                'plantilla_id' => $assigned->plantilla_id,
                'plantilla_number_id' => $assigned->plantilla_number_id,
                'started_at' => $assigned->effective_at,
                'end_at' => date('Y-m-d H:i:s')
            ];
            AssignAreaTrail::create($trails);
            AssignArea::where('id', $assigned->id)->update($Promotion);
            return response()->json(['message' => 'Employee successfully renewed.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'promotion', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function destroy($id, AuthPinApprovalRequest $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_UNAUTHORIZED);
            }

            $employee_profile = EmployeeProfile::findOrFail($id);

            if (!$employee_profile) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $employee_profile->delete();


            Helpers::registerSystemLogs($request, $employee_profile->id, true, 'Success in deleting a ' . $this->SINGULAR_MODULE_NAME . '.');


            return response()->json(['message' => 'Employee profile deleted.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function revokeRights($id, $access_right_id, AuthPinApprovalRequest $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_UNAUTHORIZED);
            }

            $employee_profile = EmployeeProfile::findOrFail($id);

            if (!$employee_profile) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $special_access_role = SpecialAccessRole::where("id", $access_right_id)->where('employee_profile_id', $employee_profile->id)->first();

            if (!$special_access_role) {
                return response()->json(['message' => "No special access right found."], Response::HTTP_NOT_FOUND);
            }
            $special_access_role->delete();

            Helpers::registerSystemLogs($request, $employee_profile->id, true, 'Success in deleting a ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(['message' => 'Special Access right has been revoke.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deactivateEmployeeAccount($id, Request $request)
    {
        try {
            $user = $request->user;
            $cleanData['password'] = strip_tags($request->password);

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'] . env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_UNAUTHORIZED);
            }

            $employee_profile = EmployeeProfile::findOrFail($id);

            if (!$employee_profile) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            if ($employee_profile->position() !== null) {
                $position = $employee_profile->position();
                $area = $employee_profile->assignedArea->findDetails();
                return response()->json(["message" => "Action is prohibited this employee currently a " . $position->position . " " . $area['details']->name . "."], Response::HTTP_FORBIDDEN);
            }

            $new_in_active = InActiveEmployee::create([
                'personal_information_id' => $employee_profile->personal_information,
                'employment_type_id' => $employee_profile->employment_type_id,
                'employee_id' => $employee_profile->employee_id,
                'profile_url' => $employee_profile->profile_url,
                'date_hired' => $employee_profile->date_hired,
                'biometic_id' => $employee_profile->biometic_id,
                'employment_end_at' => now(),
                'remarks' => strip_tags($request->remarks)
            ]);

            if (!$new_in_active) {
                return response()->json(['message' => "Failed to deactivate account."], Response::HTTP_BAD_REQUEST);
            }

            $plantilla_number = $employee_profile->assignedArea->plantillaNumber;
            $plantilla_number->update([
                'employee_profile_id' => null,
                'is_dissolve' => true
            ]);

            $assign_area = $employee_profile->assignedArea;

            $new_assign_area_data = $assign_area;
            $new_assign_area_data['employee_profile_id'] = null;
            $new_assign_area_data['in_active_employee_id'] = $new_in_active->id;
            $new_assign_area_data['end_at'] = now();

            AssignAreaTrail::create($new_assign_area_data);

            PasswordTrail::where('employee_profile_id', $employee_profile->id)->delete();
            LoginTrail::where('employee_profile_id', $employee_profile->id)->delete();
            AccessToken::where('employee_profile_id', $employee_profile->id)->delete();
            $employee_profile->delete();

            Helpers::registerSystemLogs($request, $employee_profile->id, true, 'Success in deleting a ' . $this->SINGULAR_MODULE_NAME . '.');


            return response()->json(['message' => 'Employee profile deleted.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
