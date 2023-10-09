<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\RequestLogger;
use App\Http\Requests\IdentificationNumberRequest;
use App\Http\Resources\IdentificationNumberResource;
use App\Models\IdentificationNumber;
use App\Models\SystemLogs;

class IdentificationNumberController extends Controller
{
    private $CONTROLLER_NAME = 'Identification Number';
    private $PLURAL_MODULE_NAME = 'divisions';
    private $SINGULAR_MODULE_NAME = 'division';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }
    
    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $identifations = Cache::remember('identifications', $cacheExpiration, function(){
                return IdentificationNumber::all();
            });

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json(['data' => IdentificationNumberResource::collection($identifations)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function identificationEmployee($id, Request $request)
    {
        try{
            $identification = IdentificationNumber::where('personal_information_id',$id)->first();

            if(!$identification)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id, true, 'Success in fetching employee '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new IdentificationNumberResource($identification)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(IdentificationNumberRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null || $key === 'personal_information_id'){ 
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] =  $this->encryptData(strip_tags($value));
            }

            $identification = IdentificationNumber::create($cleanData);

            $this->registerSystemLogs($request, $id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $identification = IdentificationNumber::find($id);

            if(!$identification)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new IdentificationNumberResource($identification)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, IdentificationNumberRequest $request)
    {
        try{
            $identification = IdentificationNumber::find($id);

            $cleanData = [];
            
            foreach ($request->all() as $key => $value) {
                if($value === null || $key === 'personal_information_id'){ 
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] =  $this->encryptData(strip_tags($value));
            }

            $identification -> update($cleanData);

            $this->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    
    public function updateEmployee($id, IdentificationNumberRequest $request)
    {
        try{
            $identification = IdentificationNumber::where('personal_information_id',$id)->first();

            $cleanData = [];
            
            foreach ($request->all() as $key => $value) {
                if($value === null || $key === 'personal_information_id'){ 
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] =  $this->encryptData(strip_tags($value));
            }

            $identification -> update($cleanData);

            $this->registerSystemLogs($request, $id, true, 'Success in updating employee '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $identification = IdentificationNumber::findOrFail($id);

            if(!$identification)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $identification -> delete();
            
            $this->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyEmployee($id, Request $request)
    {
        try{
            $identification = IdentificationNumber::where('personal_information_id', $id)->first();

            if(!$identification)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $identification -> delete();

            $this->registerSystemLogs($request, $id, true, 'Success in deleting employee '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function encryptData($dataToEncrypt)
    {
        return openssl_encrypt($dataToEncrypt, env("ENCRYPT_DECRYPT_ALGORITHM"), env("DATA_KEY_ENCRYPTION"), 0, substr(md5(env("DATA_KEY_ENCRYPTION")), 0, 16));
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
