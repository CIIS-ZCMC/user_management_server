<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Services\RequestLogger;
use App\Http\Resources\LoginTrailResource;
use App\Models\LoginTrail;
use App\Models\SystemLogs;

class LoginTrailController extends Controller
{
    private $CONTROLLER_NAME = 'Login Trail';
    private $PLURAL_MODULE_NAME = 'login trails';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function show($id, Request $request)
    {
        try{    
            $login_trails = LoginTrail::where('employee_profile_id', $id)->get();
            
            $this->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json(['data' => LoginTrailResource::collection($login_trails)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
