<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\PasswordApprovalRequest;
use App\Helpers\Helpers;
use App\Models\SystemLogs;
use App\Http\Resources\SystemLogsResource;

class SystemLogsController extends Controller
{
    private $CONTROLLER_NAME = 'System Logs';
    private $PLURAL_MODULE_NAME = 'system logs';
    private $SINGULAR_MODULE_NAME = 'system log';
    
    public function index(Request $request)
    {
        try{
            
            $cacheExpiration = Carbon::now()->addDay();

            $system_logs = Cache::remember('system_logs', $cacheExpiration, function(){
                return SystemLogs::all();
            });

            return response()->json([
                'data' => SystemLogsResource::collection($system_logs), 
                'message' => 'System logs retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $system_log = SystemLogs::find($id);

            if(!$system_log)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            return response()->json([
                'data' => new SystemLogsResource($system_log),
                'message' => 'System Log record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    
    public function findByAccessRights(Request $request)
    {
        try{
            $system_logs = SystemLogs::where('action', $request->action)->get();

            if(!$system_logs)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            return response()->json([
                'data' => SystemLogsResource::collection($system_logs),
                'message' => 'System Log record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'findByAccessRights', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    
    public function destroy($id, PasswordApprovalRequest $request)
    {
        try{
            $password = strip_tags($request->input('password'));

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }
            
            $system_log = SystemLogs::findOrFail($id);

            if(!$system_log)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $system_log -> delete();
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'System Log deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
}
