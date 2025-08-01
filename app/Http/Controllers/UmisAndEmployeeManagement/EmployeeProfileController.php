<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Requests\CivilServiceEligibilityManyRequest;
use App\Http\Requests\ContactRequest;
use App\Http\Requests\EducationalBackgroundRequest;
use App\Http\Requests\EmployeeProfileNewResource;
use App\Http\Requests\FamilyBackgroundRequest;
use App\Http\Requests\IdentificationNumberRequest;
use App\Http\Requests\IssuanceInformationRequest;
use App\Http\Requests\LegalInformationManyRequest;
use App\Http\Requests\LegalInformationRequest;
use App\Http\Requests\OtherInformationManyRequest;
use App\Http\Requests\OtherInformationRequest;
use App\Http\Requests\PersonalInformationRequest;
use App\Http\Requests\ReferenceManyRequest;
use App\Http\Requests\ReferenceRequest;
use App\Http\Requests\TrainingManyRequest;
use App\Http\Requests\VoluntaryWorkRequest;
use App\Http\Requests\WorkExperienceRequest;
use App\Http\Resources\EmployeeProfileUpdateResource;
use App\Http\Resources\EmployeeRedcapModulesResource;
use App\Http\Resources\ProfileUpdateRequestResource;
use App\Jobs\SendEmailJob;
use App\Models\Address;
use App\Models\Child;
use App\Models\CivilServiceEligibility;
use App\Models\EducationalBackground;
use App\Models\EmployeeOvertimeCreditLog;
use App\Models\EmployeeRedcapModules;
use App\Models\EmployeeSchedule;
use App\Models\EmploymentType;
use App\Models\FailedLoginTrail;
use App\Models\FamilyBackground;
use App\Models\IdentificationNumber;
use App\Models\IssuanceInformation;
use App\Models\LegalInformation;
use App\Models\OfficerInChargeTrail;
use App\Models\OtherInformation;
use App\Models\PersonalInformation;
use App\Models\PlantillaAssignedArea;
use App\Models\Reference;
use App\Models\SystemUserSessions;
use App\Models\Training;
use App\Models\WorkExperience;
use Carbon\Carbon;

use App\Models\Role;
use App\Models\Unit;
use App\Models\Contact;
use App\Models\Section;
use App\Helpers\Helpers;
use App\Models\Division;
use App\Models\LeaveType;
use App\Models\AssignArea;
use App\Models\Department;
use App\Models\LoginTrail;
use App\Models\SystemRole;
use App\Methods\MailConfig;
use App\Models\AccessToken;
use App\Models\Designation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\PasswordTrail;
use App\Rules\StrongPassword;
use App\Models\AssignAreaTrail;
use App\Models\DefaultPassword;
use App\Models\EmployeeProfile;
use App\Models\PlantillaNumber;
use App\Models\InActiveEmployee;
use App\Models\SpecialAccessRole;
use App\Models\PositionSystemRole;
use App\Models\EmployeeLeaveCredit;
use App\Http\Controllers\Controller;
use App\Http\Requests\SignInRequest;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\ChildResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use App\Models\EmployeeOvertimeCredit;
use App\Http\Requests\PromotionRequest;
use App\Http\Resources\AddressResource;
use App\Http\Resources\ContactResource;
use App\Http\Resources\EmployeeDTRList;
use App\Http\Resources\TrainingResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\EmployeeProfileRequest;
use App\Http\Resources\VoluntaryWorkResource;
use App\Http\Resources\WorkExperienceResource;
use App\Http\Resources\EmployeeProfileResource;
use App\Http\Resources\FamilyBackGroundResource;
use App\Http\Resources\OtherInformationResource;
use App\Http\Resources\IdentificationNumberResource;
use App\Http\Controllers\DTR\TwoFactorAuthController;
use App\Http\Resources\EducationalBackgroundResource;
use App\Http\Resources\CivilServiceEligibilityResource;
use App\Http\Resources\EmployeesAssignedAreaResource;
use App\Http\Resources\EmployeesByAreaAssignedResource;
use App\Models\LeaveApplication;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use PhpParser\Node\Expr\Assign;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Tests\Localization\AsInTest;


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

    public function employeeListSample(Request $request)
    {
        return EmployeeProfile::limit(30)->offset(0)->get();
    }

    public function employeesCards(Request $request)
    {
        try {
            $active_users = EmployeeProfile::whereNot('id', 1)->whereNotNull('authorization_pin')->count();
            $pending_users = EmployeeProfile::whereNot('id', 1)->where('authorization_pin', NULL)->count();
            $regular_employees = EmployeeProfile::whereNot('id', 1)->whereNot('employee_id', NULL)->whereNot('employment_type_id', 5)->count();
            $job_orders = EmployeeProfile::whereNot('id', 1)->whereNot('employee_id', NULL)->where('employment_type_id', 5)->count();

            return response()->json([
                'data' => [
                    'active_users' => $active_users,
                    'pending_users' => $pending_users,
                    'regular_employees' => $regular_employees,
                    'job_orders' => $job_orders
                ],
                'message' => "Retrieve cards data."
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, "employeesCards", $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function profileUpdateRequest(Request $request)
    {
        try {

            $trainings = Training::where('is_request', 1)->where('approved_at', NULL)->get();
            $trainings = $trainings->map(function ($training) {
                $new_training = $training;
                $new_training['type'] = "Training";
                return $new_training;
            });

            $eligibilities = CivilServiceEligibility::where('is_request', 1)->where('approved_at', NULL)->get();
            $eligibilities = $eligibilities->map(function ($eligibility) {
                $new_eligibility = $eligibility;
                $new_eligibility["type"] = "Eligibility";
                return $new_eligibility;
            });

            $educations = EducationalBackground::where('is_request', 1)->where('approved_at', NULL)->get();
            $educations = $educations->map(function ($education) {
                $new_education = $education;
                $new_education['type'] = "Educational Background";
                return $new_education;
            });

            return response()->json([
                'data' => EmployeeProfileUpdateResource::collection([...$trainings, ...$eligibilities, ...$educations]),
                'message' => "Retrieve employees list for add record approval"
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'profileUpdateRequest', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function approvedProfileUpdate(Request $request)
    {
        try {
            $employee = $request->user;
            $pin = strip_tags($request->password);

            if ($employee->authorization_pin !== $pin) {
                return response()->json(['message' => "Invalid pin."], Response::HTTP_FORBIDDEN);
            }
            $profile_request = null;

            $type = strip_tags($request->type);

            switch ($type) {
                case "Educational Background":

                    $profile_request = EducationalBackground::find($request->id);
                    $profile_request->update([
                        "is_request" => false,
                        "approved_at" => Carbon::now()
                    ]);

                    break;
                case "Eligibility":
                    $profile_request = CivilServiceEligibility::find($request->id);
                    $profile_request->update([
                        "is_request" => false,
                        "approved_at" => Carbon::now()
                    ]);
                    break;
                case "Training":
                    $profile_request = Training::find($request->id);
                    $profile_request->update([
                        "is_request" => false,
                        "approved_at" => Carbon::now()
                    ]);
                    break;
            }

            return response()->json([
                'data' => $profile_request,
                'message' => "Successfully approved educational background update request"
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'approvedProfileUpdate', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeeForRenewal(Request $request)
    {
        try {
            $employees = EmployeeProfile::whereBetween('renewal', [Carbon::now(), Carbon::now()->addMonths(2)])->get();

            return response()->json([
                'data' => EmployeeProfileResource::collection($employees),
                'message' => "Retrieve employees list for renewal"
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeeForRenwal', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function renewEmployee(Request $request)
    {
        try {
            DB::beginTransaction();
            $employees = $request->employees;

            foreach ($request->employees as $employee_renewal) {
                try {
                    $employee = EmployeeProfile::find($employee_renewal->id);

                    if (EmploymentType::find($employee_renewal->employment_type_id)->name === 'Temporary') {
                        $employee->update(['renewal', Carbon::parse($employee->renewal)->addYear()]);
                    }
                    $renewal_date = strip_tags($employee_renewal->renewal);

                    $employee->update(['renewal' => $renewal_date]);
                } catch (\Throwable $th) {
                    DB::rollBack();
                    return response()->json(['message' => "Failed to renew please check fields."], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            DB::commit();

            return response()->json([
                'data' => EmployeeProfileResource::collection($employees),
                'message' => "Successfully renewed employee"
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'renewEmployee', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

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
            if ($request->persist_password !== null && (int) $request->persist_password === 1) {
                $fortyDaysFromNow = Carbon::now()->addDays(90);
                $fortyDaysExpiration = $fortyDaysFromNow->toDateTimeString();

                $employee_profile->update(['password_expiration_at' => $fortyDaysExpiration]);
            }

            if (!$employee_profile) {
                FailedLoginTrail::create(['employee_id' => $employee_profile->employee_id, 'employee_profile_id' => $employee_profile->id, 'message' => "[signIn]: Employee id or password incorrect."]);
                return response()->json(['message' => "Employee id or password incorrect."], Response::HTTP_FORBIDDEN);
            }

            if (!$employee_profile->isDeactivated()) {
                FailedLoginTrail::create(['employee_id' => $employee_profile->employee_id, 'employee_profile_id' => $employee_profile->id, 'message' => "[signIn]: Account is deactivated."]);
                return response()->json(['message' => "Account is deactivated."], Response::HTTP_FORBIDDEN);
            }


            $decryptedPassword = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($cleanData['password'] . config("app.salt_value"), $decryptedPassword)) {
                FailedLoginTrail::create(['employee_id' => $employee_profile->employee_id, 'employee_profile_id' => $employee_profile->id, 'message' => "[signIn]: Employee id or password incorrect."]);
                return response()->json(['message' => "Employee id or password incorrect."], Response::HTTP_FORBIDDEN);
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
                // $my_otp_details = Helpers::generateMyOTPDetails($employee_profile);
                // SendEmailJob::dispatch('email_verification', $my_otp_details['email'], $my_otp_details['name'], $my_otp_details['data']);

                return response()->json(['message' => 'New account'], Response::HTTP_TEMPORARY_REDIRECT)
                    ->cookie('employee_details', json_encode(['employee_id' => $employee_profile->employee_id]), 60, '/', config('app.session_domain'), false); //status 307
            }

            /**
             * For password expired.
             */
            $passwordExpirationDate = Carbon::parse($employee_profile->password_expiration_at);
            $passwordCreationDate = Carbon::parse($employee_profile->password_created_at);
            if ($employee_profile->password_expiration_at && $passwordExpirationDate->isPast()) {
                $currentYear = Carbon::now()->year;
                $passwordYear = $passwordCreationDate->year;

                $message = null;

                if($currentYear > $passwordYear){
                    $message = "Your account password has expired, it is mandatory to change the password.";
                }else{
                    $message = 'Your account password has reach 3 month olds, you can keep the same password by clicking signin anyway or better change password for your account security.';
                }

                return response()->json(['message' => $message], Response::HTTP_UNPROCESSABLE_ENTITY)
                ->cookie('employee_details', json_encode(['employee_id' => $employee_profile->employee_id]), 60, '/', config('app.session_domain'), false); //status 307
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

            /** Uncomment this if this module is already in need */
            // $access_token = AccessToken::where('employee_profile_id', $employee_profile->id)->orderBy('token_exp')->first();

            // if ($access_token !== null && Carbon::parse(Carbon::now())->startOfDay()->lte($access_token->token_exp)) {
            //     $ip = $request->ip();

            //Changes applied order by desc, old version in accurate due to wrong fetching of data
            //     $login_trail = LoginTrail::where('employee_profile_id', $employee_profile->id)->orderBy('created_at', 'desc')->first();

            //     if ($login_trail !== null) {
            //         if ($login_trail->ip_address !== $ip) {
            //             $my_otp_details = Helpers::generateMyOTPDetails($employee_profile);

            //             SendEmailJob::dispatch('otp', $my_otp_details['email'], $my_otp_details['name'], $my_otp_details['data']);

            //             return response()->json(['message' => "You are currently logged on to other device. An OTP has been sent to your registered email. If you want to signout from that device, submit the OTP."], Response::HTTP_FOUND)
            //                 ->cookie('employee_details', json_encode(['employee_id' => $employee_profile->employee_id]), 60, '/', config('app.session_domain'), false);
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
            if ((bool)$employee_profile->is_2fa) {
                $my_otp_details = Helpers::generateMyOTPDetails($employee_profile);

                SendEmailJob::dispatch('otp', $my_otp_details['email'], $my_otp_details['name'], $my_otp_details['data']);

                return response()->json(['message' => "OTP has sent to your email, submit the OTP to verify that this is your account."], Response::HTTP_FOUND)
                    ->cookie('employee_details', json_encode(['employee_id' => $employee_profile->employee_id]), 60, '/', config('app.session_domain'), false);
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
                FailedLoginTrail::create(['employee_id' => $employee_profile->employee_id, 'employee_profile_id' => $employee_profile->id, 'message' => "Please be inform that your account currently doesn't have access to the system."]);
                return response()->json([
                    'data' => $side_bar_details,
                    'message' => "Please be inform that your account currently doesn't have access to the system."
                ], Response::HTTP_FORBIDDEN);
            }

            $data = $this->generateEmployeeProfileDetails($employee_profile, $side_bar_details);
            $data['redcap_forms'] = $this->employeeRedcapModules($employee_profile);

            LoginTrail::create([
                'signin_at' => now(),
                'ip_address' => $request->ip(),
                'device' => ($device['is_desktop'] ? 'Desktop' : $device['is_mobile']) ? 'Mobile' : 'Unknown',
                'platform' => is_bool($device['platform']) ? 'Postman' : $device['platform'],
                'browser' => is_bool($device['browser']) ? 'Postman' : $device['browser'],
                'browser_version' => is_bool($device['version']) ? 'Postman' : $device['version'],
                'employee_profile_id' => $employee_profile['id']
            ]);

            Helpers::infoLog("EmployeeProfileController", "SignIn", config("app.session_domain"));

            return response()
                ->json(["data" => $data, 'message' => "Success login."], Response::HTTP_OK)
                ->cookie(config('app.cookie_name'), json_encode(['token' => $token]), 60, '/', config('app.session_domain'), true);
        } catch (\Throwable $th) {
            // FailedLoginTrail::create(['employee_id' => $employee_profile->employee_id, 'employee_profile_id' => $employee_profile->id, 'message' => "[signIn]: " . $th->getMessage()]);
            Helpers::errorLog($this->CONTROLLER_NAME, 'signIn', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeeRedcapModules($employee)
    {
        return EmployeeRedcapModulesResource::collection($employee->employeeRedcapModules);
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
                        $side_bar_details['system'][] = $this->buildSystemDetails($employee_profile['id'],$system_role);
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
                        $side_bar_details['system'][] = $this->buildSystemDetails($employee_profile['id'],$system_role);
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

                    $exists = array_search($system_role->system_id, array_column($side_bar_details['system'], 'id')) !== false;

                    /**
                     * If side bar details system array is empty
                     */
                    if (!$side_bar_details['system']) {
                        $side_bar_details['system'][] = $this->buildSystemDetails($employee_profile['id'],$system_role);
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

                    /** 
                     * On empty system this will direct insert the system
                     * Or
                     * when system is not empty but the target system doesn't exist this will append to it
                     */
                    if (count($side_bar_details['system']) === 0 || !$exists) {
                        $side_bar_details['system'][] = $this->buildSystemDetails($employee_profile['id'],$system_role);
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
                $reg_system_role = SystemRole::where('role_id', operator: $role->id)->first();

                $exists = array_search($reg_system_role->system_id, array_column($side_bar_details['system'], 'id')) !== false;

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

                /** 
                 * On empty system this will direct insert the system
                 * Or
                 * when system is not empty but the target system doesn't exist this will append to it
                 */
                if (count($side_bar_details['system']) === 0 || !$exists) {
                    $side_bar_details['system'][] = $this->buildSystemDetails($employee_profile['id'],$reg_system_role);
                }
            }

            if ($employment_type->name == "Job Order") {
                $role = Role::where("code", "COMMON-JO")->first();
                $jo_system_role = SystemRole::where('role_id', $role->id)->first();
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
                        if ($system['id'] === $jo_system_role->system_id) {
                            $system_role_exist = false;
    
                            // Check if role exist in the system
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
                }

                /** 
                 * On empty system this will direct insert the system
                 * Or
                 * when system is not empty but the target system doesn't exist this will append to it
                 */
                if (count($side_bar_details['system']) === 0 || !$exists) {
                    $side_bar_details['system'][] = $this->buildSystemDetails($employee_profile['id'],$jo_system_role);
                }
            }
        }

        $public_system_roles = PositionSystemRole::where('is_public', 1)->get();


        foreach($public_system_roles as $public_system_role)
        {
            $system_role_value = $public_system_role->systemRole;
            $exists = array_search($system_role_value->system_id, array_column($side_bar_details['system'], 'id')) !== false;

            if(count($side_bar_details['system']) === 0 || !$exists){
                $side_bar_details['system'][] = $this->buildSystemDetails($employee_profile['id'],$public_system_role->systemRole);
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
                        $public_system_roles_data = $this->buildRoleDetails($public_system_role->systemRole);

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

        return $side_bar_details;
    }

    //Storing of system sessions
    private function generateSystemSessionID($user_id, $system)
    {
        $sessionId = Str::uuid();
                
        SystemUserSessions::create([
            'user_id' => $user_id,
            'system_code' => $system['code'],
            'session_id' => $sessionId
        ]);

        $domain =  Crypt::decrypt($system['domain']);

        if($system['code'] === 'UMIS'){
            return $domain;
        }

        return $domain."/signing-in/".$sessionId;
    }

    private function buildSystemDetails($user_id, $system_role)
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
            'url' => $this->generateSystemSessionID($user_id, $system_role->system),
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
            'personal_information_id' => $personal_information->id,
            'employee_profile_id' => $employee_profile['id'],
            'employee_id' => $employee_profile['employee_id'],
            'name' => $personal_information->employeeName(),
            'is_2fa' => $employee_profile->is_2fa,
            'password_expiration_at' => $employee_profile->password_expiration_at,
            'password_updated_at' => $employee_profile->password_created_at,
            'pin_created_at' => $employee_profile->pin_created_at,
            'designation' => $designation['name'],
            'plantilla_number_id' => $assigned_area['plantilla_number_id'],
            'plantilla_number' => $assigned_area['plantilla_number_id'] === NULL ? NULL : $assigned_area->plantillaNumber['number'],
            'shifting' => $employee_profile->shifting,
            'employee_details' => [
                'employee' => $employee,
                'personal_information' => $personal_information_data,
                'contact' => $personal_information->contact === null ? null : new ContactResource($personal_information->contact),
                'address' => $address,
                'family_background' => $personal_information->familyBackground === null ? null : new FamilyBackGroundResource($personal_information->familyBackground),
                'children' => ChildResource::collection($personal_information->children),
                'education' => EducationalBackgroundResource::collection($personal_information->educationalBackground->filter(function ($row) {
                    return $row->is_request === 0;
                })),
                'affiliations_and_others' => [
                    'civil_service_eligibility' => CivilServiceEligibilityResource::collection($personal_information->civilServiceEligibility->filter(function ($row) {
                        return $row->is_request === 0;
                    })),
                    'work_experience' => WorkExperienceResource::collection($personal_information->workExperience),
                    'voluntary_work_or_involvement' => VoluntaryWorkResource::collection($personal_information->voluntaryWork),
                    'training' => TrainingResource::collection($personal_information->training->filter(function ($row) {
                        return $row->is_request === 0;
                    })),
                    'other' => OtherInformationResource::collection($personal_information->otherInformation),
                ],
                'issuance' => $employee_profile->issuanceInformation,
                'reference' => $employee_profile->personalInformation->references,
                'legal_information' => $employee_profile->personalInformation->legalInformation,
                'identification' => $employee_profile->personalInformation->identificationNumber === null ? null : new IdentificationNumberResource($employee_profile->personalInformation->identificationNumber)
            ],
            'area_assigned' => $area_assigned['details']->name,
            'area_sector' => $area_assigned['sector'],
            'area_id' => $area_assigned['details']->id,
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
                FailedLoginTrail::create(['employee_id' => $employee_profile->employee_id, 'employee_profile_id' => $employee_profile->id, 'message' => "[signOutFromOtherDevice]: Please be inform that your account currently doesn't have access to the system."]);
                return response()->json([
                    'data' => $side_bar_details,
                    'message' => "Please be inform that your account currently doesn't have access to the system."
                ], Response::HTTP_FORBIDDEN);
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
                ->cookie(config('app.cookie_name'), json_encode(['token' => $token]), 60, '/', config('app.session_domain'), false);
        } catch (\Throwable $th) {
            FailedLoginTrail::create(['employee_id' => $employee_profile->employee_id, 'employee_profile_id' => $employee_profile->id, 'message' => "[signOutFromOtherDevice]: " . $th->getMessage()]);
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
                ], Response::HTTP_FORBIDDEN);
            }

            $data = $this->generateEmployeeProfileDetails($employee_profile, $side_bar_details);
            $data['redcap_forms'] = $this->employeeRedcapModules($employee_profile);

            LoginTrail::create([
                'signin_at' => now(),
                'ip_address' => $request->ip(),
                'device' => ($device['is_desktop'] ? 'Desktop' : $device['is_mobile']) ? 'Mobile' : 'Unknown',
                'platform' => is_bool($device['platform']) ? 'Postman' : $device['platform'],
                'browser' => is_bool($device['browser']) ? 'Postman' : $device['browser'],
                'browser_version' => is_bool($device['version']) ? 'Postman' : $device['version'],
                'employee_profile_id' => $employee_profile['id']
            ]);
            
            Helpers::infoLog("Test", 'wasp', "Test");

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

            /**
             * Delete all System of user if it has
             */
            SystemUserSessions::where("user_id", $user->id)->delete();

            return response()->json(['message' => 'User signout.'], Response::HTTP_OK)->cookie(config('app.cookie_name'), '', -1);
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
                return response()->json(['message' => "Email verification failed. Please use the email you provided during registration."], Response::HTTP_FORBIDDEN);
            }

            $employee = $contact->personalInformation->employeeProfile;

            if ($employee->authorization_pin === null || $employee->authorization_pin === '') {
                return response()->json(['message' => "Your account password cannot be recovered as there has been no login activity recorded.
                "], Response::HTTP_FORBIDDEN);
            };

            $my_employee_details = Helpers::generateMyOTPDetails($employee);

            SendEmailJob::dispatch('otp', $my_employee_details['email'], $my_employee_details['name'], $my_employee_details['data']);

            return response()->json(['message' => 'Please check your email address for OTP.'], Response::HTTP_OK)
                ->cookie('employee_details', json_encode(['email' => $email, 'employee_id' => $employee->employee_id]), 60, '/', config('app.session_domain'), false);
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
                FailedLoginTrail::create(['employee_id' => $employee_profile->employee_id, 'employee_profile_id' => $employee_profile->id, 'message' => "[signInWithOTP]: Please be inform that your account currently doesn't have access to the system."]);
                return response()->json([
                    'data' => $side_bar_details,
                    'message' => "Please be inform that your account currently doesn't have access to the system."
                ], Response::HTTP_FORBIDDEN);
            }

            $data = $this->generateEmployeeProfileDetails($employee_profile, $side_bar_details);
            $data['redcap_forms'] = $this->employeeRedcapModules($employee_profile);

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
                ->cookie(config('app.cookie_name'), json_encode(['token' => $token]), 60, '/', config('app.session_domain'), false);
        } catch (\Throwable $th) {
            FailedLoginTrail::create(['employee_id' => $employee_profile->employee_id, 'employee_profile_id' => $employee_profile->id, 'message' => "[signInWithOTP]: " . $th->getMessage()]);
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

    public function resendOTP(Request $request)
    {
        try {
            $employee_details = json_decode($request->cookie('employee_details'));
            $employee_profile = EmployeeProfile::where('employee_id', $employee_details->employee_id)->first();

            $my_otp_details = Helpers::generateMyOTPDetails($employee_profile);
            SendEmailJob::dispatch('email_verification', $my_otp_details['email'], $my_otp_details['name'], $my_otp_details['data']);

            return response()->json(['message' => 'Please check your email for the otp.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'resendOTP', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updatePin(Request $request)
    {
        try {
            $employee_profile = $request->user;
            $password = strip_tags($request->password);
            $pin = strip_tags($request->pin);

            $decryptedPassword = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password . config("app.salt_value"), $decryptedPassword)) {
                return response()->json(['message' => "Employee id or password incorrect."], Response::HTTP_FORBIDDEN);
            }

            $employee_profile->update(['authorization_pin' => $pin, 'pin_created_at' => now()]);

            return response()->json([
                'data' => new EmployeeProfileResource($employee_profile),
                'message' => "Pin updated."
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'updatePin', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updatePassword(Request $request)
    {
        try {
            $employee_profile = $request->user;
            $password = strip_tags($request->password);
            $new_password = strip_tags($request->new_password);
            $cleanData = ["password" => $new_password];


            $decryptedPassword = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password . config('app.salt_value'), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_FORBIDDEN);
            }

            if ($this->CheckPasswordRepetition($cleanData, 3, $employee_profile)) {
                return response()->json(['message' => "Please consider changing your password, as it appears you have reused an old password."], Response::HTTP_BAD_REQUEST);
            }


            $hashPassword = Hash::make($new_password . config('app.salt_value'));
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
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'updatePassword', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update2fa(Request $request)
    {
        try {
            $employee_profile = $request->user;
            $password = strip_tags($request->password);
            $status = strip_tags($request->status);

            $decryptedPassword = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password . config('app.salt_value'), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_FORBIDDEN);
            }

            $employee_profile->update(['is_2fa' => $status]);

            return response()->json(['message' => '2fa updated.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
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

            $otp = null;

            // if ($request->is_recover || $request->is_recover === 1) {
            //     $otp = $request->otp;

            //     $currentDateTime = Carbon::now();
            //     if ((int) $otp !== $currentDateTime->greaterThan($otp->expires_at)) {
            //         return response()->json(['message' => 'OTP is expired, Resend otp.']);
            //     }

            //     if ((int) $otp !== $employee_profile->otp) {
            //         return response()->json(['message' => "Invalid OTP."], Response::HTTP_BAD_REQUEST);
            //     }
            // }

            $new_password = strip_tags($request->password);

            $cleanData['password'] = strip_tags($request->password);

            if ($this->CheckPasswordRepetition($cleanData, 3, $employee_profile)) {
                return response()->json(['message' => "Please consider changing your password, as it appears you have reused an old password."], Response::HTTP_BAD_REQUEST);
            }

            $hashPassword = Hash::make($new_password . config('app.salt_value'));
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

            if ($request->is_recover || $request->is_recover === 1) {
                $employee_profile->update([
                    'password_encrypted' => $encryptedPassword,
                    'password_created_at' => now(),
                    'password_expiration_at' => $threeMonths
                ]);
            } else {
                $employee_profile->update([
                    'email_verified_at' => Carbon::now(),
                    'password_encrypted' => $encryptedPassword,
                    'password_created_at' => now(),
                    'password_expiration_at' => $threeMonths,
                    'is_2fa' => $request->two_factor,
                    'authorization_pin' => strip_tags($request->pin),
                    'pin_created_at' => now()
                ]);
            }


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
                ], Response::HTTP_FORBIDDEN);
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
                ->cookie(config('app.cookie_name'), json_encode(['token' => $token]), 60, '/', config('app.session_domain'), false);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'newPassword', $th->getMessage());
        }
    }

    public function CheckPasswordRepetition($cleanData, $minimumReq, $employee_profile)
    {

        $my_old_password_collection = PasswordTrail::where('employee_profile_id', $employee_profile->id)->orderBy('created_at', 'desc')->limit(3)->get();

        foreach ($my_old_password_collection as $my_old_password) {
            $decryptedLastPassword = Crypt::decryptString($my_old_password->old_password);
            if (Hash::check($cleanData['password'] . config('app.salt_value'), $decryptedLastPassword)) {
                return true;
            }
        }

        return false;
    }

    public function resetPassword($id, Request $request)
    {
        try {
            $user = $request->user;
            $cleanData['password'] = strip_tags($request->password);

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'] . config('app.salt_value'), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_FORBIDDEN);
            }

            $employee_id = strip_tags($request->employee_id);

            $employee_profile = EmployeeProfile::find($id);

            if (!$employee_profile) {
                return response()->json(['message' => "Employee doesn't exist with id given."], Response::HTTP_NOT_FOUND);
            }

            $last_password = DefaultPassword::orderBy('effective_at', 'desc')->first();

            $hashPassword = Hash::make($last_password->password . config('app.salt_value'));
            $encryptedPassword = Crypt::encryptString($hashPassword);

            $now = Carbon::now();
            $threeMonths = $now->addMonths(3);

            $employee_profile->update([
                'password_encrypted' => $encryptedPassword,
                'password_created_at' => Carbon::now(),
                'password_expiration_at' => $threeMonths,
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
                        $area_details = Division::find((int)$area);
                        if (!$area_details) {
                            return response()->json(['message' => 'No record found for division with id ' . $id], Response::HTTP_NOT_FOUND);
                        }
                        $key_details = 'division_id';
                        break;
                    case "department":
                        $area_details = Department::find((int)$area);
                        if (!$area_details) {
                            return response()->json(['message' => 'No record found for department with id ' . $id], Response::HTTP_NOT_FOUND);
                        }
                        $key_details = 'department_id';
                        break;
                    case "section":
                        $area_details = Section::find((int)$area);
                        if (!$area_details) {
                            return response()->json(['message' => 'No record found for section with id ' . $id], Response::HTTP_NOT_FOUND);
                        }
                        $key_details = 'section_id';
                        break;
                    case "unit":
                        $area_details = Unit::find((int)$area);
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
                if ($key === 'created_at' || $key === 'updated_at')
                    continue;
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

    public function updatePasswordExpiration(Request $request)
    {
        try {
            $employee_details = json_decode($request->cookie('employee_details'));
            $employee_profile = EmployeeProfile::where('employee_id', $employee_details->employee_id)->first();

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

            // if ($access_token !== null && Carbon::parse(Carbon::now())->startOfDay()->lte($access_token->token_exp)) {
            //     $ip = $request->ip();

            //     $login_trail = LoginTrail::where('employee_profile_id', $employee_profile->id)->first();

            //     if ($login_trail->ip_address !== $ip) {
            //         $data = Helpers::generateMyOTP($employee_profile);

            //         if ($this->mail->send($data)) {
            //             return response()->json(['message' => "You are currently logged on to other device. An OTP has been sent to your registered email. If you want to signout from that device, submit the OTP."], Response::HTTP_FOUND)
            //                 ->cookie('employee_details', json_encode(['employee_id' => $employee_profile->employee_id]), 60, '/', config('app.session_domain'), false);
            //         }

            //         return response()->json(['message' => "Your account is currently logged on to other device, sending otp to your email has failed please try again later."], Response::HTTP_INTERNAL_SERVER_ERROR);
            //     }
            // }

            if ($access_token !== null) {
                AccessToken::where('employee_profile_id', $employee_profile->id)->delete();
            }

            /**
             * Validate for 2FA
             * if 2FA is activated send OTP to email to validate ownership
             */
            // if ((bool)$employee_profile->is_2fa) {
            //     $my_otp_details = Helpers::generateMyOTPDetails($employee_profile);

            //     SendEmailJob::dispatch('otp', $my_otp_details['email'], $my_otp_details['name'], $my_otp_details['data']);

            //     return response()->json(['message' => "OTP has sent to your email, submit the OTP to verify that this is your account."], Response::HTTP_FOUND)
            //         ->cookie('employee_details', json_encode(['employee_id' => $employee_profile->employee_id]), 60, '/', config('app.session_domain'), false);
            // }

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
                FailedLoginTrail::create(['employee_id' => $employee_profile->employee_id, 'employee_profile_id' => $employee_profile->id, 'message' => "Please be inform that your account currently doesn't have access to the system."]);
                return response()->json([
                    'data' => $side_bar_details,
                    'message' => "Please be inform that your account currently doesn't have access to the system."
                ], Response::HTTP_FORBIDDEN);
            }

            $data = $this->generateEmployeeProfileDetails($employee_profile, $side_bar_details);
            $data['redcap_forms'] = $this->employeeRedcapModules($employee_profile);

            LoginTrail::create([
                'signin_at' => now(),
                'ip_address' => $request->ip(),
                'device' => ($device['is_desktop'] ? 'Desktop' : $device['is_mobile']) ? 'Mobile' : 'Unknown',
                'platform' => is_bool($device['platform']) ? 'Postman' : $device['platform'],
                'browser' => is_bool($device['browser']) ? 'Postman' : $device['browser'],
                'browser_version' => is_bool($device['version']) ? 'Postman' : $device['version'],
                'employee_profile_id' => $employee_profile['id']
            ]);

            Helpers::infoLog("EmployeeProfileController", "SignIn", config("app.session_domain"));

            return response()
                ->json(["user" => $data, 'message' => "Success login."], Response::HTTP_OK)
                ->cookie(config('app.cookie_name'), json_encode(['token' => $token]), 60, '/', config('app.session_domain'), false);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'updatePasswordExpiration', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesForOIC(Request $request)
    {
        try {
            $employee_profile = $request->user;

            $is_mcc = Division::where('code', 'OMCC')->where('chief_employee_profile_id', $employee_profile->id)->first();

            if ($is_mcc) {
                $employees = EmployeeProfile::where('biometric_id', '<>', null)->where('authorization_pin', '<>', null)
                    ->where('id', '<>', $employee_profile->id)->get();

                return response()->json([
                    "data" => EmployeeProfileResource::collection($employees),
                    'message' => "Success login."
                ], Response::HTTP_OK);
            }


            $position = $employee_profile->position();
            $employees = [];

            if (!$position) {
                return response()->json(['message' => "You don't have authorization as a supervisor of area."], Response::HTTP_UNAUTHORIZED);
            }

            $my_assigned_area = $employee_profile->assignedArea->findDetails();

            $employees = $this->retrieveEmployees($employees, Str::lower($my_assigned_area['sector']) . "_id", $my_assigned_area['details']->id, [$employee_profile->id, 1]);

            /** Retrieve entire employees of Division to Unit if it has  unit */
            if ($my_assigned_area['sector'] === 'Division') {
                $departments = Department::where('division_id', $my_assigned_area['details']->id)->get();

                foreach ($departments as $department) {
                    $employees = $this->retrieveEmployees($employees, 'department_id', $department->id, [$employee_profile->id, 1]);
                    $sections = Section::where('department_id', $department->id)->get();
                    foreach ($sections as $section) {
                        $employees = $this->retrieveEmployees($employees, 'section_id', $section->id, [$employee_profile->id, 1]);
                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $employees = $this->retrieveEmployees($employees, 'unit_id', $unit->id, [$employee_profile->id, 1]);
                        }
                    }
                }

                $sections = Section::where('division_id', $my_assigned_area['details']->id)->get();
                foreach ($sections as $section) {
                    $employees = $this->retrieveEmployees($employees, 'section_id', $section->id, [$employee_profile->id, 1]);
                    $units = Unit::where('section_id', $section->id)->get();
                    foreach ($units as $unit) {
                        $employees = $this->retrieveEmployees($employees, 'unit_id', $unit->id, [$employee_profile->id, 1]);
                    }
                }
            }

            /** Retrieve entire emplyoees of Department to Unit */
            if ($my_assigned_area['sector'] === 'Department') {
                $sections = Section::where('department_id', $my_assigned_area['details']->id)->get();
                foreach ($sections as $section) {
                    $employees = $this->retrieveEmployees($employees, 'section_id', $section->id, [$employee_profile->id, 1]);
                    $units = Unit::where('section_id', $section->id)->get();
                    foreach ($units as $unit) {
                        $employees = $this->retrieveEmployees($employees, 'unit_id', $unit->id, [$employee_profile->id, 1]);
                    }
                }
            }

            /** Retrieve entire employees of Section to Unit if it has Unit */
            if ($my_assigned_area['sector'] === 'Section') {
                $units = Unit::where('section_id', $my_assigned_area['details']->id)->get();
                foreach ($units as $unit) {
                    $employees = $this->retrieveEmployees($employees, 'unit_id', $unit->id, [$employee_profile->id, 1]);
                }
            }

            return response()->json([
                "data" => EmployeeProfileResource::collection($employees),
                'message' => "Success login."
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'updatePasswordExpiration', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assign Officer in charge
     * This must be in division/department/section/unit
     * Validate first for rights to assigned OIC by password of chief/head/supervisor
     */
    public function assignOICByEmployeeID(Request $request)
    {
        try {
            $employee_profile = $request->user;
            // $cleanData['pin'] = strip_tags($request->password);

            // if ($employee_profile['authorization_pin'] !==  $cleanData['pin']) {
            //     return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            // }

            $area_details = $employee_profile->assignedArea->findDetails();
            $area = null;

            switch ($area_details['sector']) {
                case 'Division':
                    $area = Division::where('chief_employee_profile_id', $employee_profile->id)->first();
                    break;
                case 'Department':
                    $area = Department::where('head_employee_profile_id', $employee_profile->id)->first();
                    break;
                case 'Section':
                    $area = Section::where('supervisor_employee_profile_id', $employee_profile->id)->first();
                    break;
                case 'Unit':
                    $area = Unit::where('head_employee_profile_id', $employee_profile->id)->first();
                    break;
                default:
                    return response()->json(['message' => "Invalid sector."], Response::HTTP_BAD_REQUEST);
            }

            if (!$area)
                return response()->json(['message' => "forbidden"], Response::HTTP_FORBIDDEN);

            $area->update(['oic_employee_profile_id' => strip_tags($request->OIC)]);

            Helpers::registerSystemLogs($request, null, true, 'Success in assigning chief ' . $this->PLURAL_MODULE_NAME . '.');

            $response = [
                'id' => $area->id,
                'name' => $area->name,
                'code' => $area->code,
                'oic' => $area->oic->personalInformation->name(),
                'position' => $area->oic->assignedArea->designation->name,
                'updated_at' => $area->updated_at
            ];

            return response()->json([
                'data' => $response,
                'message' => 'New officer incharge assign in department.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'assignOICByEmployeeID', $th->getMessage());
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
            if (isset($request->employment_type_id) && $request->employment_type_id !== null) {
                $employee_profiles = EmployeeProfile::where('employment_type_id', $request->employment_type_id)
                    ->get();


                return response()->json([
                    'data' => EmployeeDTRList::collection($employee_profiles),
                    'message' => 'list of employees retrieved.'
                ], Response::HTTP_OK);
            }

            $employee_profiles = EmployeeProfile::whereNotIn('id', [1])->whereNot('employee_id', null)->get();
            Helpers::registerSystemLogs($request, null, true, 'Success in fetching a ' . $this->PLURAL_MODULE_NAME . '.');

            return response()->json([
                'data' => EmployeeDTRList::collection($employee_profiles),
                'message' => 'list of employees retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeesDTRList', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display a searchable list of employee profiles.
     *
     * This method retrieves a list of active employee profiles
     * and returns them in a JSON response. If a search term is provided,
     * the method filters the employee profiles based on the search term.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request.
     * @return JsonResponse The JSON response containing the employee profiles.
     */
    public function indexDropdown(Request $request)
    {
        try {
            // Create the base query for active employee profiles, excluding the profile with id 1
            $query = EmployeeProfile::with('personalInformation')
                ->whereNotIn('employee_profiles.id', [1])
                ->whereNull('employee_profiles.deactivated_at');

            // Apply search filter if a search term is provided
            if ($search = $request->input('search')) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('personalInformation', function ($q) use ($search) {
                        if (!empty($search)) {
                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                        }
                    });
                });
            }

            // Execute the query to get all filtered results
            $employeeProfiles = $query->get();

            // Return a JSON response with the employee profiles
            return response()->json([
                'data' => EmployeeProfileResource::collection($employeeProfiles), // All matching employee profiles
                'total' => count($employeeProfiles), // Total number of items
                'message' => 'List of employees retrieved.' // Success message
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            // Log any exceptions and return an error response
            Helpers::errorLog($this->CONTROLLER_NAME, 'indexDropdown', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Display a paginated and searchable list of employee profiles.
     *
     * This method retrieves a paginated list of active employee profiles
     * and returns them in a JSON response along with pagination metadata.
     * The data is fetched in chunks of 10 rows per page to improve performance.
     * If a search term is provided, the method filters the employee profiles
     * based on the search term before applying pagination.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request.
     * @return JsonResponse The JSON response containing the employee profiles and pagination metadata.
     */

    public function index(Request $request)
    {
        try {
            $cacheExpiration = Carbon::now()->addDay();

            $employee_profiles = Cache::remember('employee_profiles', $cacheExpiration, function () {
                // Modify the query to exclude deactivated employees and employee profile ID 1
                return EmployeeProfile::whereNull('deactivated_at')
                    ->where('id', '!=', 1)
                    ->get();
            });

            // Return a JSON response with the paginated employee profiles and pagination metadata
            return response()->json([
                'count' => $employee_profiles->count(),
                'data' => EmployeeProfileResource::collection($employee_profiles), // Current page items
                'message' => 'List of employees retrieved.' // Success message
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            // Log any exceptions and return an error response
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function employeeRecords(Request $request): JsonResponse
    {
        try {
            $employees = collect();
            $sector = $request->query('sector');
            $area_id = $request->query('area_id');
            $designation_id = $request->query('designation_id');
            $employment_type = $request->query('employment_type_id');
            $search = $request->query('search');
            $per_page = 10;
            $current_page = $request->query('page', 1); // Get the page from request, default is 1

            if ((!$sector && $area_id) || ($sector && !$area_id)) {
                return response()->json(['message' => 'Invalid sector or area id input'], 400);
            }

            if (!$sector && !$area_id) {
                $employees = AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                    $query->whereNull('deactivated_at');
                }])
                    ->where('employee_profile_id', '<>', 1)
                    ->when($search, function ($query) use ($search) {
                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                            if (!empty($search)) {
                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%')->orWhere('employee_id', 'LIKE', '%' . $search . '%');
                            }
                        });
                    })->when($designation_id, function ($query) use ($designation_id) {
                        $query->where('designation_id', $designation_id);
                    })->when($employment_type, function ($query) use ($employment_type) {
                        $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                            $q->where('employment_type_id', $employment_type);
                        });
                    })
                    ->get();
            } else {
                switch ($sector) {
                    case 'division':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })->when($designation_id, function ($query) use ($designation_id) {
                                    $query->where('designation_id', $designation_id);
                                })->when($employment_type, function ($query) use ($employment_type) {
                                    $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                        $q->where('employment_type_id', $employment_type);
                                    });
                                })
                                ->get()
                        );

                        $departments = Department::where('division_id', $area_id)->get();
                        foreach ($departments as $department) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('department_id', $department->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })->when($designation_id, function ($query) use ($designation_id) {
                                        $query->where('designation_id', $designation_id);
                                    })->when($employment_type, function ($query) use ($employment_type) {
                                        $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                            $q->where('employment_type_id', $employment_type);
                                        });
                                    })
                                    ->get()
                            );

                            $sections = Section::where('department_id', $department->id)->get();
                            foreach ($sections as $section) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('section_id', $section->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })->when($designation_id, function ($query) use ($designation_id) {
                                            $query->where('designation_id', $designation_id);
                                        })->when($employment_type, function ($query) use ($employment_type) {
                                            $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                                $q->where('employment_type_id', $employment_type);
                                            });
                                        })
                                        ->get()
                                );

                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $employees = $employees->merge(
                                        AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                            $query->whereNull('deactivated_at');
                                        }])
                                            ->where('unit_id', $unit->id)
                                            ->where('employee_profile_id', '<>', 1)
                                            ->when($search, function ($query) use ($search) {
                                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                    if (!empty($search)) {
                                                        $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                            ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                    }
                                                });
                                            })->when($designation_id, function ($query) use ($designation_id) {
                                                $query->where('designation_id', $designation_id);
                                            })->when($employment_type, function ($query) use ($employment_type) {
                                                $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                                    $q->where('employment_type_id', $employment_type);
                                                });
                                            })
                                            ->get()
                                    );
                                }
                            }
                        }

                        // Get sections directly under the division (if any) that are not under any department
                        $sections = Section::where('division_id', $area_id)->whereNull('department_id')->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })->when($designation_id, function ($query) use ($designation_id) {
                                        $query->where('designation_id', $designation_id);
                                    })->when($employment_type, function ($query) use ($employment_type) {
                                        $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                            $q->where('employment_type_id', $employment_type);
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })->when($designation_id, function ($query) use ($designation_id) {
                                            $query->where('designation_id', $designation_id);
                                        })->when($employment_type, function ($query) use ($employment_type) {
                                            $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                                $q->where('employment_type_id', $employment_type);
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'department':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('department_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })->when($designation_id, function ($query) use ($designation_id) {
                                    $query->where('designation_id', $designation_id);
                                })->when($employment_type, function ($query) use ($employment_type) {
                                    $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                        $q->where('employment_type_id', $employment_type);
                                    });
                                })
                                ->get()
                        );

                        $sections = Section::where('department_id', $area_id)->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })->when($designation_id, function ($query) use ($designation_id) {
                                        $query->where('designation_id', $designation_id);
                                    })->when($employment_type, function ($query) use ($employment_type) {
                                        $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                            $q->where('employment_type_id', $employment_type);
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })->when($designation_id, function ($query) use ($designation_id) {
                                            $query->where('designation_id', $designation_id);
                                        })->when($employment_type, function ($query) use ($employment_type) {
                                            $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                                $q->where('employment_type_id', $employment_type);
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'section':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('section_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })->when($designation_id, function ($query) use ($designation_id) {
                                    $query->where('designation_id', $designation_id);
                                })->when($employment_type, function ($query) use ($employment_type) {
                                    $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                        $q->where('employment_type_id', $employment_type);
                                    });
                                })
                                ->get()
                        );

                        $units = Unit::where('section_id', $area_id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('unit_id', $unit->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })->when($designation_id, function ($query) use ($designation_id) {
                                        $query->where('designation_id', $designation_id);
                                    })->when($employment_type, function ($query) use ($employment_type) {
                                        $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                            $q->where('employment_type_id', $employment_type);
                                        });
                                    })
                                    ->get()
                            );
                        }
                        break;

                    case 'unit':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('unit_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })->when($designation_id, function ($query) use ($designation_id) {
                                    $query->where('designation_id', $designation_id);
                                })->when($employment_type, function ($query) use ($employment_type) {
                                    $query->whereHas('employeeProfile', function ($q) use ($employment_type) {
                                        $q->where('employment_type_id', $employment_type);
                                    });
                                })
                                ->get()
                        );
                        break;
                    default:
                        return response()->json(['message' => 'Invalid input. Please input a valid sector'], 400);
                }
            }

            // After all merge operations
            $employees = $employees->unique('employee_profile_id');
            // Sort employees by first name
            $employees = $employees->sortBy(function ($employee) {
                return $employee->employeeProfile->personalInformation->first_name;
            });

            $paginated_employees = new LengthAwarePaginator(
                $employees->forPage($current_page, 10),
                $employees->count(),
                $per_page,
                $current_page,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );

            // Transform and paginate employee data
            $data = EmployeeProfileResource::collection($paginated_employees->pluck('employeeProfile'));

            return response()->json([
                'pagination' => [
                    'current_page' => $paginated_employees->currentPage(),
                    'per_page' => $paginated_employees->perPage(),
                    'total' => $paginated_employees->total(),
                    'last_page' => $paginated_employees->lastPage(),
                    'has_more_pages' => $paginated_employees->hasMorePages(),
                ],
                'count' => $paginated_employees->count(),
                'data' => $data,
                'message' => 'List of employee records retrieved'
            ], ResponseAlias::HTTP_OK);
        } catch (\Throwable $th) {
            // Log any exceptions and return an error response
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getUserListMentions(Request $request)
    {
        try {
            $user = $request->user;


            $employee_profiles = EmployeeProfile::whereNotIn('id', [1, $user->id])->where('deactivated_at', NULL)->get();

            $filteredUsers = $employee_profiles->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->personalInformation->fullName(),
                    'avatar' => config("app.server_domain") . "/photo/profiles/" . $user->profile_url,
                ];
            });

            return response()->json([
                'data' => $filteredUsers,
                'message' => 'list of employees retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function retrieveEmployees($employees, $key, $id, $myId)
    {

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

            if (!$position) {
                return response()->json(['message' => "You don't have authorization as a supervisor of area."], Response::HTTP_FORBIDDEN);
            }

            $my_assigned_area = $user->assignedArea->findDetails();

            $employees = $this->retrieveEmployees($employees, Str::lower($my_assigned_area['sector']) . "_id", $my_assigned_area['details']->id, [$user->id, 1]);

            /** Retrieve entire employees of Division to Unit if it has  unit */
            if ($my_assigned_area['sector'] === 'Division') {
                $departments = Department::where('division_id', $my_assigned_area['details']->id)->get();

                foreach ($departments as $department) {
                    $employees = $this->retrieveEmployees($employees, 'department_id', $department->id, [$user->id, 1]);
                    $sections = Section::where('department_id', $department->id)->get();
                    foreach ($sections as $section) {
                        $employees = $this->retrieveEmployees($employees, 'section_id', $section->id, [$user->id, 1]);
                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $employees = $this->retrieveEmployees($employees, 'unit_id', $unit->id, [$user->id, 1]);
                        }
                    }
                }

                $sections = Section::where('division_id', $my_assigned_area['details']->id)->get();
                foreach ($sections as $section) {
                    $employees = $this->retrieveEmployees($employees, 'section_id', $section->id, [$user->id, 1]);
                    $units = Unit::where('section_id', $section->id)->get();
                    foreach ($units as $unit) {
                        $employees = $this->retrieveEmployees($employees, 'unit_id', $unit->id, [$user->id, 1]);
                    }
                }
            }

            /** Retrieve entire emplyoees of Department to Unit */
            if ($my_assigned_area['sector'] === 'Department') {
                $sections = Section::where('department_id', $my_assigned_area['details']->id)->get();
                foreach ($sections as $section) {
                    $employees = $this->retrieveEmployees($employees, 'section_id', $section->id, [$user->id, 1]);
                    $units = Unit::where('section_id', $section->id)->get();
                    foreach ($units as $unit) {
                        $employees = $this->retrieveEmployees($employees, 'unit_id', $unit->id, [$user->id, 1]);
                    }
                }
            }

            /** Retrieve entire employees of Section to Unit if it has Unit */
            if ($my_assigned_area['sector'] === 'Section') {
                $units = Unit::where('section_id', $my_assigned_area['details']->id)->get();
                foreach ($units as $unit) {
                    $employees = $this->retrieveEmployees($employees, 'unit_id', $unit->id, [$user->id, 1]);
                }
            }

            return response()->json([
                'data' => EmployeeProfileResource::collection($employees),
                'message' => 'list of employees retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'myAllEmployees', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function myEmployees(Request $request)
    {
        try {
            $user = $request->user;
            $position = $user->position();

            if (!$position) {
                return response()->json(['message' => "You don't have authorization as a supervisor of area."], Response::HTTP_FORBIDDEN);
            }

            $my_assigned_area = $user->assignedArea->findDetails();
            $key = Str::lower($my_assigned_area['sector']) . "_id";

            $assign_areas = AssignArea::where($key, $my_assigned_area['details']->id)
                ->where('employee_profile_id', "<>", $user->id)->get();

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

    public function getAllEmployees(Request $request)
    {
        try {
            $user = $request->user;
            $title_print = $user->assignedArea->findDetails()['details']['name'];

            $position = $user->position();
            if (!$position) {
                return response()->json(['message' => "You don't have authorization as a supervisor of area."], Response::HTTP_FORBIDDEN);
            }

            $search = $request->query('search') ?? ''; // Use empty string if no search query
            $sector = $request->query('sector'); // Use user's assigned sector if not provided
            $area_id = $request->query('area_id'); // Use user's assigned area_id if not provided

            $is_print = $request->query('is_print');
            $employees = collect();

            // If sector is empty or null, fetch the sector assigned to the user
            if (empty($sector) && empty($area_id)) {
                $sector = Str::lower($user->assignedArea->findDetails()['sector']);
                $area_id = $user->assignedArea->findDetails()['details']['id'];

                switch ($sector) {
                    case 'division':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $departments = Department::where('division_id', $area_id)->get();
                        foreach ($departments as $department) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('department_id', $department->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $sections = Section::where('department_id', $department->id)->get();
                            foreach ($sections as $section) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('section_id', $section->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );

                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $employees = $employees->merge(
                                        AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                            $query->whereNull('deactivated_at');
                                        }])
                                            ->where('unit_id', $unit->id)
                                            ->where('employee_profile_id', '<>', 1)
                                            ->when($search, function ($query) use ($search) {
                                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                    if (!empty($search)) {
                                                        $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                            ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                    }
                                                });
                                            })
                                            ->get()
                                    );
                                }
                            }
                        }

                        // Get sections directly under the division (if any) that are not under any department
                        $sections = Section::where('division_id', $area_id)->whereNull('department_id')->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'department':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('department_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $sections = Section::where('department_id', $area_id)->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                        $query->whereNull('deactivated_at');
                                    }])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->when($search, function ($query) use ($search) {
                                            $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                                if (!empty($search)) {
                                                    $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                        ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                                }
                                            });
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'section':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('section_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );

                        $units = Unit::where('section_id', $area_id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                    $query->whereNull('deactivated_at');
                                }])
                                    ->where('unit_id', $unit->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->when($search, function ($query) use ($search) {
                                        $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                            if (!empty($search)) {
                                                $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                    ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                            }
                                        });
                                    })
                                    ->get()
                            );
                        }
                        break;

                    case 'unit':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                                $query->whereNull('deactivated_at');
                            }])
                                ->where('unit_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->when($search, function ($query) use ($search) {
                                    $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                        if (!empty($search)) {
                                            $q->where('first_name', 'LIKE', '%' . $search . '%')
                                                ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                        }
                                    });
                                })
                                ->get()
                        );
                        break;
                    default:
                        return response()->json(['message', 'Invalid input. Please input a valid sector'], 400);
                }
            } else {
                // Existing sector-specific logic
                switch ($sector) {
                    case 'division':
                        $employees = AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                            $query->whereNull('deactivated_at');
                        }])
                            ->where('division_id', $area_id)
                            ->where('employee_profile_id', '<>', 1)
                            ->when($search, function ($query) use ($search) {
                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                    if (!empty($search)) {
                                        $q->where('first_name', 'LIKE', '%' . $search . '%')
                                            ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                    }
                                });
                            })
                            ->get();
                        break;

                    case 'department':
                        $employees = AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                            $query->whereNull('deactivated_at');
                        }])
                            ->where('department_id', $area_id)
                            ->where('employee_profile_id', '<>', 1)
                            ->when($search, function ($query) use ($search) {
                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                    if (!empty($search)) {
                                        $q->where('first_name', 'LIKE', '%' . $search . '%')
                                            ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                    }
                                });
                            })
                            ->get();
                        break;

                    case 'section':
                        $employees = AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                            $query->whereNull('deactivated_at');
                        }])
                            ->where('section_id', $area_id)
                            ->where('employee_profile_id', '<>', 1)
                            ->when($search, function ($query) use ($search) {
                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                    if (!empty($search)) {
                                        $q->where('first_name', 'LIKE', '%' . $search . '%')
                                            ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                    }
                                });
                            })
                            ->get();
                        break;

                    case 'unit':
                        $employees = AssignArea::with(['employeeProfile.personalInformation', 'employeeProfile' => function ($query) {
                            $query->whereNull('deactivated_at');
                        }])
                            ->where('unit_id', $area_id)
                            ->where('employee_profile_id', '<>', 1)
                            ->when($search, function ($query) use ($search) {
                                $query->whereHas('employeeProfile.personalInformation', function ($q) use ($search) {
                                    if (!empty($search)) {
                                        $q->where('first_name', 'LIKE', '%' . $search . '%')
                                            ->orWhere('last_name', 'LIKE', '%' . $search . '%');
                                    }
                                });
                            })
                            ->get();
                        break;
                }
            }



            $employees = $employees->map(function ($assign_area) {
                $employeeProfile = $assign_area->employeeProfile;

                if ($employeeProfile && $employeeProfile->personalInformation) {
                    // Safely access employeeName
                    $employeeProfile->full_name = $employeeProfile->personalInformation->employeeName();
                } else {
                    $employeeProfile->full_name = 'No Personal Information';
                }

                return $employeeProfile;
            });

            $columns = [
                [
                    "field" => "employee_id",
                    "flex" => 1,
                    "headerName" => "Employee ID"
                ],
                [
                    "field" => "name",
                    "flex" => 1,
                    "headerName" => "Employee Name"
                ],
            ];

            $data = EmployeeProfileResource::collection($employees);

            if ($is_print) {
                return Helpers::generatePdf($data, $columns, $title_print, 'portrait');
            }
            return response()->json([
                'count' => $employees->count(),
                'data' => $data,
                'message' => 'list of employees retrieved.'
            ]);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'myEmployees', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function areasEmployees($id, $sector, Request $request)
    {
        try {
            $user = $request->user;


            $position = $user->position();
            $sector = strip_tags($sector);
            $key = Str::lower($sector) . "_id";

            if (!$position) {
                return response()->json(['message' => "You don't have authorization as a supervisor of area."], Response::HTTP_FORBIDDEN);
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

    public function myAreas(Request $request)
    {
        try {
            $user = $request->user;
            $position = $user->position();

            if (!$position) {
                return response()->json(['message' => "You don't have authorization as a supervisor of area."], Response::HTTP_FORBIDDEN);
            }

            $my_area = $user->assignedArea->findDetails();
            $areas = [];

            switch ($my_area['sector']) {
                case "Division":
                    $areas[] = ['id' => $my_area['details']->id, 'name' => $my_area['details']->name, 'sector' => $my_area['sector']];
                    $deparmtents = Department::where('division_id', $my_area['details']->id)->get();

                    foreach ($deparmtents as $department) {
                        $areas[] = ['id' => $department->id, 'name' => $department->name, 'sector' => 'Department'];

                        $sections = Section::where('department_id', $department->id)->get();
                        foreach ($sections as $section) {
                            $areas[] = ['id' => $section->id, 'name' => $section->name, 'sector' => 'Section'];

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $areas[] = ['id' => $unit->id, 'name' => $unit->name, 'sector' => 'Unit'];
                            }
                        }
                    }


                    $sections = Section::where('division_id', $my_area['details']->id)->get();
                    foreach ($sections as $section) {
                        $areas[] = ['id' => $section->id, 'name' => $section->name, 'code' => $section->code, 'sector' => 'Section'];
                    }


                    break;
                case "Department":
                    $areas[] = ['id' => $my_area['details']->id, 'name' => $my_area['details']->name, 'sector' => $my_area['sector']];
                    $sections = Section::where('department_id', $my_area['details']->id)->get();

                    foreach ($sections as $section) {
                        $areas[] = ['id' => $section->id, 'name' => $section->name, 'sector' => 'Section'];

                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $areas[] = ['id' => $unit->id, 'name' => $unit->name, 'sector' => 'Unit'];
                        }
                    }
                    break;
                case "Section":
                    $areas[] = ['id' => $my_area['details']->id, 'name' => $my_area['details']->name, 'sector' => $my_area['sector']];

                    $units = Unit::where('section_id', $my_area['details']->id)->get();
                    foreach ($units as $unit) {
                        $areas[] = ['id' => $unit->id, 'name' => $unit->name, 'sector' => 'Unit'];
                    }
                    break;
                case "Unit":
                    $areas[] = ['id' => $my_area['details']->id, 'name' => $my_area['details']->name, 'sector' => $my_area['sector']];
                    break;
            }

            return response()->json([
                'data' => $areas,
                'message' => 'Successfully retrieved all my areas.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'myAreas', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function Areas(Request $request)
    {
        try {

            $area = $request->sector;
            $report_type = $request->report_type;
            $status = $request->status;
            $area_under = $request->area_under;
            $area_id = $request->area_id;
            $leave_type_id = $request->leave_type_id;
            $leave_type_ids = explode(',', $request->leave_type_id);
            $areas = [];

            if ($report_type === 'area') {
                switch ($area) {
                    case "Division":
                        $division = Division::where('id', $area_id)->first();
                        if ($division) {
                            $areaData = [
                                'id' => $division->id . '-division',
                                'name' => $division->name,
                                'sector' => 'Division',
                                'leave_with_pay_count' => 0,
                                'leave_without_pay_count' => 0,
                                'leave_count' => 0,
                                'leave_types' => []
                            ];

                            // Initialize total leave counts
                            $leave_with_pay_count_total = 0;
                            $leave_without_pay_count_total = 0;
                            $leave_count_total = 0;
                            foreach ($leave_type_ids as $leave_type_id) {
                                $leave_type = LeaveType::find($leave_type_id);
                                if ($leave_type) {
                                    // Calculate leave counts for the current leave type
                                    $leave_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                        ->whereHas('employeeProfile', function ($query) use ($division) {
                                            $query->whereHas('assignedArea', function ($q) use ($division) {
                                                $q->where('division_id', $division->id);
                                            });
                                        })->count();

                                    $leave_with_pay_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                        ->where('without_pay', false)
                                        ->whereHas('employeeProfile', function ($query) use ($division) {
                                            $query->whereHas('assignedArea', function ($q) use ($division) {
                                                $q->where('division_id', $division->id);
                                            });
                                        })->count();

                                    $leave_without_pay_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                        ->where('without_pay', true)
                                        ->whereHas('employeeProfile', function ($query) use ($division) {
                                            $query->whereHas('assignedArea', function ($q) use ($division) {
                                                $q->where('division_id', $division->id);
                                            });
                                        })->count();

                                    // Accumulate total leave counts
                                    $leave_with_pay_count_total += $leave_with_pay_count;
                                    $leave_without_pay_count_total += $leave_without_pay_count;
                                    $leave_count_total += $leave_count;

                                    // Push leave counts for the current leave type into areaData
                                    $areaData['leave_types'][] = [
                                        'leave_type_id' => $leave_type_id,
                                        'leave_type_name' => $leave_type->name,
                                        'leave_type_code' => $leave_type->code,
                                        'leave_type_count' => $leave_count,
                                    ];
                                }
                            }

                            // Assign total leave counts to areaData
                            $areaData['leave_with_pay_count'] = $leave_with_pay_count_total;
                            $areaData['leave_without_pay_count'] = $leave_without_pay_count_total;
                            $areaData['leave_count'] = $leave_count_total;
                            // Push areaData into areas array
                            $areas[] = $areaData;
                        }
                        if ($area_under === 'All') {
                            $departments = Department::where('division_id', $area_id)->get();

                            foreach ($departments as $department) {
                                $departmentData = [
                                    'id' => $department->id . '-department',
                                    'name' => $department->name,
                                    'sector' => 'Department',
                                    'leave_with_pay_count' => 0,
                                    'leave_without_pay_count' => 0,
                                    'leave_count' => 0,
                                    'leave_types' => []
                                ];

                                // Initialize total leave counts
                                $leave_with_pay_count_total = 0;
                                $leave_without_pay_count_total = 0;
                                $leave_count_total = 0;

                                // Loop through leave types
                                foreach ($leave_type_ids as $leave_type_id) {
                                    $leave_type = LeaveType::find($leave_type_id);
                                    if ($leave_type) {
                                        $leave_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                            ->whereHas('employeeProfile', function ($query) use ($department) {
                                                $query->whereHas('assignedArea', function ($q) use ($department) {
                                                    $q->where('department_id', $department->id);
                                                });
                                            })->count();
                                        $leave_with_pay_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                            ->where('without_pay', false)
                                            ->whereHas('employeeProfile', function ($query) use ($department) {
                                                $query->whereHas('assignedArea', function ($q) use ($department) {
                                                    $q->where('department_id', $department->id);
                                                });
                                            })->count();
                                        $leave_without_pay_count = LeaveApplication::where('without_pay', true)
                                            ->whereHas('employeeProfile', function ($query) use ($department) {
                                                $query->whereHas('assignedArea', function ($q) use ($department) {
                                                    $q->where('department_id', $department->id);
                                                });
                                            })->count();
                                    }

                                    // Accumulate total leave counts
                                    $leave_with_pay_count_total += $leave_with_pay_count;
                                    $leave_without_pay_count_total += $leave_without_pay_count;
                                    $leave_count_total += $leave_count;

                                    // Push leave counts for the current leave type into areaData
                                    $departmentData['leave_types'][] = [
                                        'leave_type_id' => $leave_type_id,
                                        'leave_type_name' => $leave_type->name,
                                        'leave_type_code' => $leave_type->code,
                                        'leave_type_count' => $leave_count,
                                    ];
                                }

                                $departmentData['leave_with_pay_count'] = $leave_with_pay_count_total;
                                $departmentData['leave_without_pay_count'] = $leave_without_pay_count_total;
                                $departmentData['leave_count'] = $leave_count_total;
                                // Push departmentData into areas array
                                $areas[] = $departmentData;

                                $sections = Section::where('department_id', $department->id)->get();
                                foreach ($sections as $section) {
                                    $sectionData = [
                                        'id' => $department->id . '-department',
                                        'name' => $department->name,
                                        'sector' => 'Department',
                                        'leave_with_pay_count' => 0,
                                        'leave_without_pay_count' => 0,
                                        'leave_count' => 0,
                                        'leave_types' => []
                                    ];

                                    foreach ($leave_type_ids as $leave_type_id) {
                                        $leave_type = LeaveType::find($leave_type_id);
                                        if ($leave_type) {
                                            $leave_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                                ->whereHas('employeeProfile', function ($query) use ($section) {
                                                    $query->whereHas('assignedArea', function ($q) use ($section) {
                                                        $q->where('section_id', $section->id);
                                                    });
                                                })->count();
                                            $leave_with_pay_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                                ->where('without_pay', false)
                                                ->whereHas('employeeProfile', function ($query) use ($section) {
                                                    $query->whereHas('assignedArea', function ($q) use ($section) {
                                                        $q->where('section_id', $section->id);
                                                    });
                                                })->count();
                                            $leave_without_pay_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                                ->where('without_pay', true)
                                                ->whereHas('employeeProfile', function ($query) use ($section) {
                                                    $query->whereHas('assignedArea', function ($q) use ($section) {
                                                        $q->where('section_id', $section->id);
                                                    });
                                                })->count();
                                        }

                                        // Accumulate total leave counts
                                        $leave_with_pay_count_total += $leave_with_pay_count;
                                        $leave_without_pay_count_total += $leave_without_pay_count;
                                        $leave_count_total += $leave_count;

                                        // Push leave counts for the current leave type into areaData
                                        $sectionData['leave_types'][] = [
                                            'leave_type_id' => $leave_type_id,
                                            'leave_type_name' => $leave_type->name,
                                            'leave_type_code' => $leave_type->code,
                                            'leave_type_count' => $leave_count,
                                        ];
                                    }
                                    $sectionData['leave_with_pay_count'] = $leave_with_pay_count_total;
                                    $sectionData['leave_without_pay_count'] = $leave_without_pay_count_total;
                                    $sectionData['leave_count'] = $leave_count_total;
                                    // Push sectionData into areas array
                                    $areas[] = $sectionData;

                                    $units = Unit::where('section_id', $section->id)->get();
                                    foreach ($units as $unit) {
                                        $sunitData = [
                                            'id' => $department->id . '-department',
                                            'name' => $department->name,
                                            'sector' => 'Department',
                                            'leave_with_pay_count' => 0,
                                            'leave_without_pay_count' => 0,
                                            'leave_count' => 0,
                                            'leave_types' => []
                                        ];
                                        foreach ($leave_type_ids as $leave_type_id) {
                                            $leave_type = LeaveType::find($leave_type_id);
                                            if ($leave_type) {

                                                $leave_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                                    ->whereHas('employeeProfile', function ($query) use ($unit) {
                                                        $query->whereHas('assignedArea', function ($q) use ($unit) {
                                                            $q->where('unit_id', $unit->id);
                                                        });
                                                    })->count();
                                                $leave_with_pay_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                                    ->where('without_pay', false)
                                                    ->whereHas('employeeProfile', function ($query) use ($unit) {
                                                        $query->whereHas('assignedArea', function ($q) use ($unit) {
                                                            $q->where('unit_id', $unit->id);
                                                        });
                                                    })->count();
                                                $leave_without_pay_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                                    ->where('without_pay', true)
                                                    ->whereHas('employeeProfile', function ($query) use ($unit) {
                                                        $query->whereHas('assignedArea', function ($q) use ($unit) {
                                                            $q->where('unit_id', $unit->id);
                                                        });
                                                    })->count();
                                            }

                                            $leave_with_pay_count_total += $leave_with_pay_count;
                                            $leave_without_pay_count_total += $leave_without_pay_count;
                                            $leave_count_total += $leave_count;

                                            // Push leave counts for the current leave type into areaData
                                            $unitData['leave_types'][] = [
                                                'leave_type_id' => $leave_type_id,
                                                'leave_type_name' => $leave_type->name,
                                                'leave_type_code' => $leave_type->code,
                                                'leave_type_count' => $leave_count,
                                            ];
                                        }
                                        $unitData['leave_with_pay_count'] = $leave_with_pay_count_total;
                                        $unitData['leave_without_pay_count'] = $leave_without_pay_count_total;
                                        $unitData['leave_count'] = $leave_count_total;

                                        $areas[] = $sunitData;
                                    }
                                }
                            }

                            $sections = Section::where('division_id', $area_id)->get();
                            foreach ($sections as $section) {
                                $sectionData = [
                                    'id' => $section->id . '-section',
                                    'name' => $section->name,
                                    'sector' => 'Section',
                                    'leave_with_pay_count' => 0,
                                    'leave_without_pay_count' => 0,
                                    'leave_count' => 0,
                                    'leave_types' => []
                                ];
                                foreach ($leave_type_ids as $leave_type_id) {
                                    $leave_type = LeaveType::find($leave_type_id);
                                    if ($leave_type) {
                                        $leave_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                            ->whereHas('employeeProfile', function ($query) use ($section) {
                                                $query->whereHas('assignedArea', function ($q) use ($section) {
                                                    $q->where('section_id', $section->id);
                                                });
                                            })->count();
                                        $leave_with_pay_count = LeaveApplication::where('without_pay', false)
                                            ->whereHas('employeeProfile', function ($query) use ($section) {
                                                $query->whereHas('assignedArea', function ($q) use ($section) {
                                                    $q->where('section_id', $section->id);
                                                });
                                            })->count();
                                        $leave_without_pay_count = LeaveApplication::where('without_pay', true)
                                            ->whereHas('employeeProfile', function ($query) use ($section) {
                                                $query->whereHas('assignedArea', function ($q) use ($section) {
                                                    $q->where('section_id', $section->id);
                                                });
                                            })->count();

                                        // Accumulate total leave counts
                                        $sectionData['leave_with_pay_count'] += $leave_with_pay_count;
                                        $sectionData['leave_without_pay_count'] += $leave_without_pay_count;
                                        $sectionData['leave_count'] += $leave_count;

                                        // Push leave counts for the current leave type into sectionData
                                        $sectionData['leave_types'][] = [
                                            'leave_type_id' => $leave_type_id,
                                            'leave_type_name' => $leave_type->name,
                                            'leave_type_code' => $leave_type->code,
                                            'leave_type_count' => $leave_count,
                                        ];
                                    }
                                }
                                $areas[] = $sectionData;

                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $unitData = [
                                        'id' => $unit->id . '-unit',
                                        'name' => $unit->name,
                                        'sector' => 'Unit',
                                        'leave_with_pay_count' => 0,
                                        'leave_without_pay_count' => 0,
                                        'leave_count' => 0,
                                        'leave_types' => []
                                    ];
                                    foreach ($leave_type_ids as $leave_type_id) {
                                        $leave_type = LeaveType::find($leave_type_id);
                                        if ($leave_type) {
                                            $leave_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                                ->whereHas('employeeProfile', function ($query) use ($unit) {
                                                    $query->whereHas('assignedArea', function ($q) use ($unit) {
                                                        $q->where('unit_id', $unit->id);
                                                    });
                                                })->count();
                                            $leave_with_pay_count = LeaveApplication::where('without_pay', false)
                                                ->whereHas('employeeProfile', function ($query) use ($unit) {
                                                    $query->whereHas('assignedArea', function ($q) use ($unit) {
                                                        $q->where('unit_id', $unit->id);
                                                    });
                                                })->count();
                                            $leave_without_pay_count = LeaveApplication::where('without_pay', true)
                                                ->whereHas('employeeProfile', function ($query) use ($unit) {
                                                    $query->whereHas('assignedArea', function ($q) use ($unit) {
                                                        $q->where('unit_id', $unit->id);
                                                    });
                                                })->count();

                                            // Accumulate total leave counts
                                            $unitData['leave_with_pay_count'] += $leave_with_pay_count;
                                            $unitData['leave_without_pay_count'] += $leave_without_pay_count;
                                            $unitData['leave_count'] += $leave_count;

                                            // Push leave counts for the current leave type into unitData
                                            $unitData['leave_types'][] = [
                                                'leave_type_id' => $leave_type_id,
                                                'leave_type_name' => $leave_type->name,
                                                'leave_type_code' => $leave_type->code,
                                                'leave_type_count' => $leave_count,
                                            ];
                                        }
                                    }
                                    $areas[] = $unitData;
                                }
                            }
                        }
                        break;
                    case "Department":
                        $department = Department::where('id', $area_id)->first();
                        if ($department) {
                            $departmentData = [
                                'id' => $department->id . '-department',
                                'name' => $department->name,
                                'sector' => 'Department',
                                'leave_with_pay_count' => 0,
                                'leave_without_pay_count' => 0,
                                'leave_count' => 0,
                                'leave_types' => []
                            ];
                            // Initialize total leave counts
                            $leave_with_pay_count_total = 0;
                            $leave_without_pay_count_total = 0;
                            $leave_count_total = 0;

                            // Loop through leave types
                            foreach ($leave_type_ids as $leave_type_id) {
                                $leave_type = LeaveType::find($leave_type_id);
                                if ($leave_type) {
                                    $leave_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                        ->whereHas('employeeProfile', function ($query) use ($department) {
                                            $query->whereHas('assignedArea', function ($q) use ($department) {
                                                $q->where('department_id', $department->id);
                                            });
                                        })->count();
                                    $leave_with_pay_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                        ->where('without_pay', false)
                                        ->whereHas('employeeProfile', function ($query) use ($department) {
                                            $query->whereHas('assignedArea', function ($q) use ($department) {
                                                $q->where('department_id', $department->id);
                                            });
                                        })->count();
                                    $leave_without_pay_count = LeaveApplication::where('without_pay', true)
                                        ->whereHas('employeeProfile', function ($query) use ($department) {
                                            $query->whereHas('assignedArea', function ($q) use ($department) {
                                                $q->where('department_id', $department->id);
                                            });
                                        })->count();
                                }

                                // Accumulate total leave counts
                                $leave_with_pay_count_total += $leave_with_pay_count;
                                $leave_without_pay_count_total += $leave_without_pay_count;
                                $leave_count_total += $leave_count;

                                // Push leave counts for the current leave type into areaData
                                $departmentData['leave_types'][] = [
                                    'leave_type_id' => $leave_type_id,
                                    'leave_type_name' => $leave_type->name,
                                    'leave_type_code' => $leave_type->code,
                                    'leave_type_count' => $leave_count,
                                ];
                            }

                            $departmentData['leave_with_pay_count'] = $leave_with_pay_count_total;
                            $departmentData['leave_without_pay_count'] = $leave_without_pay_count_total;
                            $departmentData['leave_count'] = $leave_count_total;
                            // Push departmentData into areas array
                            $areas[] = $departmentData;
                        }
                        if ($area_under === 'All') {
                            $sections = Section::where('division_id', $area_id)->get();
                            foreach ($sections as $section) {
                                $sectionData = [
                                    'id' => $section->id . '-section',
                                    'name' => $section->name,
                                    'sector' => 'Section',
                                    'leave_with_pay_count' => 0,
                                    'leave_without_pay_count' => 0,
                                    'leave_count' => 0,
                                    'leave_types' => []
                                ];
                                foreach ($leave_type_ids as $leave_type_id) {
                                    $leave_type = LeaveType::find($leave_type_id);
                                    if ($leave_type) {
                                        $leave_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                            ->whereHas('employeeProfile', function ($query) use ($section) {
                                                $query->whereHas('assignedArea', function ($q) use ($section) {
                                                    $q->where('section_id', $section->id);
                                                });
                                            })->count();
                                        $leave_with_pay_count = LeaveApplication::where('without_pay', false)
                                            ->whereHas('employeeProfile', function ($query) use ($section) {
                                                $query->whereHas('assignedArea', function ($q) use ($section) {
                                                    $q->where('section_id', $section->id);
                                                });
                                            })->count();
                                        $leave_without_pay_count = LeaveApplication::where('without_pay', true)
                                            ->whereHas('employeeProfile', function ($query) use ($section) {
                                                $query->whereHas('assignedArea', function ($q) use ($section) {
                                                    $q->where('section_id', $section->id);
                                                });
                                            })->count();

                                        // Accumulate total leave counts
                                        $sectionData['leave_with_pay_count'] += $leave_with_pay_count;
                                        $sectionData['leave_without_pay_count'] += $leave_without_pay_count;
                                        $sectionData['leave_count'] += $leave_count;

                                        // Push leave counts for the current leave type into sectionData
                                        $sectionData['leave_types'][] = [
                                            'leave_type_id' => $leave_type_id,
                                            'leave_type_name' => $leave_type->name,
                                            'leave_type_code' => $leave_type->code,
                                            'leave_type_count' => $leave_count,
                                        ];
                                    }
                                }
                                $areas[] = $sectionData;

                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $unitData = [
                                        'id' => $unit->id . '-unit',
                                        'name' => $unit->name,
                                        'sector' => 'Unit',
                                        'leave_with_pay_count' => 0,
                                        'leave_without_pay_count' => 0,
                                        'leave_count' => 0,
                                        'leave_types' => []
                                    ];
                                    foreach ($leave_type_ids as $leave_type_id) {
                                        $leave_type = LeaveType::find($leave_type_id);
                                        if ($leave_type) {
                                            $leave_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                                ->whereHas('employeeProfile', function ($query) use ($unit) {
                                                    $query->whereHas('assignedArea', function ($q) use ($unit) {
                                                        $q->where('unit_id', $unit->id);
                                                    });
                                                })->count();
                                            $leave_with_pay_count = LeaveApplication::where('without_pay', false)
                                                ->whereHas('employeeProfile', function ($query) use ($unit) {
                                                    $query->whereHas('assignedArea', function ($q) use ($unit) {
                                                        $q->where('unit_id', $unit->id);
                                                    });
                                                })->count();
                                            $leave_without_pay_count = LeaveApplication::where('without_pay', true)
                                                ->whereHas('employeeProfile', function ($query) use ($unit) {
                                                    $query->whereHas('assignedArea', function ($q) use ($unit) {
                                                        $q->where('unit_id', $unit->id);
                                                    });
                                                })->count();

                                            // Accumulate total leave counts
                                            $unitData['leave_with_pay_count'] += $leave_with_pay_count;
                                            $unitData['leave_without_pay_count'] += $leave_without_pay_count;
                                            $unitData['leave_count'] += $leave_count;

                                            // Push leave counts for the current leave type into unitData
                                            $unitData['leave_types'][] = [
                                                'leave_type_id' => $leave_type_id,
                                                'leave_type_name' => $leave_type->name,
                                                'leave_type_code' => $leave_type->code,
                                                'leave_type_count' => $leave_count,
                                            ];
                                        }
                                    }
                                    $areas[] = $unitData;
                                }
                            }
                        }
                        break;
                    case "Section":
                        $section = Section::where('id', $area_id)->first();
                        if ($section) {
                            $sectionData = [
                                'id' => $section->id . '-section',
                                'name' => $section->name,
                                'sector' => 'Section',
                                'leave_with_pay_count' => 0,
                                'leave_without_pay_count' => 0,
                                'leave_count' => 0,
                                'leave_types' => []
                            ];
                            foreach ($leave_type_ids as $leave_type_id) {
                                $leave_type = LeaveType::find($leave_type_id);
                                if ($leave_type) {
                                    $leave_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                        ->whereHas('employeeProfile', function ($query) use ($section) {
                                            $query->whereHas('assignedArea', function ($q) use ($section) {
                                                $q->where('section_id', $section->id);
                                            });
                                        })->count();
                                    $leave_with_pay_count = LeaveApplication::where('without_pay', false)
                                        ->whereHas('employeeProfile', function ($query) use ($section) {
                                            $query->whereHas('assignedArea', function ($q) use ($section) {
                                                $q->where('section_id', $section->id);
                                            });
                                        })->count();
                                    $leave_without_pay_count = LeaveApplication::where('without_pay', true)
                                        ->whereHas('employeeProfile', function ($query) use ($section) {
                                            $query->whereHas('assignedArea', function ($q) use ($section) {
                                                $q->where('section_id', $section->id);
                                            });
                                        })->count();

                                    // Accumulate total leave counts
                                    $sectionData['leave_with_pay_count'] += $leave_with_pay_count;
                                    $sectionData['leave_without_pay_count'] += $leave_without_pay_count;
                                    $sectionData['leave_count'] += $leave_count;

                                    // Push leave counts for the current leave type into sectionData
                                    $sectionData['leave_types'][] = [
                                        'leave_type_id' => $leave_type_id,
                                        'leave_type_name' => $leave_type->name,
                                        'leave_type_code' => $leave_type->code,
                                        'leave_type_count' => $leave_count,
                                    ];
                                }
                            }
                            $areas[] = $sectionData;
                        }
                        if ($area_under === 'All') {
                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $unitData = [
                                    'id' => $unit->id . '-unit',
                                    'name' => $unit->name,
                                    'sector' => 'Unit',
                                    'leave_with_pay_count' => 0,
                                    'leave_without_pay_count' => 0,
                                    'leave_count' => 0,
                                    'leave_types' => []
                                ];
                                foreach ($leave_type_ids as $leave_type_id) {
                                    $leave_type = LeaveType::find($leave_type_id);
                                    if ($leave_type) {
                                        $leave_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                            ->whereHas('employeeProfile', function ($query) use ($unit) {
                                                $query->whereHas('assignedArea', function ($q) use ($unit) {
                                                    $q->where('unit_id', $unit->id);
                                                });
                                            })->count();
                                        $leave_with_pay_count = LeaveApplication::where('without_pay', false)
                                            ->whereHas('employeeProfile', function ($query) use ($unit) {
                                                $query->whereHas('assignedArea', function ($q) use ($unit) {
                                                    $q->where('unit_id', $unit->id);
                                                });
                                            })->count();
                                        $leave_without_pay_count = LeaveApplication::where('without_pay', true)
                                            ->whereHas('employeeProfile', function ($query) use ($unit) {
                                                $query->whereHas('assignedArea', function ($q) use ($unit) {
                                                    $q->where('unit_id', $unit->id);
                                                });
                                            })->count();

                                        // Accumulate total leave counts
                                        $unitData['leave_with_pay_count'] += $leave_with_pay_count;
                                        $unitData['leave_without_pay_count'] += $leave_without_pay_count;
                                        $unitData['leave_count'] += $leave_count;

                                        // Push leave counts for the current leave type into unitData
                                        $unitData['leave_types'][] = [
                                            'leave_type_id' => $leave_type_id,
                                            'leave_type_name' => $leave_type->name,
                                            'leave_type_code' => $leave_type->code,
                                            'leave_type_count' => $leave_count,
                                        ];
                                    }
                                }
                                $areas[] = $unitData;
                            }
                        }
                        break;
                    case "Unit":
                        $unit = Unit::where('id', $area_id)->first();
                        if ($unit) {
                            $unitData = [
                                'id' => $unit->id . '-unit',
                                'name' => $unit->name,
                                'sector' => 'Unit',
                                'leave_with_pay_count' => 0,
                                'leave_without_pay_count' => 0,
                                'leave_count' => 0,
                                'leave_types' => []
                            ];
                            foreach ($leave_type_ids as $leave_type_id) {
                                $leave_type = LeaveType::find($leave_type_id);
                                if ($leave_type) {
                                    $leave_count = LeaveApplication::where('leave_type_id', $leave_type_id)
                                        ->whereHas('employeeProfile', function ($query) use ($unit) {
                                            $query->whereHas('assignedArea', function ($q) use ($unit) {
                                                $q->where('unit_id', $unit->id);
                                            });
                                        })->count();
                                    $leave_with_pay_count = LeaveApplication::where('without_pay', false)
                                        ->whereHas('employeeProfile', function ($query) use ($unit) {
                                            $query->whereHas('assignedArea', function ($q) use ($unit) {
                                                $q->where('unit_id', $unit->id);
                                            });
                                        })->count();
                                    $leave_without_pay_count = LeaveApplication::where('without_pay', true)
                                        ->whereHas('employeeProfile', function ($query) use ($unit) {
                                            $query->whereHas('assignedArea', function ($q) use ($unit) {
                                                $q->where('unit_id', $unit->id);
                                            });
                                        })->count();

                                    // Accumulate total leave counts
                                    $unitData['leave_with_pay_count'] += $leave_with_pay_count;
                                    $unitData['leave_without_pay_count'] += $leave_without_pay_count;
                                    $unitData['leave_count'] += $leave_count;

                                    // Push leave counts for the current leave type into unitData
                                    $unitData['leave_types'][] = [
                                        'leave_type_id' => $leave_type_id,
                                        'leave_type_name' => $leave_type->name,
                                        'leave_type_code' => $leave_type->code,
                                        'leave_type_count' => $leave_count,
                                    ];
                                }
                            }
                            $areas[] = $unitData;
                        }
                        break;
                }

                // Sort the areas by leave_count in descending order
                usort($areas, function ($a, $b) {
                    return $b['leave_count'] - $a['leave_count'];
                });
                return response()->json(['areas' => $areas]);
            } elseif ($report_type === 'employee') {
                // Return leave applications with areas
                $status = $request->status; // Assuming leave_status is passed in the request
                $leave_applications = LeaveApplication::where('status', $status)->get();

                foreach ($leave_applications as $leave_application) {
                    $employee_profile_id = $leave_application->employee_profile_id;

                    // Get employee's assigned areas
                    $employee = AssignArea::where('employee_profile_id', $employee_profile_id)->first();
                    $employee_areas = [];

                    if ($employee) {
                        switch ($area) {
                            case "Division":
                                $division = Division::where('id', $area_id)->first();
                                if ($division) {
                                    $employee_areas[] = ['id' => $area_id . '-' . strtolower($area), 'name' => $division->name, 'sector' => $area];
                                }
                                if ($area_under) {
                                    $departments = Department::where('division_id', $area_id)->get();
                                    foreach ($departments as $department) {
                                        $employee_areas[] = ['id' => $department->id . '-department', 'name' => $department->name, 'sector' => 'Department'];
                                        $sections = Section::where('department_id', $department->id)->get();
                                        foreach ($sections as $section) {
                                            $employee_areas[] = ['id' => $section->id . '-section', 'name' => $section->name, 'sector' => 'Section'];
                                            $units = Unit::where('section_id', $section->id)->get();
                                            foreach ($units as $unit) {
                                                $employee_areas[] = ['id' => $unit->id . '-unit', 'name' => $unit->name, 'sector' => 'Unit'];
                                            }
                                        }
                                    }
                                }
                                break;
                            case "Department":
                                $department = Department::where('id', $area_id)->first();
                                if ($department) {
                                    $employee_areas[] = ['id' => $area_id . '-' . strtolower($area), 'name' => $department->name, 'sector' => $area];
                                }
                                if ($area_under) {
                                    $sections = Section::where('department_id', $area_id)->get();
                                    foreach ($sections as $section) {
                                        $employee_areas[] = ['id' => $section->id . '-section', 'name' => $section->name, 'sector' => 'Section'];
                                        $units = Unit::where('section_id', $section->id)->get();
                                        foreach ($units as $unit) {
                                            $employee_areas[] = ['id' => $unit->id . '-unit', 'name' => $unit->name, 'sector' => 'Unit'];
                                        }
                                    }
                                }
                                break;
                            case "Section":
                                $section = Section::where('id', $area_id)->first();
                                if ($section) {
                                    $employee_areas[] = ['id' => $area_id . '-' . strtolower($area), 'name' => $section->name, 'sector' => $area];
                                }
                                if ($area_under) {
                                    $units = Unit::where('section_id', $area_id)->get();
                                    foreach ($units as $unit) {
                                        $employee_areas[] = ['id' => $unit->id . '-unit', 'name' => $unit->name, 'sector' => 'Unit'];
                                    }
                                }
                                break;
                            case "Unit":
                                $unit = Unit::where('id', $area_id)->first();
                                if ($unit) {
                                    $employee_areas[] = ['id' => $area_id . '-' . strtolower($area), 'name' => $unit->name, 'sector' => $area];
                                }
                                break;
                        }
                    }

                    // Combine leave application with employee areas
                    $areas[] = [
                        'leave_application' => $leave_application,
                        'employee_areas' => $employee_areas
                    ];
                }

                return response()->json(['areas' => $areas]);
            }

            return response()->json([
                'data' => $areas,
                'message' => 'Successfully retrieved all my areas.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'myAreas', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAreas(Request $request)
    {
        try {
            $area = $request->sector;
            $area_id = $request->area_id;
            $areas = [];

            switch ($area) {
                case "division":
                    $division = Division::where('id', $area_id)->first();
                    $areas[] = ['id' => $area_id . '-' . strtolower($area), 'name' => $division->name, 'sector' => $area, 'code' => $division->code];
                    $departments = Department::where('division_id', $area_id)->get();

                    foreach ($departments as $department) {
                        $areas[] = ['id' => $department->id . '-' . 'department', 'name' => $department->name, 'sector' => 'department', 'code' => $department->code];
                        $sections = Section::where('department_id', $department->id)->get();
                        foreach ($sections as $section) {
                            $areas[] = ['id' => $section->id . '-' . 'section', 'name' => $section->name, 'sector' => 'section', 'code' => $section->code];

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $areas[] = ['id' => $unit->id . '-' . 'unit', 'name' => $unit->name, 'sector' => 'unit', 'code' => $unit->code];
                            }
                        }
                    }
                    $sections = Section::where('division_id', $area_id)->get();
                    foreach ($sections as $section) {
                        $areas[] = ['id' => $section->id . '-' . 'section', 'name' => $section->name, 'sector' => 'section', 'code' => $section->code];

                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $areas[] = ['id' => $unit->id . '-' . 'unit', 'name' => $unit->name, 'sector' => 'unit', 'code' => $unit->code];
                        }
                    }
                    break;
                case "department":
                    $department = Department::where('id', $area_id)->first();
                    $areas[] = ['id' => $area_id . '-' . strtolower($area), 'name' => $department->name, 'sector' => $area, 'code' => $department->code];
                    $sections = Section::where('department_id', $area_id)->get();

                    foreach ($sections as $section) {
                        $areas[] = ['id' => $section->id . '-' . 'section', 'name' => $section->name, 'sector' => 'section', 'code' => $section->code];

                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $areas[] = ['id' => $unit->id . '-' . 'unit', 'name' => $unit->name, 'sector' => 'unit', 'code' => $unit->code];
                        }
                    }
                    break;
                case "section":
                    $section = Section::where('id', $area_id)->first();
                    $areas[] = ['id' => $area_id . '-' . strtolower($area), 'name' => $section->name, 'sector' => $area, 'code' => $section->code];

                    $units = Unit::where('section_id', $area_id)->get();
                    foreach ($units as $unit) {
                        $areas[] = ['id' => $unit->id . '-' . 'unit', 'name' => $unit->name, 'sector' => 'unit', 'code' => $unit->code];
                    }
                    break;
                case "unit":
                    $unit = Unit::where('id', $area_id)->first();
                    $areas[] = ['id' => $area_id . '-' . strtolower($area), 'name' => $unit->name, 'sector' => $area, 'code' => $unit->code];
                    break;
            }

            return response()->json([
                'data' => $areas,
                'message' => 'Successfully retrieved all my areas.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'myAreas', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getEmployeeListByEmployementTypes(Request $request)
    {
        try {
            $cacheExpiration = Carbon::now()->addDay();

            // $employee_profiles = Cache::remember('employee_profiles', $cacheExpiration, function () {
            //     return EmployeeProfile::whereNotIn('id', [1])->get();
            // });

            $employee_profiles = EmployeeProfile::where('employee_id', '!=', null)->whereNotIn('id', [1])->get();
            $temp_perm = EmployeeProfileResource::collection($employee_profiles->filter(function ($profile) {
                return $profile->employment_type_id != 5;
            }) ?? []);

            $joborder = EmployeeProfileResource::collection($employee_profiles->filter(function ($profile) {
                return $profile->employment_type_id == 5;
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

    public function store(EmployeeProfileNewResource $request)
    {
        try {
            $personal_information_value = json_decode($request->personal_information);
            DB::beginTransaction();

            /**
             * Personal Information module.
             */
            $personal_information_request = new PersonalInformationRequest();
            $personal_information_json = json_decode($request->personal_information);
            $personal_information_data = [];

            foreach ($personal_information_json as $key => $value) {
                if (Str::contains($key, "_value")) continue;
                $personal_information_data[$key] = $value;
            }

            $personal_information_request->merge($personal_information_data);
            $personal_information_controller = new PersonalInformationController();
            $personal_information = $personal_information_controller->store($personal_information_request);

            /**
             * Contact module.
             */
            $contact_request = new ContactRequest();
            $contact_json = json_decode($request->contact);
            $contact_data = [];

            foreach ($contact_json as $key => $value) {
                $contact_data[$key] = $value;
            }

            $contact_request->merge($contact_data);
            $contact_controller = new ContactController();
            $contact_controller->store($personal_information->id, $contact_request);

            /**
             * Family background module
             */
            if ($request->family_background !== null) {
                $family_background_request = new FamilyBackgroundRequest();
                $family_background_json = json_decode($request->family_background);
                $family_background_data = [];

                foreach ($family_background_json as $key => $value) {
                    $family_background_data[$key] = $value;
                }

                $family_background_request->merge($family_background_data);
                $family_background_request->merge(['children' => $request->children]);
                $family_background_controller = new FamilyBackgroundController();
                $family_background_controller->store($personal_information->id, $family_background_request);
            }

            /**
             * Education module
             */
            if ($request->educations !== null) {
                $education_request = new EducationalBackgroundRequest();
                $education_json = json_decode($request->educations);
                $education_data = [];

                foreach ($education_json as $key => $value) {
                    $education_data[$key] = $value;
                }

                $education_request->merge(['educations' => $education_data]);
                $education_controller = new EducationalBackgroundController();
                $education_controller->storeMany($personal_information->id, $education_request);
            }

            /**
             * Identification module
             */
            if ($request->identification !== null) {
                $identification_request = new IdentificationNumberRequest();
                $identification_json = json_decode($request->identification);
                $identification_data = [];

                foreach ($identification_json as $key => $value) {
                    $identification_data[$key] = $value;
                }

                $identification_request->merge($identification_data);
                $identification_controller = new IdentificationNumberController();
                $identification_controller->store($personal_information->id, $identification_request);
            }

            /**
             * Work experience module
             */
            if ($request->work_experiences !== null) {
                $work_experience_request = new WorkExperienceRequest();
                $work_experience_json = json_decode($request->work_experiences);
                $work_experience_data = [];

                foreach ($work_experience_json as $key => $value) {
                    $work_experience_data[$key] = $value;
                }

                $work_experience_request->merge(['work_experiences' => $work_experience_data]);
                $work_experience_controller = new WorkExperienceController();
                $work_experience_controller->storeMany($personal_information->id, $work_experience_request);
            }

            /**
             * Voluntary work module
             */
            if ($request->voluntary_work !== null) {
                $voluntary_work_request = new VoluntaryWorkRequest();
                $voluntary_work_json = json_decode($request->voluntary_work);
                $voluntary_work_data = [];

                foreach ($voluntary_work_json as $key => $value) {
                    $voluntary_work_data[$key] = $value;
                }

                $voluntary_work_request->merge(['voluntary_work' => $voluntary_work_data]);
                $voluntary_work_controller = new VoluntaryWorkController();
                $voluntary_work_controller->storeMany($personal_information->id, $voluntary_work_request);
            }

            /**
             * Other module
             */
            if ($request->others !== null) {
                $other_request = new OtherInformationManyRequest();
                $other_json = json_decode($request->others);
                $other_data = [];

                foreach ($other_json as $key => $value) {
                    $voluntary_work_data[$key] = $value;
                }

                $other_request->merge(['others' => $other_data]);
                $other_controller = new OtherInformationController();
                $other_controller->storeMany($personal_information->id, $other_request);
            }

            /**
             * Legal information module
             */
            if ($request->legal_information !== null) {
                $legal_info_request = new LegalInformationManyRequest();
                $legal_info_json = json_decode($request->legal_information);
                $legal_info_data = [];

                foreach ($legal_info_json as $key => $value) {
                    $legal_info_data[$key] = $value;
                }

                $legal_info_request->merge(['legal_information' => $legal_info_data]);
                $legal_information_controller = new LegalInformationController();
                $legal_information_controller->storeMany($personal_information->id, $legal_info_request);
            }

            /**
             * Training module
             */
            if ($request->trainings !== null) {
                $training_request = new TrainingManyRequest();
                $training_json = json_decode($request->trainings);
                $training_data = [];

                foreach ($training_json as $key => $value) {
                    $training_data[$key] = $value;
                }

                $training_request->merge(['trainings' => $training_data]);
                $training_controller = new TrainingController();
                $training_controller->storeMany($personal_information->id, $training_request);
            }

            /**
             * Reference module
             */
            if ($request->reference !== null) {
                $referrence_request = new ReferenceManyRequest();
                $referrence_json = json_decode($request->reference);
                $referrence_data = [];

                foreach ($referrence_json as $key => $value) {
                    $referrence_data[$key] = $value;
                }

                $referrence_request->merge(['reference' => $referrence_data]);
                $referrence_controller = new ReferencesController();
                $referrence_controller->storeMany($personal_information->id, $referrence_request);
            }

            /**
             * Eligibilities module
             */
            if ($request->eligibilities !== null) {
                $eligibilities_request = new CivilServiceEligibilityManyRequest();
                $eligibilities_json = json_decode($request->eligibilities);
                $eligibilities_data = [];

                foreach ($eligibilities_json as $key => $value) {
                    $eligibilities_data[$key] = $value;
                }

                $eligibilities_request->merge(['eligibilities' => $eligibilities_data]);
                $eligibilities_controller = new CivilServiceEligibilityController();
                $eligibilities_controller->storeMany($personal_information->id, $eligibilities_request);
            }

            $in_valid_file = false;

            $cleanData = [];
            $dateString = $request->date_hired;
            $carbonDate = Carbon::parse($dateString);
            $date_hired_string = $carbonDate->format('Ymd');

            $total_registered_this_day = EmployeeProfile::whereDate('date_hired', $carbonDate)->get();
            $employee_id_random_digit = 50 + count($total_registered_this_day);

            $last_registered_employee = EmployeeProfile::orderBy('biometric_id', 'desc')->first();
            $default_password = Helpers::generatePassword();

            $hashPassword = Hash::make($default_password . config('app.salt_value'));
            $encryptedPassword = Crypt::encryptString($hashPassword);
            $now = Carbon::now();

            $twominutes = $now->addMinutes(2)->toDateTimeString();

            $new_biometric_id = $last_registered_employee->biometric_id + 1;
            $new_employee_id = $date_hired_string . $employee_id_random_digit;

            $cleanData['employee_id'] = strip_tags($personal_information_value->employee_id);
            $cleanData['biometric_id'] = $new_biometric_id;
            $cleanData['employment_type_id'] = strip_tags($request->employment_type_id);
            $cleanData['personal_information_id'] = strip_tags($personal_information->id);

            try {
                $fileName = Helpers::checkSaveFile($request->attachment, 'photo/profiles');
                if (is_string($fileName)) {
                    $cleanData['profile_url'] = $request->attachment === null || $request->attachment === 'null' ? null : $fileName;
                }

                if (is_array($fileName)) {
                    $in_valid_file = true;
                    $cleanData['profile_url'] = null;
                }
            } catch (\Throwable $th) {
            }

            $cleanData['allow_time_adjustment'] = strip_tags($request->allow) == 1 ? true : false;
            $cleanData['solo_parent'] = strip_tags($request->solo_parent) == 1 ? true : false;
            $cleanData['shifting'] = strip_tags($request->shifting) == 1 ? true : false;
            $cleanData['password_encrypted'] = $encryptedPassword;
            $cleanData['password_created_at'] = now();
            $cleanData['password_expiration_at'] = $twominutes;
            $cleanData['salary_grade_step'] = strip_tags($request->salary_grade_step);
            $cleanData['date_hired'] = $request->date_hired;
            $cleanData['designation_id'] = $request->designation_id;
            $cleanData['effective_at'] = $request->date_hired;

            if (EmploymentType::find($cleanData['employment_type_id'])->name === 'Temporary' || EmploymentType::find($cleanData['employment_type_id'])->name === 'Job Order') {

                // if ($request->renewal === 'null' || $request->renewal === null) {
                //     DB::rollBack();
                //     return response()->json([
                //         'message' => 'Temporary or Job order renewal date is required.'
                //     ], Response::HTTP_BAD_REQUEST);
                // }

                if (EmploymentType::find($cleanData['employment_type_id'])->name === 'Temporary') {
                    $cleanData['renewal'] = Carbon::now()->addYear();
                }

                // $cleanData['renewal'] = strip_tags($request->renewal);
            }

            $plantilla_number_id = $request->plantilla_number_id === "null" || $request->plantilla_number_id === null ? null : $request->plantilla_number_id;
            $sector = strip_tags($request->sector);

            $cleanData[Str::lower($sector) . '_id'] = strip_tags($request->sector_id);

            if ($plantilla_number_id !== null) {
                $plantilla_number = PlantillaNumber::find($plantilla_number_id);

                if (!$plantilla_number) {
                    return response()->json(['message' => 'No record found for plantilla number ' . $plantilla_number_id], Response::HTTP_NOT_FOUND);
                }

                $key = strtolower($request->sector) . '_id';
                $cleanData['plantilla_number_id'] = $plantilla_number_id;
                $cleanData[$key] = strip_tags($request->area_id);

                $key_list = ['division_id', 'department_id', 'section_id', 'unit_id'];

                foreach ($key_list as $value) {
                    if ($value === $key) continue;
                    $cleanData[$value] = null;
                }

                $plantilla_assign_area = PlantillaAssignedArea::create($cleanData);
                $plantilla_number->update(['assigned_at' => now()]);

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
                $currentYear = date('Y');
                $validUntil = date('Y-m-d', strtotime("$currentYear-12-31"));

                EmployeeOvertimeCredit::create([
                    'employee_profile_id' => $employee_profile->id,
                    'earned_credit_by_hour' => 0,
                    'used_credit_by_hour' => 0,
                    'valid_until' => $validUntil,
                    'is_expired' => 0,
                    'max_credit_monthly' => 40,
                    'max_credit_annual' => 120
                ]);
            }
            /**
             * Issuance module
             */
            $issuance_request = new IssuanceInformationRequest();
            $issuance_json = json_decode($request->issuance_information);
            $issuance_data = [];

            foreach ($issuance_json as $key => $value) {
                $issuance_data[$key] = $value;
            }

            $issuance_request->merge($issuance_data);
            $issuance_controller = new IssuanceInformationController();
            $issuance_controller->store($employee_profile->id, $issuance_request);

            if (strip_tags($request->shifting) === "0") {
                $schedule_this_month = Helpers::generateSchedule(Carbon::now(), $cleanData['employment_type_id'], $request->meridian);

                foreach ($schedule_this_month as $schedule) {
                    EmployeeSchedule::create([
                        'employee_profile_id' => $employee_profile->id,
                        'schedule_id' => $schedule->id
                    ]);
                }

                $schedule_next_month = Helpers::generateSchedule(Carbon::now()->addMonth()->startOfMonth(), $cleanData['employment_type_id'], $request->meridian);

                foreach ($schedule_next_month as $schedule) {
                    EmployeeSchedule::create([
                        'employee_profile_id' => $employee_profile->id,
                        'schedule_id' => $schedule->id
                    ]);
                }
            } else {

                $role = Role::where('code', 'SHIFTING')->first();
                $system_role = SystemRole::where('role_id', $role->id)->first();

                SpecialAccessRole::create([
                    'system_role_id' => $system_role->id,
                    'employee_profile_id' => $employee_profile->id,
                    'effective_at' => now()
                ]);
            }

            if (strip_tags($request->allow_time_adjustment) === 1) {
                $role = Role::where('code', 'ATA')->first();
                $system_role = SystemRole::where('role_id', $role->id)->first();

                SpecialAccessRole::create([
                    'system_role_id' => $system_role->id,
                    'employee_profile_id' => $employee_profile->id,
                    'effective_at' => now()
                ]);
            }

            $data = [
                'employeeID' => $employee_profile->employee_id,
                'Password' => $default_password,
                "Link" => config('app.client_domain')
            ];

            $email = $employee_profile->personalInformation->contact->email_address;
            $name = $employee_profile->personalInformation->name();

            SendEmailJob::dispatch('new_account', $email, $name, $data);

            DB::commit();
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
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

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
                $profile_path = $request->attachment === null || $request->attachment === 'null' ? null : $fileName;
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

            $hashPassword = Hash::make($default_password['password'] . config('app.salt_value'));
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
                'profile_url' => config('app.server_domain') . "/photo/profiles/" . $employee_profile->profile_url,
                'employee_id' => $employee_profile->employee_id,
                'position' => $position,
                'job_position' => $designation->name,
                'date_hired' => $employee_profile->date_hired,
                'job_type' => $employee_profile->employmentType->name,
                'employment_type_id' => $employee_profile->employmentType->id,
                'years_of_service' => $employee_profile->personalInformation->years_of_service,
                'last_login' => $last_login === null ? null : $last_login->created_at,
                'biometric_id' => $employee_profile->biometric_id,
                'total_months' => $total - ($totalYears * 12),
                'total_years' => $totalYears,
                'zcmc_service_years' => $totalYearsInZcmc,
                'zcmc_service_months' => $totalMonthsInZcmc - ($totalYearsInZcmc * 12),
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
                    $address['residential_telephone_no'] = $value->telephone_no;
                    $address['permanent_address'] = $value->address;
                    $address['permanent_zip_code'] = $value->zip_code;
                    $address['permanent_telephone_no'] = $value->telephone_no;
                    break;
                }

                if ($value->is_residential) {
                    $address['residential_address'] = $value->address;
                    $address['residential_zip_code'] = $value->zip_code;
                    $address['residential_telephone_no'] = $value->telephone_no;
                } else {
                    $address['permanent_address'] = $value->address;
                    $address['permanent_zip_code'] = $value->zip_code;
                    $address['permanent_telephone_no'] = $value->telephone_no;
                }
            }

            $data = [
                'personal_information_id' => $personal_information->id,
                'employee_profile_id' => $employee_profile['id'],
                'employee_id' => $employee_profile['employee_id'],
                'name' => $personal_information->employeeName(),
                'designation_id' => $designation['id'],
                'designation' => $designation['name'],
                'designation_code' => $designation['code'],
                'plantilla_number_id' => $assigned_area['plantilla_number_id'],
                'plantilla_number' => $assigned_area['plantilla_number_id'] === NULL ? NULL : $assigned_area->plantillaNumber['number'],
                'employee_details' => [
                    'employee' => $employee,
                    'personal_information' => $personal_information_data,
                    'personal_information_id' => $personal_information->id,
                    'contact' => new ContactResource($personal_information->contact),
                    'address' => $address,
                    'address_update' => AddressResource::collection($personal_information->addresses),
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

    public function update($id, Request $request)
    {
        try {
            $user = $request->user;
            $cleanData['password'] = strip_tags($request->password);

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'] . config('app.salt_value'), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_FORBIDDEN);
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
            $cleanData['password'] = strip_tags($request->password);

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'] . config('app.salt_value'), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_FORBIDDEN);
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
        try {
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

            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
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

    public function revokeOIC(AuthPinApprovalRequest $request)
    {
        try {
            $employee_profile = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($employee_profile['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $position = $employee_profile->position();

            switch ($position['position']) {
                case "Medical Center Chief":
                    $division = Division::where('code', 'OMCC')
                        ->where('chief_employee_profile_id', $employee_profile->id)
                        ->first();

                    if (!$division)
                        return response()->json(['message' => "Invalid request."], Response::HTTP_FORBIDDEN);
                    $oic = $division->oic_employee_profile_id;

                    $this->trailOICandResetAreaOICRecord($oic, $division);
                    $system_role = SystemRole::where('code', 'OMCC-01')->first();
                    $this->revokeOICRights($oic, $system_role->id);
                    break;
                case "Chief Nurse" || "Division Head":
                    $division = Division::where('chief_employee_profile_id', $employee_profile->id)->first();

                    if (!$division)
                        return response()->json(['message' => "Invalid request."], Response::HTTP_FORBIDDEN);
                    $oic = $division->oic_employee_profile_id;

                    $this->trailOICandResetAreaOICRecord($oic, $division);
                    $system_role = SystemRole::where('code', 'DIV-HEAD-03')->first();
                    $this->revokeOICRights($oic, $system_role->id);
                    break;
                case "Nurse Manager" || "Department Head":
                    $department = Department::where('head_employee_profile_id', $employee_profile->id)->first();

                    if (!$department)
                        return response()->json(['message' => "Invalid request."], Response::HTTP_FORBIDDEN);
                    $oic = $department->oic_employee_profile_id;

                    $this->trailOICandResetAreaOICRecord($oic, $department);
                    $system_role = SystemRole::where('code', 'DEPT-HEAD-04')->first();
                    $this->revokeOICRights($oic, $system_role->id);
                    break;
                case "Supervisor":
                    $supervisor = Section::where('supervisor_employee_profile_id', $employee_profile->id)->first();

                    if (!$supervisor)
                        return response()->json(['message' => "Invalid request."], Response::HTTP_FORBIDDEN);
                    $oic = $supervisor->oic_employee_profile_id;

                    $this->trailOICandResetAreaOICRecord($oic, $supervisor);
                    $system_role = SystemRole::where('code', 'SECTION-HEAD-05')->first();
                    $this->revokeOICRights($oic, $system_role->id);
                    break;
                case "Unit Head":
                    $unit = Unit::where('head_employee_profile_id', $employee_profile->id)->first();

                    if (!$unit)
                        return response()->json(['message' => "Invalid request."], Response::HTTP_FORBIDDEN);
                    $oic = $unit->oic_employee_profile_id;

                    $this->trailOICandResetAreaOICRecord($oic, $unit);
                    $system_role = SystemRole::where('code', 'UNIT-HEAD-06')->first();
                    $this->revokeOICRights($oic, $system_role->id);
                    break;
                default:
                    return response()->json(['message' => "Invalid."], Response::HTTP_FORBIDDEN);
            }

            Helpers::registerSystemLogs($request, null, true, 'Success in deleting a ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(['message' => "OIC rights successfully revoke."], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'revokeOIC', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function trailOICandResetAreaOICRecord($oic, $area)
    {
        /**
         * Transfer OIC to trail
         */
        OfficerInChargeTrail::create([
            'employee_profile_id' => $oic,
            'division_id' => $area->id,
            'attachment_url' => $area->oic_attachment_url,
            'started_at' => $area->oic_effective_at,
            'ended_at' => $area->oic_end_at
        ]);

        /**
         * Reset OIC record.
         */
        $area->update([
            'oic_employee_profile_id' => null,
            'oic_attachment_url' => null,
            'oic_effective_at' => null,
            'oic_end_at' => null
        ]);
    }

    /**
     * Revoke OIC Special access rights for chief.
     */
    private function revokeOICRights($oic, $system_role_id)
    {
        $special_right = SpecialAccessRole::where('employee_profile_id', $oic)->where('system_role_id', $system_role_id)->first();
        $special_right->delete();
    }

    public function revokeRights($id, $access_right_id, Request $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
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
            DB::beginTransaction();
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !== $cleanData['pin']) {
                DB::rollBack();
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $employee_profile = EmployeeProfile::findOrFail($id);

            if (!$employee_profile) {
                DB::rollBack();
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $position = $employee_profile->position();

            if (is_array($position)) {
                DB::rollBack();
                $area = $employee_profile->assignedArea->findDetails();
                return response()->json(["message" => "Action is prohibited, this employee is currently a " . $position['position'] . " in " . $area['details']->name . "."], Response::HTTP_FORBIDDEN);
            }

            $in_active_employee = InActiveEmployee::create([
                'employee_id' => $employee_profile->employee_id,
                'date_hired' => $employee_profile->date_hired,
                'date_resigned' => $request->date_resigned,
                'employee_profile_id' => $employee_profile->id,
                'status' => strip_tags($request->status),
                'remarks' => strip_tags($request->remarks),
            ]);

            if ($employee_profile->employmentType->name === 'Permanent CTI') {
                $plantilla_number = $employee_profile->assignedArea->plantillaNumber;
                $plantilla_number->update([
                    'employee_profile_id' => null,
                    'is_dissolve' => true
                ]);
            }

            if ($employee_profile->employmentType->name !== 'Job Order') {
                $plantilla_number = $employee_profile->assignedArea->plantillaNumber;
                $plantilla_number->update([
                    'employee_profile_id' => null
                ]);
            }

            $assign_area = $employee_profile->assignedArea;
            
            $areas = [
                'division_id' => $assign_area['division_id'],
                'department_id' => $assign_area['department_id'],
                'section_id' => $assign_area['section_id'],
                'unit_id' => $assign_area['unit_id'],
            ];

            AssignAreaTrail::create([
                ...$areas,
                'employee_profile_id' => $employee_profile->id,
                'started_at' => $employee_profile->date_hired,
                'end_at' => now()
            ]);

            $employee_profile->removeRecords();
            $employee_profile->update([
                'employee_id' => null,
                'date_hired' => null,
                'encrypted_password' => null,
                'password_created_at' => null,
                'password_expiration_at' => null,
                'authorization_pin' => null,
                'allow_time_adjustment' => 0,
                'shifting' => 0,
                'is_2fa' => 0,
                'deactivated_at' => now()
            ]);

            Helpers::registerSystemLogs($request, null, true, 'Success in deleting a ' . $this->SINGULAR_MODULE_NAME . '.');
            DB::commit();

            return response()->json(['message' => 'Employee profile deleted.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            DB::rollBack();
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateEmployeeProfileShifting(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'id' => 'required|integer',
                'shifting' => 'required|string',
            ]);

            // Find the employee profile by its ID
            $id = $request->id;
            $employeeProfile = EmployeeProfile::findOrFail($id);

            // Update the shifting status
            $employeeProfile->shifting = $request->shifting;
            $employeeProfile->save();

            return response()->json(
                [
                    'data' => $employeeProfile,
                    'message' => 'Employee profile shifting updated.'
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'updateEmployeeProfileShifting', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesByArea(Request $request)
    {
        try {
            $employees = collect();
            $sector = $request->sector;
            $area_id = $request->area_id;

            switch ($sector) {
                case 'division':
                    $employees = $employees->merge(
                        AssignArea::with(['employeeProfile.personalInformation'])
                            ->where('division_id', $area_id)
                            ->get()
                    );

                    $departments = Department::where('division_id', $area_id)->get();
                    foreach ($departments as $department) {
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation'])
                                ->where('department_id', $department->id)
                                ->get()
                        );

                        $sections = Section::where('department_id', $department->id)->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation'])
                                    ->where('section_id', $section->id)
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation'])
                                        ->where('unit_id', $unit->id)
                                        ->get()
                                );
                            }
                        }
                    }

                    // Get sections directly under the division (if any) that are not under any department
                    $sections = Section::where('division_id', $area_id)->whereNull('department_id')->get();
                    foreach ($sections as $section) {
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation'])
                                ->where('section_id', $section->id)
                                ->get()
                        );

                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation'])
                                    ->where('unit_id', $unit->id)
                                    ->get()
                            );
                        }
                    }
                    break;

                case 'department':
                    $employees = $employees->merge(
                        AssignArea::with(['employeeProfile.personalInformation'])
                            ->where('department_id', $area_id)
                            ->get()
                    );

                    $sections = Section::where('department_id', $area_id)->get();
                    foreach ($sections as $section) {
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation'])
                                ->where('section_id', $section->id)
                                ->get()
                        );

                        $units = Unit::where('section_id', $section->id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation'])
                                    ->where('unit_id', $unit->id)
                                    ->get()
                            );
                        }
                    }
                    break;

                case 'section':
                    $employees = $employees->merge(
                        AssignArea::with(['employeeProfile.personalInformation'])
                            ->where('section_id', $area_id)
                            ->get()
                    );

                    $units = Unit::where('section_id', $area_id)->get();
                    foreach ($units as $unit) {
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation'])
                                ->where('unit_id', $unit->id)
                                ->get()
                        );
                    }
                    break;

                case 'unit':
                    $employees = $employees->merge(
                        AssignArea::with(['employeeProfile.personalInformation'])
                            ->where('unit_id', $area_id)
                            ->get()
                    );
                    break;
            }

            return response()->json([
                'count' => $employees->count(),
                'data' => EmployeesAssignedAreaResource::collection($employees),
                'message' => 'List of employees by area retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'employeesByArea', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function filterEmployeeProfile(Request $request)
    {
        try {
            $employees = collect();
            $sector = $request->sector;
            $area_id = $request->area_id;
            $employment_type_id = $request->employment_type_id;
            $page = $request->input('page', 1);

            if (!$sector && !$area_id) {
                $employees = AssignArea::with(['employeeProfile.personalInformation'])
                    ->where('employee_profile_id', '<>', 1)
                    ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                        if (!empty($employment_type_id)) {
                            if ($employment_type_id === 1 || $employment_type_id === 2 || $employment_type_id === 3) {
                                $q->whereIn('employment_type_id', [1, 2, 3]);
                            } else {
                                $q->where('employment_type_id', $employment_type_id);
                            }
                        }
                    })
                    ->get();
            } else {
                switch ($sector) {
                    case 'division':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation'])
                                ->where('division_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                    if (!empty($employment_type_id)) {
                                        if ($employment_type_id === 1 || $employment_type_id === 2 || $employment_type_id === 3) {
                                            $q->whereIn('employment_type_id', [1, 2, 3]);
                                        } else {
                                            $q->where('employment_type_id', $employment_type_id);
                                        }
                                    }
                                })
                                ->get()
                        );

                        $departments = Department::where('division_id', $area_id)->get();
                        foreach ($departments as $department) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation'])
                                    ->where('department_id', $department->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                        if (!empty($employment_type_id)) {
                                            if ($employment_type_id === 1 || $employment_type_id === 2 || $employment_type_id === 3) {
                                                $q->whereIn('employment_type_id', [1, 2, 3]);
                                            } else {
                                                $q->where('employment_type_id', $employment_type_id);
                                            }
                                        }
                                    })
                                    ->get()
                            );

                            $sections = Section::where('department_id', $department->id)->get();
                            foreach ($sections as $section) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation'])
                                        ->where('section_id', $section->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                            if (!empty($employment_type_id)) {
                                                if ($employment_type_id === 1 || $employment_type_id === 2 || $employment_type_id === 3) {
                                                    $q->whereIn('employment_type_id', [1, 2, 3]);
                                                } else {
                                                    $q->where('employment_type_id', $employment_type_id);
                                                }
                                            }
                                        })
                                        ->get()
                                );

                                $units = Unit::where('section_id', $section->id)->get();
                                foreach ($units as $unit) {
                                    $employees = $employees->merge(
                                        AssignArea::with(['employeeProfile.personalInformation'])
                                            ->where('unit_id', $unit->id)
                                            ->where('employee_profile_id', '<>', 1)
                                            ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                                if (!empty($employment_type_id)) {
                                                    if ($employment_type_id === 1 || $employment_type_id === 2 || $employment_type_id === 3) {
                                                        $q->whereIn('employment_type_id', [1, 2, 3]);
                                                    } else {
                                                        $q->where('employment_type_id', $employment_type_id);
                                                    }
                                                }
                                            })
                                            ->get()
                                    );
                                }
                            }
                        }

                        // Get sections directly under the division (if any) that are not under any department
                        $sections = Section::where('division_id', $area_id)->whereNull('department_id')->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation'])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                        if (!empty($employment_type_id)) {
                                            if ($employment_type_id === 1 || $employment_type_id === 2 || $employment_type_id === 3) {
                                                $q->whereIn('employment_type_id', [1, 2, 3]);
                                            } else {
                                                $q->where('employment_type_id', $employment_type_id);
                                            }
                                        }
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation'])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                            if (!empty($employment_type_id)) {
                                                if ($employment_type_id === 1 || $employment_type_id === 2 || $employment_type_id === 3) {
                                                    $q->whereIn('employment_type_id', [1, 2, 3]);
                                                } else {
                                                    $q->where('employment_type_id', $employment_type_id);
                                                }
                                            }
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'department':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation'])
                                ->where('department_id', $area_id)
                                ->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                    if (!empty($employment_type_id)) {
                                        if ($employment_type_id === 1 || $employment_type_id === 2 || $employment_type_id === 3) {
                                            $q->whereIn('employment_type_id', [1, 2, 3]);
                                        } else {
                                            $q->where('employment_type_id', $employment_type_id);
                                        }
                                    }
                                })
                                ->get()
                        );

                        $sections = Section::where('department_id', $area_id)->get();
                        foreach ($sections as $section) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation'])
                                    ->where('section_id', $section->id)
                                    ->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                        if (!empty($employment_type_id)) {
                                            if ($employment_type_id === 1 || $employment_type_id === 2 || $employment_type_id === 3) {
                                                $q->whereIn('employment_type_id', [1, 2, 3]);
                                            } else {
                                                $q->where('employment_type_id', $employment_type_id);
                                            }
                                        }
                                    })
                                    ->get()
                            );

                            $units = Unit::where('section_id', $section->id)->get();
                            foreach ($units as $unit) {
                                $employees = $employees->merge(
                                    AssignArea::with(['employeeProfile.personalInformation'])
                                        ->where('unit_id', $unit->id)
                                        ->where('employee_profile_id', '<>', 1)
                                        ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                            if (!empty($employment_type_id)) {
                                                if ($employment_type_id === 1 || $employment_type_id === 2 || $employment_type_id === 3) {
                                                    $q->whereIn('employment_type_id', [1, 2, 3]);
                                                } else {
                                                    $q->where('employment_type_id', $employment_type_id);
                                                }
                                            }
                                        })
                                        ->get()
                                );
                            }
                        }
                        break;

                    case 'section':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation'])
                                ->where('section_id', $area_id)->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                    if (!empty($employment_type_id)) {
                                        if ($employment_type_id === 1 || $employment_type_id === 2 || $employment_type_id === 3) {
                                            $q->whereIn('employment_type_id', [1, 2, 3]);
                                        } else {
                                            $q->where('employment_type_id', $employment_type_id);
                                        }
                                    }
                                })
                                ->get()
                        );

                        $units = Unit::where('section_id', $area_id)->get();
                        foreach ($units as $unit) {
                            $employees = $employees->merge(
                                AssignArea::with(['employeeProfile.personalInformation'])
                                    ->where('unit_id', $unit->id)->where('employee_profile_id', '<>', 1)
                                    ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                        if (!empty($employment_type_id)) {
                                            if ($employment_type_id === 1 || $employment_type_id === 2 || $employment_type_id === 3) {
                                                $q->whereIn('employment_type_id', [1, 2, 3]);
                                            } else {
                                                $q->where('employment_type_id', $employment_type_id);
                                            }
                                        }
                                    })
                                    ->get()
                            );
                        }
                        break;

                    case 'unit':
                        $employees = $employees->merge(
                            AssignArea::with(['employeeProfile.personalInformation'])
                                ->where('unit_id', $area_id)->where('employee_profile_id', '<>', 1)
                                ->whereHas('employeeProfile', function ($q) use ($employment_type_id) {
                                    if (!empty($employment_type_id)) {
                                        if ($employment_type_id === 1 || $employment_type_id === 2 || $employment_type_id === 3) {
                                            $q->whereIn('employment_type_id', [1, 2, 3]);
                                        } else {
                                            $q->where('employment_type_id', $employment_type_id);
                                        }
                                    }
                                })
                                ->get()
                        );
                        break;
                }
            }

            // Create a new LengthAwarePaginator instance
            $currentPage = $page;
            $perPage = 10;
            $total = $employees->unique('employee_profile_id')->count(); // Total number of unique employees

            // Slice the collection to get the items for the current page
            $currentItems = $employees->unique('employee_profile_id')
                ->forPage($currentPage, $perPage);

            $employee_profiles = new LengthAwarePaginator(
                $currentItems,
                $total,
                $perPage,
                $currentPage,
                ['path' => Paginator::resolveCurrentPath()]
            );

            return response()->json([
                'current_page' => $employee_profiles->currentPage(),
                'last_page' => $employee_profiles->lastPage(),
                'per_page' => $employee_profiles->perPage(),
                'total' => $employee_profiles->total(),
                'data' => EmployeesAssignedAreaResource::collection($employee_profiles->items()),
                'message' => 'List of filtered employees retrieved'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterEmployeeProfile', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function remove(Request $request)
    {
        $remarks = $request->remarks;

        $in_active_employees = InActiveEmployee::where('remarks', 'like', "%".$remarks."%")->get();

        DB::beginTransaction();

        try{
            foreach($in_active_employees as $in_active_employee)
            {
                $employee_profile = EmployeeProfile::find($in_active_employee->employee_profile_id);
    
                $assign_area = AssignArea::where('employee_profile_id', $employee_profile->id)->first();
                $assign_area_trail = AssignAreaTrail::where('employee_profile_id', $employee_profile->id)->get();
                
                $personal_information = PersonalInformation::find($employee_profile->personal_information_id);
                $addresses = Address::where('personal_information_id', $personal_information->id)->get();
                $contact = Contact::where('personal_information_id', $personal_information->id)->first();
                $childs = Child::where('personal_information_id', $personal_information->id)->get();
                $civil_service_eligibilities = CivilServiceEligibility::where('personal_information_id', $personal_information->id)->get();
                $education_backgrounds = EducationalBackground::where('personal_information_id', $personal_information->id)->get();
                $family_backgrounds = FamilyBackground::where('personal_information_id', $personal_information->id)->get();
                $identification_numbers = IdentificationNumber::where('personal_information_id', $personal_information->id)->get();
                $issuance_information = IssuanceInformation::where('employee_profile_id', $employee_profile->id)->first();
                $legal_informations = LegalInformation::where('personal_information_id', $personal_information->id)->get();
                $other_informations = OtherInformation::where("personal_information_id", $personal_information->id)->get();
                $references = Reference::where('personal_information_id', $personal_information->id)->get();
                $trainings = Training::where('personal_information_id', $personal_information->id)->get();
                $special_access_roles = SpecialAccessRole::where('employee_profile_id', $employee_profile->id)->get();
                $employee_leave_credits = EmployeeLeaveCredit::where('employee_profile_id', $employee_profile->id)->get();
                $employee_overtime_credits = EmployeeOvertimeCredit::where('employee_profile_id', $employee_profile->id)->get();
                $failed_login_trails = FailedLoginTrail::where('employee_profile_id', $employee_profile->id)->get();
                $employee_redcap_modules = EmployeeRedcapModules::where('employee_profile_id', $employee_profile->id)->get();
    
                if($contact !== null){
                    $contact->delete();
                }
    
                if(count($addresses) > 0){
                    Address::where('personal_information_id', $personal_information->id)->delete();
                }
    
                if(count($childs) > 0){
                    Child::where('personal_information_id', $personal_information->id)->delete();
                }
    
                if(count($civil_service_eligibilities) > 0){
                    CivilServiceEligibility::where('personal_information_id', $personal_information->id)->delete();
                }
    
                if(count($education_backgrounds) > 0){
                    EducationalBackground::where('personal_information_id', $personal_information->id)->delete();
                }
    
                if(count($family_backgrounds) > 0){
                    FamilyBackground::where('personal_information_id', $personal_information->id)->delete();    
                }
    
                if(count($identification_numbers) > 0){
                    IdentificationNumber::where('personal_information_id', $personal_information->id)->delete();
                }
    
                if($issuance_information !== null){
                    $issuance_information->delete();
                }
    
                if(count($legal_informations) > 0){
                    LegalInformation::where('personal_information_id', $personal_information->id)->delete();
                }
    
                if(count($other_informations) > 0){
                    OtherInformation::where("personal_information_id", $personal_information->id)->delete();
                }
    
                if(count($references) > 0){
                    Reference::where('personal_information_id', $personal_information->id)->delete();
                }
    
                if(count($trainings) > 0){
                    Training::where('personal_information_id', $personal_information->id)->delete();
                }
    
                if(count($special_access_roles) > 0){
                    SpecialAccessRole::where('employee_profile_id', $employee_profile->id)->delete();
                }
    
                if(count($assign_area_trail) > 0){
                    AssignAreaTrail::where('employee_profile_id', $employee_profile->id)->delete();
                }
    
                if($assign_area !== null){
                    $assign_area->delete();
                }

                if(count($assign_area_trail) > 0){
                    AssignAreaTrail::where('employee_profile_id', $employee_profile->id)->delete();
                }

                if(count($employee_leave_credits) > 0){
                    EmployeeLeaveCredit::where('employee_profile_id', $employee_profile->id)->delete();
                }

                if(count($employee_overtime_credits) > 0){
                    foreach($employee_overtime_credits as $employee_overtime_credit){
                        EmployeeOvertimeCreditLog::where('employee_ot_credit_id', $employee_overtime_credit->id)->delete();
                        $employee_overtime_credit->delete();
                    }
                }

                if(count($failed_login_trails) > 0){
                    FailedLoginTrail::where('employee_profile_id', $employee_profile->id)->delete();
                }

                if(count($employee_redcap_modules) > 0){
                    EmployeeRedcapModules::where('employee_profile_id', $employee_profile->id)->delete();
                }
    
                $in_active_employee->delete();
                $employee_profile->delete();
                $personal_information->delete();
            }
        }catch(\Throwable $th){
            DB::rollBack();
            return response()->json(['message' => "Failed to delete duplicate entry on 'InActiveEmployee' record. ".$th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        DB::commit();

        return response()->json(['message' => "Successfully deleted in active employee with related remarks of ".$remarks." ."], Response::HTTP_OK);
    }    


    public function updateEmployeeProfileData(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'id' => 'required|integer',
                'shifting' => 'required|string',
                'date_hired' => 'required|date',
                'employee_id' => 'required|string',
            ]);

            // Find the employee profile by its ID
            $id = $request->id;
            $employeeProfile = EmployeeProfile::findOrFail($id);

            // Update the shifting status
            $employeeProfile->shifting = $request->shifting;
            $employeeProfile->date_hired = $request->date_hired;$employeeProfile->employee_id = $request->employee_id;
            $employeeProfile->save();

            return response()->json(
                [
                    'data' => $employeeProfile,
                    'message' => 'Employee profile data updated.'
                ],
                Response::HTTP_OK
            );
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'updateEmployeeProfileData', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
