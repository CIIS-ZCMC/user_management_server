<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\RequestLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
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
            $cacheExpiration = Carbon::now()->addDay();

            $systems = Cache::remember('systems', $cacheExpiration, function(){
                return System::all();
            });

            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');
            
            return response() -> json(['data' => SystemResource::collection($systems)], Response::HTTP_OK);
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

            $data = System::create($cleanData);

            $this->registerSystemLogs($request, $id, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
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
            
            return response() -> json(['data' => new SystemResource($system)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function update($id, SystemRequest $request)
    {
        try{
            $data = System::find($id);
            
            if (!$data) {
                return response()->json(['message' => 'No record found.'], 404);
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

            $data -> update($cleanData);

            $this->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json(['data' => "Success"], Response::HTTP_OK);
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
            
            return response() -> json(['data' => 'Success'], Response::HTTP_OK);
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
