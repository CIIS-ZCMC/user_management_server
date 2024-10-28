<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use App\Helpers\Helpers;
use App\Http\Requests\SystemRequest;
use App\Http\Resources\SystemResource;
use App\Models\System;

class SystemController extends Controller
{   
    private $CONTROLLER_NAME = 'System';
    private $PLURAL_MODULE_NAME = 'systems';
    private $SINGULAR_MODULE_NAME = 'system';
    
    public function authenticateUserFromDifferentSystem(Request $request)
    {
        $unique_id = $request->unique_id;
        
        if(!$unique_id){
            return response()->json(['message' => "unauthorized"], Response::HTTP_UNAUTHORIZED);
        }
    }

    // In case the env client domain doesn't work
    public function updateUMISDATA(){
        $system = System::find(1)->update([
            'domain' => Crypt::encryptString("http://localhost:5173")
        ]);
        

        // System::find(4)->update(['api_key' => Str::random(60)]);
        return response()->json(['message' => "Success"], 200);
    }

    public function index(Request $request)
    {
        try{
            $systems = System::all();
            
            return response() -> json([
                'data' => SystemResource::collection($systems),
                'message' => 'System list retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(SystemRequest $request)
    {
        try{
            $cleanData = [];

            $domain = Crypt::encrypt($request->domain);

            foreach ($request->all() as $key => $value) {
                if($key === 'domain')
                {
                    $cleanData[$key] = $domain;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $check_if_exist =  System::where('name', $cleanData['name'])->where('code', $cleanData['code'])->first();

            if($check_if_exist !== null){
                return response()->json(['message' => 'System already exist.'], Response::HTTP_FORBIDDEN);
            }

            $cleanData['api_key'] = Str::random(60);
            $system = System::create($cleanData);
            
            $log = Helpers::registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response() -> json([
                'data' => new SystemResource($system),
                'message' => 'System created successfully.',
                'logs' => $log
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * GENERATE API KEY
     * this end point expect for an System ID
     * The ID will be validated if it is has a record in the system record
     * if TRUE then the system will generate a API Key that will be encrypted before storing in the System Details Record.
     */
    public function generateAPIKey($id, Request $request)
    {
        try{
            $system = System::find($id);

            if(!$system){
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $apiKey = base64_encode(random_bytes(32));

            $encrypted_api_key = Crypt::encrypt($apiKey);

            $system -> api_key = $encrypted_api_key;
            $system -> updated_at = now();
            $system -> save();

            Helpers::registerSystemLogs($request, $id, true, 'Success in generating API Key '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json([
                'data' => new SystemResource($system),
                'message' => 'System record updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'generateKey', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * This End Point expect an System ID and The status value in which expect as integer between 0 - 2
     * Validating the status value will trigger first then
     * finding a system record according to the ID given and if found
     * the system status will be updated.
     */
    public function updateSystemStatus($id, Request $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $status = $request->input('status');

            if(!is_int($status) || $status < 0 || $status > 2)
            {
                return response()->json(['message' => 'Invalid Data.'], Response::HTTP_BAD_REQUEST);
            }

            $system = System::find($id);

            if(!$system){
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $system->update(['status' => $status]);

            Helpers::registerSystemLogs($request, $id, true, 'Success in Updating System Status'.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json([
                'data' => new SystemResource($system),
                'message' => 'System updated successfully.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'activateSystem', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id, Request $request)
    {
        try{
            $system = System::find($id);

            if(!$system){
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            return response() -> json([
                'data' => new SystemResource($system),
                'message' => 'System record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id, Request $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $system = System::find($id);
            
            if (!$system) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($key === 'domain')
                {
                    $cleanData[$key] = Crypt::encrypt($value);
                    continue;
                }
                $cleanData[$key] = strip_tags($value); 
            }

            $system -> update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json([
                'data' => new SystemResource($system),
                "message" => 'System record updated.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id, Request $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $system = System::findOrFail($id);

            if(!$system){
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $system_role = $system->systemRoles;

            if(count($system_role) > 0){
                return response()->json(['message' => "Record is being used by other data."], Response::HTTP_FORBIDDEN);
            }

            $system -> delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json(['message' => 'System deleted successfully.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
