<?php

namespace App\Http\Controllers\Authentication;

use App\Helpers\AuthHelper;
use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Models\AccessToken;
use App\Models\EmployeeProfile;
use App\Models\SpecialAccessRole;
use App\Models\SystemRole;
use App\Models\SystemUserSessions;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthWithApiKeySessionIDController extends Controller
{
    public function store(Request $request)
    {
        $api = $request->api_key;
        $session_id = $request->query("session_id");
        
        $permissions = null;
        $user_details = null;
        $session = null;
        
        try{
            if(!$session_id){
                return response()->json(['message' => "unauthorized"], Response::HTTP_UNAUTHORIZED);
            }
    
            $system_user_sessions = SystemUserSessions::where("session_id", $session_id)->first();
            
            if(!$system_user_sessions){
                return response()->json(['message' => "unauthorized session id is invalid."], Response::HTTP_UNAUTHORIZED);
            }
    
            $employee_profile = EmployeeProfile::find($system_user_sessions['user_id']);
    
            $assigned_area = $employee_profile->assignedArea;
            $system_role_ids = SystemRole::where('system_id', $api['id'])->pluck('id')->toArray();
            
            $special_access_roles = SpecialAccessRole::whereIn('system_role_id', $system_role_ids)
                ->where('employee_profile_id', $employee_profile->id)->get();
    
            if ($assigned_area['plantilla_id'] === null) {
                $designation = $assigned_area->designation;
            } else {
                //Employment is plantilla retrieve the plantilla and its designation.
                $plantilla = $assigned_area->plantilla;
                $designation = $plantilla->designation;
            }
    
            $permissions = AuthHelper::buildSidebarDetails($employee_profile, $designation, $special_access_roles, $api['id']);
            $user_details = AuthHelper::generateEmployeeProfileDetails($employee_profile);
            $session = AccessToken::where("employee_profile_id", $employee_profile->id)->first();
        }catch(\Throwable $th){
            Helpers::infoLog("SystemController", "authenticateUserFromDifferentSystem", $th->getMessage());
            return response()->json([
                'message' => "Failed to authenticate."
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'user_details' => $user_details,
            'session' => $session,
            'permissions' => $permissions,
            'authorization_pin' => $employee_profile->authorization_pin
        ], 200);
    }
}
