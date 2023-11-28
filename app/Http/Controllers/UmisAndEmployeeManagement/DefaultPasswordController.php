<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\RequestLogger;
use App\Models\DefaultPassword;
use App\Http\Resources\DefaultPasswordResource;
use App\Http\Requests\DefaultPasswordRequest;

class DefaultPasswordController extends Controller
{
    private $CONTROLLER_NAME = 'Default Password';
    private $PLURAL_MODULE_NAME = 'default password records';
    private $SINGULAR_MODULE_NAME = 'default password record';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }
    
    public function index(Request $request)
    {
        try{
            $default_passwords = DefaultPassword::all();

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => DefaultPasswordResource::collection($default_passwords),
                'message' => 'Default password records retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(DefaultPasswordRequest $request)
    {
        try{ 
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $default_password = DefaultPassword::create($cleanData);

            $this->requestLogger->registerSystemLogs($request, $default_password['id'], true, 'Success creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new DefaultPasswordResource($default_password),
                'message' => 'New default password added.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $default_password = DefaultPassword::find($id);

            if(!$default_password)
            {
                $this->requestLogger->registerSystemLogs($request, $id, false, 'Failed to find a '.$this->SINGULAR_MODULE_NAME.'.');
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new DefaultPasswordResource($default_password),
                'message' => 'Default password record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, DefaultPasswordRequest $request)
    {
        try{ 
            $default_password = DefaultPassword::find($id);

            if(!$default_password)
            {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($value === null){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $default_password->update($cleanData);

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new DefaultPasswordResource($default_password),
                'message' => 'Default password record updated.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $default_password = DefaultPassword::findOrFail($id);

            if(!$default_password)
            {
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $default_password -> delete();

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting a '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['message' => 'Default password record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
