<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\Helpers;
use App\Http\Resources\LoginTrailResource;
use App\Models\LoginTrail;
use App\Models\EmployeeProfile;

class LoginTrailController extends Controller
{
    private $CONTROLLER_NAME = 'Login Trail';
    private $PLURAL_MODULE_NAME = 'login trails';

    public function show(Request $request)
    {
        try{    
            $employee_profile = $request->user;

            $login_trails = LoginTrail::where('employee_profile_id', $employee_profile['id'])->get();

            return response()->json([
                'data' => LoginTrailResource::collection($login_trails), 
                'message' => 'Employee login trail retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByEmployeeID($id, Request $request)
    {
        try{
            $employee_profile = EmployeeProfile::where('employee_id', $id)->first();

            if(!$employee_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $login_trails = LoginTrail::where('employee_profile_id', $employee_profile['id'])->get();

            return response()->json(['data' => LoginTrailResource::collection($login_trails), 'message' => 'Employee login trail retrieved.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
