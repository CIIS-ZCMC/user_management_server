<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use App\Services\RequestLogger;
use App\Models\SystemLogs;
use App\Http\Resources\SystemLogsResource;

class SystemLogsController extends Controller
{
    private $CONTROLLER_NAME = 'System Logs';
    private $PLURAL_MODULE_NAME = 'system logs';
    private $SINGULAR_MODULE_NAME = 'system log';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }
    
    public function index(Request $request)
    {
        try{
            $stations = SystemLogs::all();

            return response()->json([
                'data' => SystemLogsResource::collection($stations), 
                'message' => 'System logs retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $station = SystemLogs::find($id);

            if(!$station)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json([
                'data' => new SystemLogsResource($station),
                'message' => 'System Log record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    
    public function destroy($id, Request $request)
    {
        try{
            $user = $request->user;
            $cleanData['password'] = strip_tags($request->input('password'));

            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);

            if (!Hash::check($cleanData['password'].env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_UNAUTHORIZED);
            }
            
            $station = SystemLogs::findOrFail($id);

            if(!$station)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $station -> delete();
            
            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'System Log deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
}
