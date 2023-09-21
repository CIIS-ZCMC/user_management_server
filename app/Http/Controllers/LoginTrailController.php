<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Resources\LoginTrailResource;
use App\Models\LoginTrail;

class LoginTrailController extends Controller
{
    public function show($id, Request $request)
    {
        try{    
            $login_trails = LoginTrail::where('employee_profile_id', $id)->get();

            return response()->json(['data' => LoginTrailResource::collection($login_trails)], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->errorLog('show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function infoLog($module, $message)
    {
        Log::channel('custom-info')->info('Login Trail Controller ['.$module.']: message: '.$errorMessage);
    }

    protected function errorLog($module, $errorMessage)
    {
        Log::channel('custom-error')->error('Login Trail Controller ['.$module.']: message: '.$errorMessage);
    }
}
