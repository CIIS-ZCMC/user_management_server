<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Services\RequestLogger;
use App\Http\Requests\ProfileUpdateRequestRequest;
use App\Http\Resources\ProfileUpdateRequestResource;
use App\Models\ProfileUpdateRequest;
use App\Models\RequestDetail;
use App\Models\EmployeeProfile;
use App\Models\SystemLogs;

class ProfileUpdateController extends Controller
{
    private $CONTROLLER_NAME = 'Profile Update Request';
    private $PLURAL_MODULE_NAME = 'profile update requests';
    private $SINGULAR_MODULE_NAME = 'profile update request';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function findByPersonalInformationID($id, Request $request)
    {
        try{
            $profile_update_requests = ProfileUpdateRequest::where('personal_information_id', $id)->get();

            if(!$profile_update_request)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $profile_update_requests['id'], true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json(['data' => ProfileUpdateRequestResource::collection($profile_update_requests), 'message' => 'Employee profile update request record found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'findByPersonalInformationID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByEmployeeID(Request $request)
    {
        try{
            $employee_profile = EmployeeProfile::where('employee_id',$request->input('employee_id'))->get();

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            $personal_information = $employee_profile->personalInformation;
            $profile_update_requests = $personal_information->profile_update_requests;

            $this->registerSystemLogs($request, $profile_update_requests['id'], true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json(['data' => ProfileUpdateRequestResource::collection($profile_update_requests), 'message' => 'Employee profile update request record found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(ProfileUpdateRequestRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if ($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $profile_update_request = ProfileUpdateRequest::create($cleanData);

            $request_details = $request->input('details');

            foreach($request_details as $key => $request_detail)
            {
                $cleanData = [];

                $cleanData['attachment_url'] = $this->check_save_file($request_detail['attachment']);
                $cleanData['target_data'] = $request_detail['key'];
                $cleanData['new_data'] = $request_detail['value'];

                RequestDetail::create($cleanData);
             }

            $this->registerSystemLogs($request, $profile_update_request['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new ProfileUpdateRequest($profile_update_request) ,'message' => 'New employee profile update request registered.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $profile_update_request = ProfileUpdateRequest::findOrFail($id);

            if(!$profile_update_request)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new ProfileUpdateRequest($profile_update_request), 'message' => 'Profile update request record found.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, ProfileUpdateRequestRequest $request)
    {
        try{
            $profile_update_request = ProfileUpdateRequest::find($id);

            if(!$profile_update_request)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if ($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $profile_update_request->update($cleanData);

            $request_details = $request->input('details');

            foreach($request_details as $key => $request_detail)
            {
                $cleanData = [];

                $cleanData['attachment_url'] = $this->check_save_file($request_detail['attachment']);
                $cleanData['target_data'] = $request_detail['key'];
                $cleanData['new_data'] = $request_detail['value'];

                RequestDetail::create($cleanData);
             }
             
            $this->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new ProfileUpdateRequestResource($profile_update_request), 'message' => 'Employee profile update request data is updated.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $profile_update_request = ProfileUpdateRequest::findOrFail($id);

            if(!$profile_update_request)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $profile_update_request->delete();
            
            $this->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee profile update request record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByPersonalInformationID($id, Request $request)
    {
        try{
            $profile_update_requests = ProfileUpdateRequest::where('personal_information_id', $id)->get();

            if(!$profile_update_requests)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach($profile_update_request as $key => $profile_update_request){
                $profile_update_request->delete();
            }
            
            $this->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee profile update request record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyByEmployeeID(Request $request)
    {
        try{
            $employee_profile = EmployeeProfile::where('employee_id',$request->input('employee_id'))->get();

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $personal_information = $employee_profile->personalInformation;
            $profile_update_requests = $personal_information->profile_update_requests;

            foreach($profile_update_request as $key => $profile_update_request){
                $profile_update_request->delete();
            }
            
            $this->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Employee profile update request record deleted'], Response::HTTP_OK);
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
            'ip_profile_update_request' => $ip
        ]);
    }
}
