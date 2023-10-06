<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\DefaultPasswordResource;
use App\Models\DefaultPassword;

class DefaultPasswordController extends Controller
{
    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $defaultPasswords = DefaultPassword::all();

            $this->registerSystemLogs($request, null, true, 'Success in fetching all default password records.');

            return response()->json(['data' => DefaultPasswordResource::collection($defaultPasswords)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(Request $request)
    {
        try{ 
            $cleanData = [];

            $cleanData['uuid'] = Str::uuid();
            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $defaultPassword = DefaultPassword::create($cleanData);

            $this->registerSystemLogs($request, $defaultPassword->id, true, 'Success creating new record of default password.');

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{  
            $defaultPassword = DefaultPassword::find($id);

            if(!$defaultPassword)
            {
                $this->registerSystemLogs($request, $id, false, 'Failed to find an default password record.');
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $this->registerSystemLogs($request, null, true, 'Success in fetching a default password record.');

            return response()->json(['data' => new DefaultPasswordResource($defaultPassword)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, Request $request)
    {
        try{ 
            $defaultPassword = DefaultPassword::find($id)->first();

            if(!$defaultPassword)
            {
                $this->registerSystemLogs($request, $id, false, 'Failed to find an default password record.');
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $defaultPassword -> update($cleanData);

            $this->registerSystemLogs($request, $defaultPassword->id, true, 'Success in updating a default password.');

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $defaultPassword = DefaultPassword::findOrFails($id)->first();

            if(!$defaultPassword)
            {
                $this->registerSystemLogs($request, $id, false, 'Failed to find an default password record.');
                return response()->json(['message' => "No record found"], Response::HTTP_NOT_FOUND);
            }

            $defaultPassword -> delete();

            $this->registerSystemLogs($request, $defaultPassword->id, true, 'Success in deleting a default password record.');

            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function infoLog($module, $message)
    {
        Log::channel('custom-info')->info('Default Password Controller ['.$module.']: message: '.$errorMessage);
    }

    protected function errorLog($module, $errorMessage)
    {
        Log::channel('custom-error')->error('Default Password Controller ['.$module.']: message: '.$errorMessage);
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
