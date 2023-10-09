<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;   
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\RequestLogger;
use App\Http\Requests\SignInRequest;
use App\Http\Requests\EmployeeProfileRequest;
use App\Http\Resources\EmployeeProfileResource;
use App\Http\Resources\SignInResource;
use App\Models\DefaultPassword;
use App\Models\EmployeeProfile;
use App\Models\LoginTrail;
use App\Models\SystemLogs;

class EmployeeProfileController extends Controller
{
    private $CONTROLLER_NAME = 'Employee Profile';
    private $PLURAL_MODULE_NAME = 'employee profiles';
    private $SINGULAR_MODULE_NAME = 'employee profile';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function signIn(SignInRequest $request)
    {
        try {
            
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($key === 'public_key'){
                    break;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $user = EmployeeProfile::where('employee_id', $cleanData['employee_id'])->first();

            if (!$user) {
                return response()->json(['message' => "Employee id or password incorrect."], Response::HTTP_UNAUTHORIZED);
            }            

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'].env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Employee id or password incorrect."], Response::HTTP_UNAUTHORIZED);
            }
            
            if(!$user->isDeactivated()){
                return response()->json(['message' => "Account is deactivated."], Response::HTTP_FORBIDDEN);
            }

            if (!$user->isAprroved()) {
                return response()->json(['message' => "Your account is not approved yet."], Response::HTTP_UNAUTHORIZED);
            }

            $token = $user->createToken();

            $personal_information = $user->personalInformation;

            $name = $personal_information->employeeName();
            $position = $user->position->name;
            $department = $user->department->name;

            $dataToEncrypt = [
                'name' => $name,
                'department' => $department,
                'position' => $position
            ];

            LoginTrail::create([
                'signin_datetime' => now(),
                'ip_address' => $request->ip(),
                'employee_profile_id' => $user['id']
            ]);

            return response()
                ->json(['data' =>  $dataToEncrypt], Response::HTTP_OK)
                ->cookie(env('COOKIE_NAME'), json_encode(['token' => $token]), 60, '/', env('SESSION_DOMAIN'), true);
        } catch (\Throwable $th) {
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'authenticate', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function isAuthenticated(Request $request)
    {
        try{
            $user = $request->user;

            $personal_information = $user->personalInformation;

            $name = $personal_information->employeeName();
            $position = $user->position->name;
            $department = $user->department->name;

            $dataToEncrypt = [
                'name' => $name,
                'department' => $department,
                'position' => $position
            ];

            return response()->json(['data' => $dataToEncrypt], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'isAuthenticated', $th->getMessage());
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

            return response()->json(['data' => '/'], Response::HTTP_OK)->cookie(env('COOKIE_NAME'), '', -1);;
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'logout', $th->getMessage());
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

            $this->registerSystemLogs($request, null, true, 'Success in fetching a '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json(['data' => EmployeeProfileResource::collection($employee_profiles)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(Request $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($key === 'profile_image' && $value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                if($key === 'profile_image')
                {
                    $cleanData[$key] = $this->check_save_file($request);
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            $employee_profile = EmployeeProfile::create($cleanData);
            
            $this->registerSystemLogs($request, $employee_profile->id, true, 'Success in creating a '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => $employee_profile], Response::HTTP_OK);
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
                $this->registerSystemLogs($request, $id, false, 'Failed to find an employee profile.');
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            $defaultPassword = DefaultPassword::find('status', TRUE)->first();
            $hashPassword = Hash::make($defaultPassword['password'].env('SALT_VALUE'));
            $encryptedPassword = Crypt::encryptString($hashPassword);

            $employee_profile -> employee_id = $request->employee_id;
            $employee_profile -> password_encrypted = $encryptedPassword;
            $employee_profile -> save();

            $this->registerSystemLogs($request, $employee_profile->id, true, 'Success in creating a '.$this->SINGULAR_MODULE_NAME.' account.');

            return response()->json(['data' => 'Account created successfully.'], Response::HTTP_OK);
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
                $this->registerSystemLogs($request, $id, false, 'Failed to find an employee profile.');
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id, true, 'Success in fetching a '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new EmployeeProfileResource($employee_profile)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, Request $request)
    {
        try{
            $employee_profile = EmployeeProfile::find($id);

            if(!$employee_profile)
            {
                $this->registerSystemLogs($request, $id, false, 'Failed to find an employee profile.');
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

            $this->registerSystemLogs($request, $id, true, 'Success in updating a '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function updateEmployeeProfile($id, EmployeeProfileRequest $request)
    {
        try{
            $employee_profile = EmployeeProfile::find($id);

            $file_value = $this->check_save_file($request->file('profile_image'));

            $employee_profile->update($file_value);

            $this->registerSystemLogs($request, $employee_profile->id, true, 'Success in changing profile picture of an employee profile.');

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    

    public function destroy($id, Request $request)
    {
        try{
            $employee_profile = EmployeeProfile::findOrFail($id);

            if(!$employee_profile)
            {
                $this->registerSystemLogs($request, $id, false, 'Failed to find a '.$this->SINGULAR_MODULE_NAME.'.');
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $employee_profile->delete();

            $this->registerSystemLogs($request, $employee_profile->id, true, 'Success in deleting a '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function check_save_file($request)
    {
        $FILE_URL = 'employee/profiles';
        $fileName = '';

        if ($request->file('profile_image')->isValid()) {
            $file = $request->file('profile_image');
            $filePath = $file->getRealPath();

            $finfo = new \finfo(FILEINFO_MIME);
            $mime = $finfo->file($filePath);
            
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];

            if (!in_array($mime, $allowedMimeTypes)) {
                return response()->json(['message' => 'Invalid file type'], 400);
            }

            // Check for potential malicious content
            $fileContent = file_get_contents($filePath);

            if (preg_match('/<\s*script|eval|javascript|vbscript|onload|onerror/i', $fileContent)) {
                return response()->json(['message' => 'File contains potential malicious content'], 400);
            }

            $file = $request->file('profile_image');
            $fileName = Hash::make(time()) . '.' . $file->getClientOriginalExtension();

            $file->move(public_path($FILE_URL), $fileName);
        }
        
        return $fileName;
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
            'ip_address' => $ip
        ]);
    }
}
