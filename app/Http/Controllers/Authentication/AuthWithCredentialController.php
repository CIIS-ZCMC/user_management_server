<?php

namespace App\Http\Controllers\Authentication;

use App\Helpers\AuthHelper;
use App\Helpers\Helpers;
use App\Http\Controllers\Controller;

use App\Models\AccessToken;
use App\Models\EmployeeProfile;
use App\Models\FailedLoginTrail;
use App\Models\SpecialAccessRole;
use App\Models\SystemRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthWithCredentialController extends Controller
{
    public function store(Request $request)
    {
        $permissions = null;
        $user_details = null;
        $session = null;

        $api = $request->api_key;
        $employee_id = $request->employee_id;
        $password = $request->password;

        $employee_profile = EmployeeProfile::where('employee_id', $employee_id)->first();

        $decryptedPassword = Crypt::decryptString($employee_profile['password_encrypted']);

        if (!Hash::check($password . config("app.salt_value"), $decryptedPassword)) {
            FailedLoginTrail::create(['employee_id' => $employee_profile->employee_id, 'employee_profile_id' => $employee_profile->id, 'message' => "[Third party signIn]: employee id or password incorrect."]);
            return response()->json(['message' => "Employee id or password incorrect."], Response::HTTP_FORBIDDEN);
        }
        
        try{
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
