<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Resources\EmployeeDTRList;
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

    public function __construct(RequestLogger $requestLogger, FileValidationAndUpload $file_validation_and_upload)
    {
        $this->requestLogger = $requestLogger;
        $this->file_validation_and_upload = $file_validation_and_upload;
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

            /**
             * Validate for 2FA
             * if 2FA is activated send OTP to email to validate ownership
             */

            /**
             * If account is login to other device
             * notify user for that and allow user to choose to cancel or proceed to signout account to other device
             * return employee profile id when user choose to proceed signout in other device
             * for server to be able to determine which account it want to sign out.
             * If account is singin with in the same machine like ip and device and platform continue
             * signin without signout to current signined of account.
             * Reuse the created token of first instance of signin to have single access token.
             */

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

            $data = [
                'employee' => new SignInResource($employee_profile),
                'designation'=> $designation['name'],
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
                            $query->with(['module', 'permission']);
                        }
                    ]);
                }
            ])->where('employee_profile_id', $employee_profile['id'])->get();
        
            if(count($special_access_permissions) > 0)
            {
                foreach($special_access_permissions['systemRole'] as $key => $special_access_permission)
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
            'roles' => [$this->buildRoleDetails($system_role)],
        ];
    }
    
    private function buildRoleDetails($system_role) 
    {
        $modules = [];

        $role_module_permissions = $system_role->roleModulePermissions;

        // return $role_module_permissions;
    
        foreach ($role_module_permissions as $role_module_permission) {
            $module_name = $role_module_permission->modulePermission->module->name;
            $permission_action = $role_module_permission->modulePermission->permission->action;
    
            if (!isset($modules[$module_name])) {
                $modules[$module_name] = ['name' => $module_name, 'permissions' => []];
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
    
    //**Require employee id */
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

            $data = [
                'employee_id' => $employee_profile['employee_id'],
                'name' => $name,
                'designation'=> $designation['name'],
                'area_assigned' => $area_assigned['name'],
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

            $data = [
                'employee_id' => $employee_profile['employee_id'],
                'name' => $name,
                'designation'=> $designation['name'],
                'area_assigned' => $area_assigned['name'],
                'area_sector' => $area_assigned['sector'],
                'side_bar_details' => $side_bar_details
            ];

            return response()
                ->json([$data, 'message' => "Success login."], Response::HTTP_OK);
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

            return response()->json(['message' => 'User signout.'], Response::HTTP_OK)->cookie(env('COOKIE_NAME'), '', -1);;
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'signOut', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeesDTRList(Request $request)
    {
        try{
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
            $plantilla_number_id = $request->input('plantilla_number_id');

            if($plantilla_number_id !== null)
            {
                $plantilla_number = PlantillaNumber::find($plantilla_number_id);

                if(!$plantilla_number){
                    return response()->json(['message' => 'No record found for plantilla number '.$plantilla_number_id], Response::HTTP_NOT_FOUND);
                }

                $plantilla = $plantilla_number->plantilla;
                $plantilla_assigned_area = $plantilla_number->assignedArea();

            }

            foreach ($request->all() as $key => $value) {
                if($key === 'profile_url' && $value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                if($key === 'profile_url')
                {
                    $cleanData[$key] = $this->file_validation_and_upload->check_save_file($request, "employee/profiles");
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }
            
            /**
             * Retrieve total registered employee to use for biometric ID  since biometric id is base on employee count.
             */
            $total_employee = EmployeeProfile::all()->count();
            $biomentric_id = $total_employee++;

            $cleanData['biometric_id'] = $biomentric_id;

            $employee_profile = EmployeeProfile::create($cleanData);
            
            $this->requestLogger->registerSystemLogs($request, $employee_profile->id, true, 'Success in creating a '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new EmployeeProfileResource($employee_profile), 'message' => 'Newly employee registered.'], Response::HTTP_OK);
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
