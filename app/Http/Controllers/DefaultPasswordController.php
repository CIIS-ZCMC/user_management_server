<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Services\RequestLogger;
use App\Http\Resources\DefaultPasswordResource;
use App\Models\DefaultPassword;
use App\Models\SystemLogs;

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
            $cacheExpiration = Carbon::now()->addDay();

            $defaultPasswords = DefaultPassword::all();

            $this->registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json(['data' => DefaultPasswordResource::collection($defaultPasswords)], Response::HTTP_OK);
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
                $cleanData[$key] = strip_tags($value);
            }

            $defaultPassword = DefaultPassword::create($cleanData);

            $this->registerSystemLogs($request, $defaultPassword['id'], true, 'Success creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{  
            $defaultPassword = DefaultPassword::find($id);

            if(!$defaultPassword)
            {
                $this->registerSystemLogs($request, $id, false, 'Failed to find a '.$this->SINGULAR_MODULE_NAME.'.');
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => new DefaultPasswordResource($defaultPassword)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, Request $request)
    {
        try{ 
            $defaultPassword = DefaultPassword::find($id)->first();

            if(!$defaultPassword)
            {
                $this->registerSystemLogs($request, $id, false, 'Failed to find a '.$this->SINGULAR_MODULE_NAME.'.');
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $defaultPassword -> update($cleanData);

            $this->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $defaultPassword = DefaultPassword::findOrFails($id)->first();

            if(!$defaultPassword)
            {
                $this->registerSystemLogs($request, $id, false, 'Failed to find a '.$this->SINGULAR_MODULE_NAME.'.');
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $defaultPassword -> delete();

            $this->registerSystemLogs($request, $id, true, 'Success in deleting a '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
