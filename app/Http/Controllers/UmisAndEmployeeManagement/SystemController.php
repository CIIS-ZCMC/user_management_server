<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\RequestLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use App\Http\Requests\SystemRequest;
use App\Http\Resources\SystemResource;
use App\Models\System;
use App\Models\SystemLogs;

class SystemController extends Controller
{   
    private $CONTROLLER_NAME = 'System';
    private $PLURAL_MODULE_NAME = 'systems';
    private $SINGULAR_MODULE_NAME = 'system';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }
    
    public function index(Request $request)
    {
        try{
            $systems = System::all();

            $this->registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');
            
            return response() -> json([
                'data' => SystemResource::collection($systems),
                'message' => 'System list retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(SystemRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($key === 'domain')
                {
                    $cleanData[$key] = Crypt::encrypt($value);
                    continue;
                }
                $cleanData[$key] = strip_tags($value); 
            }

            $system = System::create($cleanData);

            $this->registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json([
                'data' => new SystemResource($system),
                'message' => 'New system record added.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
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

            $this->registerSystemLogs($request, $id, true, 'Success in generating API Key '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json([
                'data' => new SystemResource($system),
                'message' => 'System record updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'generateKey', $th->getMessage());
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
            $status = $request->input('status');

            if(!is_int($status) || $status < 0 || $status > 2)
            {
                return response()->json(['message' => 'Invalid Data.'], Response::HTTP_INVALID_REQUEST);
            }

            $system = System::find($id);

            if(!$system){
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $system->update([
                'status' => $status,
                'updated_at' => now()
            ]);

            $this->registerSystemLogs($request, $id, true, 'Success in Updating System Status'.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json([
                'data' => new SystemResource($system),
                'message' => 'System record updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'activateSystem', $th->getMessage());
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

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json([
                'data' => new SystemResource($system),
                'message' => 'System record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id, SystemRequest $request)
    {
        try{
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

            $this->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json([
                'data' => new SystemResource($system),
                "message" => 'System record updated.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id, Request $request)
    {
        try{
            $system = System::findOrFail($id);

            if(!$system){
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $system -> delete();

            $this->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json(['message' => 'System record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function registerSystemLogs($request, $moduleID, $status, $remarks)
    {
        $ip = $request->ip();
        $user = $request->user;
        $permission = $request->permission;
        list($module, $action) = explode(' ', $permission);

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
