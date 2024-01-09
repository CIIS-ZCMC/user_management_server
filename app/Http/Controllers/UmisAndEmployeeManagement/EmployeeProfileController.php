<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Controllers\DTR\TwoFactorAuthController;
use App\Http\Resources\AddressResource;
use App\Http\Resources\ChildResource;
use App\Http\Resources\CivilServiceEligibilityResource;
use App\Http\Resources\ContactResource;
use App\Http\Resources\EducationalBackgroundResource;
use App\Http\Resources\FamilyBackGroundResource;
use App\Http\Resources\OtherInformationResource;
use App\Http\Resources\PersonalInformationResource;
use App\Http\Resources\TrainingResource;
use App\Http\Resources\VoluntaryWorkResource;
use App\Http\Resources\WorkExperienceResource;
use App\Methods\MailConfig;
use App\Models\AccessToken;
use App\Models\AssignAreaTrail;
use App\Models\Contact;
use App\Models\InActiveEmployee;
use App\Models\PasswordTrail;
use App\Models\PlantillaNumber;
use Carbon\Carbon;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;   
use Illuminate\Support\Facades\Hash;
use App\Services\RequestLogger;
use App\Services\FileValidationAndUpload;
use App\Http\Requests\SignInRequest;
use App\Http\Requests\EmployeeProfileRequest;
use App\Http\Resources\EmployeeProfileResource;
use App\Http\Requests\EmployeesByAreaAssignedRequest;
use App\Http\Resources\EmployeesByAreaAssignedResource;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Resources\EmployeeDTRList;
use App\Models\AssignArea;
use App\Models\DefaultPassword;
use App\Models\EmployeeProfile;
use App\Models\LoginTrail;
use App\Models\PositionSystemRole;
use App\Models\SpecialAccessRole;

class EmployeeProfileController extends Controller
{
    private $CONTROLLER_NAME = 'Employee Profile';
    private $PLURAL_MODULE_NAME = 'employee profiles';
    private $SINGULAR_MODULE_NAME = 'employee profile';

    protected $requestLogger;
    protected $file_validation_and_upload;

    
    private $mail;
    private $two_auth;

    public function __construct(RequestLogger $requestLogger, FileValidationAndUpload $file_validation_and_upload)
    {
        $this->requestLogger = $requestLogger;
        $this->file_validation_and_upload = $file_validation_and_upload;
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
            
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $employee_profile = EmployeeProfile::where('employee_id', $cleanData['employee_id'])->first();

            if (!$employee_profile) {
                return response()->json(['message' => "Employee id or password incorrect."], Response::HTTP_UNAUTHORIZED);
            }            

            if(!$employee_profile->isDeactivated()){
                return response()->json(['message' => "Account is deactivated."], Response::HTTP_FORBIDDEN);
            }

            $decryptedPassword = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($cleanData['password'].env("SALT_VALUE"), $decryptedPassword)) {
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
             * If account is login to other device
             * notify user for that and allow user to choose to cancel or proceed to signout account to other device
             * return employee profile id when user choose to proceed signout in other device
             * for server to be able to determine which account it want to sign out.
             * If account is singin with in the same machine like ip and device and platform continue
             * signin without signout to current signined of account.
             * Reuse the created token of first instance of signin to have single access token.
             */

            $access_token = $employee_profile->accessToken;

            if(!$access_token)
            {
                $ip = $request->ip();
                $created_at = Carbon::parse($access_token['created_at']);
                $current_time = Carbon::now();

                $difference_in_minutes = $current_time->diffInMinutes($created_at);
                
                $login_trail = LoginTrail::where('employee_profile_id', $employee_profile['id'])->first();

                if($difference_in_minutes < 5 && $login_trail['ip_address'] != $ip)
                {

                    $body = view('mail.otp', ['otpcode' => $this->two_auth->getOTP($employee_profile)]);
                    $data = [
                        'Subject' => 'ONE TIME PIN',
                        'To_receiver' => $employee_profile->personalinformation->contact->email,
                        'Receiver_Name' => $employee_profile->personalInformation->name(),
                        'Body' => $body
                    ];
        
                    if ($this->mail->send($data)) {
                        return response()->json(['message' => "You're account is currently signined to other device. A OTP has sent to your email if you want to signout from other device submit the OTP."], Response::HTTP_OK)
                            ->cookie('employee_details', json_encode(['employee_id' => $employee_profile->employee_id]), 60, '/', env('SESSION_DOMAIN'), true);
                    }

                    return response()->json(['message' => "Your account is currently signined to other device, sending otp to your email has failed please try again later."], Response::HTTP_INTERNAL_SERVER_ERROR);                    
                }

                AccessToken::where('employee_profile_id', $employee_profile->id)->delete();
            }
            

            /**
             * Validate for 2FA
             * if 2FA is activated send OTP to email to validate ownership
             */

            if($employee_profile->is_2fa){
                $body = view('mail.otp', ['otpcode' => $this->two_auth->getOTP($employee_profile)]);
                $data = [
                    'Subject' => 'ONE TIME PIN',
                    'To_receiver' => $employee_profile->personalinformation->contact->email,
                    'Receiver_Name' => $employee_profile->personalInformation->name(),
                    'Body' => $body
                ];
        
                if ($this->mail->send($data)) {
                    return response()->json(['message' => "OTP has sent to your email, submit the OTP to verify that this is your account."], Response::HTTP_OK)
                        ->cookie('employee_details', json_encode(['employee_id' => $employee_profile->employee_id]), 60, '/', env('SESSION_DOMAIN'), true);
                }
            }

            $token = $employee_profile->createToken();

            $personal_information = $employee_profile->personalInformation;

            $assigned_area = $employee_profile->assignedArea;
            $plantilla = null;
            $designation = null;

            if($assigned_area['plantilla_id'] === null)
            {
                $designation = $assigned_area->designation;
            }else{
                //Employment is plantilla retrieve the plantilla and its designation.
                $plantilla = $assigned_area->plantilla;
                $designation = $plantilla->designation;
            }

            $special_access_roles = $employee_profile->specialAccessRole;

            //Retrieve Sidebar Details for the employee base on designation.
            $side_bar_details = $this->buildSidebarDetails($employee_profile, $designation, $special_access_roles);  

            $area_assigned = $employee_profile->assignedArea->findDetails(); 

            $position = $employee_profile->position();
            
            $last_login = LoginTrail::where('employee_profile_id', $employee_profile->id)->orderByDesc('created_at')->first();

            $employee = [
                'profile_url' => $employee_profile->profile_url,
                'employee_id' => $employee_profile->employee_id,
                'position' => $position,
                'job_position' => $designation->name,
                'date_hired' => $employee_profile->date_hired,
                'citizenship' => $personal_information->citizenship,
                'job_type' => $employee_profile->employmentType->name,
                'years_of_service' => $employee_profile->personalInformation->years_of_service,
                'last_login' => $last_login === null? null: $last_login->created_at
            ];

            $personal_information_data = [
                'full_name' => $personal_information->nameWithSurnameFirst(),
                'name_extension' => $personal_information->name_extension === null? 'NONE':$personal_information->name_extension,
                'employee_id' => $employee_profile->employee_id,
                'years_of_service' => $employee_profile->personalInformation->years_of_service === null? 'NONE':$personal_information->years_of_service,
                'name_title' => $personal_information->name_title === null? 'NONE':$personal_information->name_title,
                'sex' => $personal_information->sex,
                'date_of_birth' => $personal_information->date_of_birth,
                'date_hired' => $employee_profile->date_hired,
                'place_of_birth' => $personal_information->place_of_birth,
                'civil_status' => $personal_information->civil_status,
                'date_of_marriage' => $personal_information->date_of_marriage === null? 'NONE':$personal_information->date_of_marriage,
                'agency_employee_no' => $employee_profile->agency_employee_no === null? 'NONE':$personal_information->agency_employee_no,
                'blood_type' => $personal_information->blood_type === null? 'NONE':$personal_information->blood_type,
                'height' => $personal_information->height,
                'weight' => $personal_information->weight,
            ];

            $data = [
                'employee_id' => $employee_profile['employee_id'],
                'name' => $personal_information->employeeName(),
                'designation'=> $designation['name'],
                'employee_details' => [
                    'employee' => $employee,
                    'personal_information' => $personal_information_data,
                    'contact' => new ContactResource($personal_information->contact),
                    'address' => AddressResource::collection($personal_information->addresses),
                    'family_background' => new FamilyBackGroundResource($personal_information->familyBackground),
                    'children' => ChildResource::collection($personal_information->children),
                    'education' => EducationalBackgroundResource::collection($personal_information->educationalBackground),
                    'affiliations_and_others' => [
                        'civil_service_eligibility' => CivilServiceEligibilityResource::collection($personal_information->civilServiceEligibility),
                        'work_experience' => WorkExperienceResource::collection($personal_information->workExperience),
                        'voluntary_work_or_involvement' => VoluntaryWorkResource::collection($personal_information->voluntaryWork),
                        'training' => TrainingResource::collection($personal_information->training),
                        'other' => $personal_information->other
                    ]
                ],
                'area_assigned' => $area_assigned['details']->name,
                'area_sector' => $area_assigned['sector'],
                'side_bar_details' => $side_bar_details
            ];

            LoginTrail::create([
                'signin_at' => now(),
                'ip_address' => $request->ip(),
                'device' => ($device['is_desktop'] ? 'Desktop': $device['is_mobile']) ?'Mobile':'Unknown', 
                'platform' => is_bool($device['platform'])?'Postman':$device['platform'],
                'browser' => is_bool( $device['browser'])?'Postman':$device['browser'],
                'browser_version' => is_bool($device['version'])?'Postman':$device['version'],
                'employee_profile_id' => $employee_profile['id']
            ]);

            return response()
                ->json(["data" => $data, 'message' => "Success login."], Response::HTTP_OK)
                ->cookie(env('COOKIE_NAME'), json_encode(['token' => $token]), 60, '/', env('SESSION_DOMAIN'), true);
        } catch (\Throwable $th) {
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'signIn', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function buildSidebarDetails($employee_profile, $designation, $special_access_roles)
    {
        $sidebar_cache = Cache::get($designation['name']);

        if($sidebar_cache === null)
        {   
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
                        'roleModulePermissions' => function($query){
                            $query->with([
                                'modulePermission' => function($query){
                                    $query->with(['module', 'permission']);
                                }
                            ]);
                        },
                    ]);
                }
            ])->where('designation_id',$designation['id'])->get();

            $side_bar_details['designation_id'] = $designation['id'];
            $side_bar_details['designation_name'] = $designation['name'];
            $side_bar_details['system'] = [];
            
            /**
             * Convert to meet sidebar data format.
             * Iterate to every system roles.
             */

            foreach($position_system_roles as $key => $position_system_role)
            {
                $system_exist = false;
                $system_role = $position_system_role['systemRole'];
                
                /**
                 * If side bar details system array is empty
                 */
                if (!$side_bar_details['system']) {
                    $side_bar_details['system'][] = $this->buildSystemDetails($system_role);
                    continue;
                }

                foreach($side_bar_details['system'] as $key => $system)
                {
                    if($system['id'] === $system_role->system['id'])
                    {
                        $system_exist = true;
                        $system[] = $this->buildRoleDetails($system_role);
                        break;
                    }
                }

                if(!$system_exist){
                    $side_bar_details['system'][] = $this->buildSystemDetails($system_role);
                }
            }

            $cacheExpiration = Carbon::now()->addYear();
            Cache::put($designation['name'], $side_bar_details, $cacheExpiration);
        }else{
            $side_bar_details = $sidebar_cache;
        }
    
        /**
         * For Empoyee with Special Access Roles
         * Validate if employee has Special Access Roles
         * Update Sidebar Details.
         */
        if(!empty($special_access_roles))
        {
            $special_access_permissions = SpecialAccessRole::with([
                'systemRole' => function ($query) {
                    $query->with([
                        'system',
                        'roleModulePermissions' => function ($query) {
                            $query->with([
                                'modulePermission' => function($query){
                                    $query->with(['module', 'permission']);
                                }
                            ]);
                        }
                    ]);
                }
            ])->where('employee_profile_id', $employee_profile['id'])->get();
                
            if(count($special_access_permissions) > 0)
            {
                foreach($special_access_permissions as $key => $special_access_permission)
                {
                    $system_exist = false;
                    $system_role = $special_access_permission['systemRole'];

                    foreach($side_bar_details['system'] as $key => $system)
                    {
                        if($system['id'] === $system_role->system['id'])
                        {
                            $system_exist = true;
                            $system[] = $this->buildRoleDetails($system_role);
                            break;
                        }
                    }

                    if(!$system_exist){
                        $side_bar_details->system[] = $this->buildSystemDetails($system_role);
                    }
                }
            }
        }

        return $side_bar_details;
    }

    private function buildSystemDetails($system_role) 
    {
        return [
            'id' => $system_role->system['id'],
            'name' => $system_role->system['name'],
            'code' => $system_role->system['code'],
            'roles' => [$this->buildRoleDetails($system_role)],
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
                $modules[$module_name] = ['name' => $module_name, 'code' => $module_code,'permissions' => []];
            }
    
            if (!in_array($permission_action, $modules[$module_name]['permissions'])) {
                $modules[$module_name]['permissions'][] = $permission_action;
            }
        }
    
        return [
            'id' => $system_role->id,
            'name' => $system_role->name,
            'modules' => array_values($modules), // Resetting array keys
        ];
    }
    
    //**Require employee id *
    public function signOutFromOtherDevice($id, Request $request)
    {
        try{
            $employee_profile = EmployeeProfile::find($id);

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $access_token = $employee_profile->accessToken;
            $access_token->delete();

            $agent = new Agent();
            $device = [
                'is_desktop' => $agent->isDesktop(),
                'is_mobile' => $agent->isMobile(),
                'platform' => $agent->platform(),
                'browser' => $agent->browser(),
                'version' => $agent->version($agent->browser())
            ];

            $token = $employee_profile->createToken();

            $personal_information = $employee_profile->personalInformation;
            $name = $personal_information->employeeName();

            $assigned_area = $employee_profile->assignedArea;
            $plantilla = null;
            $designation = null;

            if($assigned_area['plantilla_id'] === null)
            {
                $designation = $assigned_area->designation;
            }else{
                //Employment is plantilla retrieve the plantilla and its designation.
                $plantilla = $assigned_area->plantilla;
                $designation = $plantilla->designation;
            }

            $special_access_roles = $employee_profile->specialAccessRole;
            
            //Retrieve Sidebar Details for the employee base on designation.
            $side_bar_details = $this->buildSidebarDetails($employee_profile, $designation, $special_access_roles);  

            $area_assigned = $employee_profile->assignArea->findDetails; 

            $position = $employee_profile->position();
            
            $last_login = LoginTrail::where('employee_profile_id', $employee_profile->id)->orderByDesc('created_at')->first();

            $employee = [
                'profile_url' => $employee_profile->profile_url,
                'employee_id' => $employee_profile->employee_id,
                'full_name' => $employee_profile->personalInformation->nameWithSurnameFirst(),
                'position' => $position,
                'job_position' => $designation->name,
                'date_hired' => $employee_profile->date_hired,
                'job_type' => $employee_profile->employmentType->name,
                'years_of_service' => $employee_profile->personalInformation->years_of_service,
                'last_login' => $last_login === null? null: $last_login->created_at
            ];

            $data = [
                'employee_id' => $employee_profile['employee_id'],
                'name' => $personal_information->employeeName(),
                'designation'=> $designation['name'],
                'employee_details' => [
                    'employee' => $employee,
                    'personal_information' => new PersonalInformationResource( $personal_information),
                    'contact' => new ContactResource($personal_information->contact),
                    'address' => AddressResource::collection($personal_information->addresses),
                    'family_background' => new FamilyBackGroundResource($personal_information->familyBackground),
                    'children' => ChildResource::collection($personal_information->children),
                    'education' => EducationalBackgroundResource::collection($personal_information->educationalBackground),
                    'affiliations_and_others' => [
                        'civil_service_eligibility' => CivilServiceEligibilityResource::collection($personal_information->civilServiceEligibility),
                        'work_experience' => WorkExperienceResource::collection($personal_information->workExperience),
                        'voluntary_work_or_involvement' => VoluntaryWorkResource::collection($personal_information->voluntaryWork),
                        'training' => TrainingResource::collection($personal_information->training),
                        'other' => $personal_information->other
                    ]
                ],
                'area_assigned' => $area_assigned['details']->name,
                'area_sector' => $area_assigned['sector'],
                'side_bar_details' => $side_bar_details
            ];

            LoginTrail::create([
                'signin_at' => now(),
                'ip_address' => $request->ip(),
                'device' => ($device['isDesktop'] ? 'Desktop': $device['isMobile']) ?'Mobile':'Unknown', 
                'platform' => $device['platform'],
                'browser' => $device['browser'],
                'browser_version' => $device['version'],
                'employee_profile_id' => $employee_profile['id']
            ]);

            return response()
                ->json(['data' => $data, 'message' => "Success signout to other device you are now login."], Response::HTTP_OK)
                ->cookie(env('COOKIE_NAME'), json_encode(['token' => $token]), 60, '/', env('SESSION_DOMAIN'), true);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'signOutFromOtherDevice', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function revalidateAccessToken(Request $request)
    {
        try{
            $employee_profile = $request->user;

            $personal_information = $employee_profile->personalInformation;
            $name = $personal_information->employeeName();

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

            if($assigned_area['plantilla_id'] === null)
            {
                $designation = $assigned_area->designation;
            }else{
                //Employment is plantilla retrieve the plantilla and its designation.
                $plantilla = $assigned_area->plantilla;
                $designation = $plantilla->designation;
            }

            $special_access_roles = $employee_profile->specialAccessRole;

            //Retrieve Sidebar Details for the employee base on designation.
            $side_bar_details = $this->buildSidebarDetails($employee_profile, $designation, $special_access_roles);  

            $area_assigned = $employee_profile->assignedArea->findDetails(); 

            $position = $employee_profile->position();
            
            $last_login = LoginTrail::where('employee_profile_id', $employee_profile->id)->orderByDesc('created_at')->first();

            $employee = [
                'profile_url' => $employee_profile->profile_url,
                'employee_id' => $employee_profile->employee_id,
                'full_name' => $employee_profile->personalInformation->nameWithSurnameFirst(),
                'position' => $position,
                'job_position' => $designation->name,
                'date_hired' => $employee_profile->date_hired,
                'job_type' => $employee_profile->employmentType->name,
                'years_of_service' => $employee_profile->personalInformation->years_of_service,
                'last_login' => $last_login === null? null: $last_login->created_at
            ];

            $data = [
                'employee_id' => $employee_profile['employee_id'],
                'name' => $personal_information->employeeName(),
                'designation'=> $designation['name'],
                'employee_details' => [
                    'employee' => $employee,
                    'personal_information' => new PersonalInformationResource( $personal_information),
                    'contact' => new ContactResource($personal_information->contact),
                    'address' => AddressResource::collection($personal_information->addresses),
                    'family_background' => new FamilyBackGroundResource($personal_information->familyBackground),
                    'children' => ChildResource::collection($personal_information->children),
                    'education' => EducationalBackgroundResource::collection($personal_information->educationalBackground),
                    'affiliations_and_others' => [
                        'civil_service_eligibility' => CivilServiceEligibilityResource::collection($personal_information->civilServiceEligibility),
                        'work_experience' => WorkExperienceResource::collection($personal_information->workExperience),
                        'voluntary_work_or_involvement' => VoluntaryWorkResource::collection($personal_information->voluntaryWork),
                        'training' => TrainingResource::collection($personal_information->training),
                        'other' => $personal_information->other
                    ]
                ],
                'area_assigned' => $area_assigned['details']->name,
                'area_sector' => $area_assigned['sector'],
                'side_bar_details' => $side_bar_details
            ];

            LoginTrail::create([
                'signin_at' => now(),
                'ip_address' => $request->ip(),
                'device' => ($device['is_desktop'] ? 'Desktop': $device['is_mobile']) ?'Mobile':'Unknown', 
                'platform' => is_bool($device['platform'])?'Postman':$device['platform'],
                'browser' => is_bool( $device['browser'])?'Postman':$device['browser'],
                'browser_version' => is_bool($device['version'])?'Postman':$device['version'],
                'employee_profile_id' => $employee_profile['id']
            ]);

            return response()
                ->json(["data" => $data, 'message' => "Success login."], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'revalidateAccessToken', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function signOut(Request $request)
    {
        try{
            $user = $request->user;
    
            $accessToken = $user->accessToken;
            
            foreach ($accessToken as $token) {
                $token->delete();
            }

            return response()->json(['message' => 'User signout.'], Response::HTTP_OK)->cookie(env('COOKIE_NAME'), '', -1);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'signOut', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function verifyEmailAndSendOTP(Request $request)
    {
        try{
            $email = strip_tags($request->email);
            $contact = Contact::where('email_address', $email)->first();

            if(!$contact){
                return response()->json(['message' => "Email doesn't exist."], Response::HTTP_UNAUTHORIZED);
            }

            $employee = $contact->personalInformation->employeeProfile;

            $data = $request->data;

            $body = view('mail.otp', ['otpcode' => $this->two_auth->getOTP($employee)]);
            $data = [
                'Subject' => 'ONE TIME PIN',
                'To_receiver' => $email,
                'Receiver_Name' => $employee->personalInformation->name(),
                'Body' => $body
            ];

            if ($this->mail->send($data)) {
                return response()->json(['message' => 'Please check your email address for OTP.'], Response::HTTP_OK)
                    ->cookie('employee_details', json_encode(['email' => $email, 'employee_id' => $employee->employee_id]), 60, '/', env('SESSION_DOMAIN'), true);
            }

            return response()->json([
                'message' => 'Failed to send OTP to your email.'
            ], Response::HTTP_BAD_REQUEST);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'signOut', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function verifyOTP(Request $request)
    {
        try{
            $otp = strip_tags($request->otp);
            $employee_details = json_decode($request->cookie('employee_details'));

            $employee = EmployeeProfile::where('employee_id', $employee_details->employee_id)->first();

            $otpExpirationMinutes = 5;
            $currentDateTime = Carbon::now();
            $otp_expiration = Carbon::parse($employee->otp_expiration);

            if ($currentDateTime->diffInMinutes($otp_expiration) > $otpExpirationMinutes) {
                return response()->json(['message' => 'OTP has expired.'], Response::HTTP_BAD_REQUEST);
            }

            if((int)$otp !== $employee->otp){
                return response()->json(['message' => 'Invalid OTP.'], Response::HTTP_BAD_REQUEST);
            }

            $employee->update([
                'otp' => null,
                'otp_expiration' => null
            ]);

            return response()->json(['message' => "Valid OTP, redirecting to new password form."], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'signOut', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function newPassword(Request $request)
    {
        try{
            $employee_details = json_decode($request->cookie('employee_details'));

            $employee_profile = EmployeeProfile::where('employee_id', $employee_details->employee_id)->first();

            $new_password = strip_tags($request->new_password);

            $hashPassword = Hash::make($new_password.env('SALT_VALUE'));
            $encryptedPassword = Crypt::encryptString($hashPassword);

            $now = Carbon::now();
            $fortyDaysFromNow = $now->addDays(40);
            $fortyDaysExpiration = $fortyDaysFromNow->toDateTimeString();

            $old_password = PasswordTrail::create([
                'old_password' => $employee_profile->password_encrypted,
                'password_created_at' => $employee_profile->password_created_at,
                'expired_at' => $employee_profile->password_expiration_at,
                'employee_profile_id' => $employee_profile->id
            ]);

            if(!$old_password){
                return response()->json(['message' => "A problem encounter while trying to register new password."], Response::HTTP_BAD_REQUEST);
            }

            $employee_profile->update([
                'password_encrypted' => $encryptedPassword,
                'password_created_at' => now(),
                'password_expiration_at' => $fortyDaysExpiration
            ]);


            $agent = new Agent();
            $device = [
                'is_desktop' => $agent->isDesktop(),
                'is_mobile' => $agent->isMobile(),
                'platform' => $agent->platform(),
                'browser' => $agent->browser(),
                'version' => $agent->version($agent->browser())
            ];
            
            $access_token = $employee_profile->accessToken;

            if(!$access_token)
            {
                $ip = $request->ip();
                $created_at = Carbon::parse($access_token['created_at']);
                $current_time = Carbon::now();

                $difference_in_minutes = $current_time->diffInMinutes($created_at);
                
                $login_trail = LoginTrail::where('employee_profile_id', $employee_profile['id'])->first();

                if($difference_in_minutes < 5 && $login_trail['ip_address'] != $ip)
                {
                    return response()->json(['data' => $employee_profile['id'],'message' => "You are currently login to different Device."], Response::HTTP_OK);
                }
            }

            $token = $employee_profile->createToken();

            $personal_information = $employee_profile->personalInformation;
            $name = $personal_information->employeeName();

            $assigned_area = $employee_profile->assignedArea;
            $plantilla = null;
            $designation = null;

            if($assigned_area['plantilla_id'] === null)
            {
                $designation = $assigned_area->designation;
            }else{
                //Employment is plantilla retrieve the plantilla and its designation.
                $plantilla = $assigned_area->plantilla;
                $designation = $plantilla->designation;
            }

            $special_access_roles = $employee_profile->specialAccessRole;

            //Retrieve Sidebar Details for the employee base on designation.
            $side_bar_details = $this->buildSidebarDetails($employee_profile, $designation, $special_access_roles);  

            $area_assigned = $employee_profile->assignedArea->findDetails(); 

            $position = $employee_profile->position();
            
            $last_login = LoginTrail::where('employee_profile_id', $employee_profile->id)->orderByDesc('created_at')->first();

            $employee = [
                'profile_url' => $employee_profile->profile_url,
                'employee_id' => $employee_profile->employee_id,
                'full_name' => $employee_profile->personalInformation->nameWithSurnameFirst(),
                'position' => $position,
                'job_position' => $designation->name,
                'date_hired' => $employee_profile->date_hired,
                'job_type' => $employee_profile->employmentType->name,
                'years_of_service' => $employee_profile->personalInformation->years_of_service,
                'last_login' => $last_login === null? null: $last_login->created_at
            ];

            $data = [
                'employee_id' => $employee_profile['employee_id'],
                'name' => $personal_information->employeeName(),
                'designation'=> $designation['name'],
                'employee_details' => [
                    'employee' => $employee,
                    'personal_information' => new PersonalInformationResource( $personal_information),
                    'contact' => new ContactResource($personal_information->contact),
                    'address' => AddressResource::collection($personal_information->addresses),
                    'family_background' => new FamilyBackGroundResource($personal_information->familyBackground),
                    'children' => ChildResource::collection($personal_information->children),
                    'education' => EducationalBackgroundResource::collection($personal_information->educationalBackground),
                    'affiliations_and_others' => [
                        'civil_service_eligibility' => CivilServiceEligibilityResource::collection($personal_information->civilServiceEligibility),
                        'work_experience' => WorkExperienceResource::collection($personal_information->workExperience),
                        'voluntary_work_or_involvement' => VoluntaryWorkResource::collection($personal_information->voluntaryWork),
                        'training' => TrainingResource::collection($personal_information->training),
                        'other' => $personal_information->other
                    ]
                ],
                'area_assigned' => $area_assigned['details']->name,
                'area_sector' => $area_assigned['sector'],
                'side_bar_details' => $side_bar_details
            ];

            LoginTrail::create([
                'signin_at' => now(),
                'ip_address' => $request->ip(),
                'device' => ($device['is_desktop'] ? 'Desktop': $device['is_mobile']) ?'Mobile':'Unknown', 
                'platform' => is_bool($device['platform'])?'Postman':$device['platform'],
                'browser' => is_bool( $device['browser'])?'Postman':$device['browser'],
                'browser_version' => is_bool($device['version'])?'Postman':$device['version'],
                'employee_profile_id' => $employee_profile['id']
            ]);

            return response()
                ->json(["data" => $data, 'message' => "Success login."], Response::HTTP_OK)
                ->cookie(env('COOKIE_NAME'), json_encode(['token' => $token]), 60, '/', env('SESSION_DOMAIN'), true);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'signOut', $th->getMessage());
        }
    }

    public function employeesByAreaAssigned(EmployeesByAreaAssignedRequest $request)
    {
        try{
            $area = strip_tags($request->query('id'));
            $sector = strip_tags($request->query('sector'));
            $employees = [];
            $key = '';

            switch($sector){
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

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in fetching a '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => EmployeesByAreaAssignedResource::collection($employees), 
                'message' => 'list of employees retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function employeesDTRList(Request $request)
    {
        try{
            $employment_type_id = $request->employment_type_id;

            if($employment_type_id !== null){
                $employee_profiles = EmployeeProfile::where('employment_type_id', $employment_type_id)->get();
                
                return response()->json([
                    'data' => EmployeeDTRList::collection($employee_profiles), 
                    'message' => 'list of employees retrieved.'
                ], Response::HTTP_OK);
            }

            $employee_profiles = EmployeeProfile::all();

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in fetching a '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => EmployeeDTRList::collection($employee_profiles), 
                'message' => 'list of employees retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $employee_profiles = Cache::remember('employee_profiles', $cacheExpiration, function(){
                return EmployeeProfile::all();
            });

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in fetching a '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => EmployeeProfileResource::collection($employee_profiles), 
                'message' => 'list of employees retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(EmployeeProfileRequest $request)
    {
        try{
            $cleanData = [];
            $dateString = $request->date_hired;
            $carbonDate = Carbon::parse($dateString);
            $date_hired_string = $carbonDate->format('Ymd');

            $total_registered_this_day = EmployeeProfile::whereDate('date_hired', $carbonDate)->get();
            $employee_id_random_digit = 50 + count($total_registered_this_day);

            $last_registered_employee = EmployeeProfile::orderBy('biometric_id', 'desc')->first();
            $last_password = DefaultPassword::orderBy('effective_at', 'desc')->first();

            $hashPassword = Hash::make($last_password.env('SALT_VALUE'));
            $encryptedPassword = Crypt::encryptString($hashPassword);

            $now = Carbon::now();
            $fortyDaysFromNow = $now->addDays(40);
            $fortyDaysExpiration = $fortyDaysFromNow->toDateTimeString();

            $new_biometric_id = $last_registered_employee->biometric_id + 1;
            $new_employee_id = $date_hired_string.$employee_id_random_digit;

            $cleanData['employee_id'] = $new_employee_id;
            $cleanData['biomentric_id'] = $new_biometric_id;
            $cleanData['employment_type_id'] = strip_tags($request->employment_type_id);
            $cleanData['personal_information_id'] = strip_tags($request->personal_information_id);
            $cleanData['profile_url'] = $request->attachment === null?null:$this->file_validation_and_upload->check_save_file($request, 'employee/profiles');
            $cleanData['allow_time_adjustment'] = strip_tags($request->allow_time_adjustment) === 1? true: false;
            $cleanData['password_encrypted'] = $encryptedPassword;
            $cleanData['password_created_at'] = now();
            $cleanData['password_expiration_at'] = $fortyDaysExpiration;
            $cleanData['salary_grade_step'] = strip_tags($request->salary_grade_step);
            $cleanData['date_hired'] = $request->date_hired;
            $cleanData['designation_id'] = $request->designation_id;
            $cleanData['effective_at'] = $request->date_hired;

            $plantilla_number_id = $request->plantilla_number_id;
            $sector_key = '';

            switch(strip_tags($request->sector))
            {
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

            if($sector_key === null){
                return response()->json(['message' => 'Invalid sector area.'], Response::HTTP_BAD_REQUEST);
            }

            $cleanData[$sector_key] = strip_tags($request->sector_id);

            if($plantilla_number_id !== null)
            {
                $plantilla_number = PlantillaNumber::find($plantilla_number_id);

                if(!$plantilla_number){
                    return response()->json(['message' => 'No record found for plantilla number '.$plantilla_number_id], Response::HTTP_NOT_FOUND);
                }

                $plantilla = $plantilla_number->plantilla;
                $designation = $plantilla->designation;
                $cleanData['designation_id'] = $designation->id;
                $cleanData['plantilla_number_id'] = $plantilla_number->id;
            }
            
            $employee_profile = EmployeeProfile::create($cleanData);

            $cleanData['employee_profile_id'] = $employee_profile->id;
            AssignArea::create($cleanData);
            
            if($plantilla_number_id !== null)
            {
                $plantilla_number = PlantillaNumber::find($plantilla_number_id);
                $plantilla_number->update(['employee_profile_id' => $employee_profile->id, 'is_vacant' => false, 'assigned_at' => now()]);
            }
            

            $this->requestLogger->registerSystemLogs($request, $employee_profile->id, true, 'Success in creating a '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new EmployeeProfileResource($employee_profile), 
                'message' => 'Newly employee registered.'], 
            Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createEmployeeAccount($id, Request $request)
    {
        try{
            $employee_profile = EmployeeProfile::find($id);

            if(!$employee_profile)
            {
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
            $employee_id = $date_hired_in_string.$last_employee_id_registered_by_date++;

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
            
            $hashPassword = Hash::make($default_password['password'].env('SALT_VALUE'));
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

            $this->requestLogger->registerSystemLogs($request, $employee_profile->id, true, 'Success in creating a '.$this->SINGULAR_MODULE_NAME.' account.');

            return response()->json(['message' => 'Employee account created.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'createEmployeeAccount', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function updateEmployeeToInActiveEmployees($id, Request $request)
    {
        try{
            $user = $request->user;
            $cleanData['password'] = strip_tags($request->input('password'));

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'].env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_UNAUTHORIZED);
            }

            $employee_profile = EmployeeProfile::find($id);

            if(!$employee_profile)
            {
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

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching a '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => $in_active_employee, 
                'message' => 'Employee record transfer to in active employees.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'updateEmployeeToInActiveEmployees', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $employee_profile = EmployeeProfile::find($id);

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching a '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new EmployeeProfileResource($employee_profile), 'message' => 'Employee details found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function findByEmployeeID(Request $request)
    {
        try{
            $employee_id = $request->input('employee_id');
            $employee_profile = EmployeeProfile::where('employee_id', $employee_id)->first();

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in fetching a '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new EmployeeProfileResource($employee_profile), 'message' => 'Employee details found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    
    /**
     * Update needed as this will require approval of HR to update account
     * data to update of employee and attachment getting from Profile Update Request Table
     */
    public function update($id, Request $request)
    {
        try{
            $user = $request->user;
            $cleanData['password'] = strip_tags($request->input('password'));

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'].env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_UNAUTHORIZED);
            }

            $employee_profile = EmployeeProfile::find($id);

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {   
                if($key === 'profile_image' && $value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                if($key === 'profile_image')
                {   continue;   }
                $cleanData[$key] = strip_tags($value);
            }

            $employee_profile->update($cleanData);

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in updating a '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new EmployeeProfileResource($employee_profile) ,'message' => 'Employee details updated.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function updateEmployeeProfile($id, EmployeeProfileRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['password'] = strip_tags($request->input('password'));

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'].env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_UNAUTHORIZED);
            }

            $employee_profile = EmployeeProfile::find($id);

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            $file_value = $this->file_validation_and_upload->check_save_file($request->file('profile_image'), "employee/profiles");

            $employee_profile->update(['profile_url' => $file_value]);

            $this->requestLogger->registerSystemLogs($request, $employee_profile->id, true, 'Success in changing profile picture of an employee profile.');

            return response()->json(['data' => new EmployeeProfileResource($employee_profile),'message' => 'Employee profile picture updated.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    

    public function destroy($id, PasswordApprovalRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['password'] = strip_tags($request->input('password'));

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'].env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_UNAUTHORIZED);
            }

            $employee_profile = EmployeeProfile::findOrFail($id);

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $employee_profile->delete();

            $this->requestLogger->registerSystemLogs($request, $employee_profile->id, true, 'Success in deleting a '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee profile deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
