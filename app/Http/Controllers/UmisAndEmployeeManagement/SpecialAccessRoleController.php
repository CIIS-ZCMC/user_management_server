<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Requests\PasswordApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Requests\SpecialAccessRoleRequest;
use App\Http\Resources\SpecialAccessRoleResource;
use App\Models\SpecialAccessRole;
use App\Models\EmployeeProfile;
use App\Models\SystemRole;

class SpecialAccessRoleController extends Controller
{
    private $CONTROLLER_NAME = 'Special Access Role';
    private $PLURAL_MODULE_NAME = 'special access roles';
    private $SINGULAR_MODULE_NAME = 'special access role';

    public function index(Request $request)
    {
        try{
            $special_access_roles = SpecialAccessRole::all();

            return response() -> json([
                'data' => SpecialAccessRoleResource::collection($special_access_roles),
                'message' => 'Special access role list retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(SpecialAccessRoleRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value); 
            }

            $employee_profile = EmployeeProfile::find($cleanData['employee_profile_id']);
            
            if (!$employee_profile) 
            {
                return response()->json(['message' => 'No record found for Employe.'], Response::HTTP_NOT_FOUND);
            }

            $system_role = SystemRole::find($cleanData['system_role_id']);

            if (!$system_role) 
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $check_if_exist =  SpecialAccessRole::where('employee_profile_id', $cleanData['employee_profile_id'])->where('system_role_id', $cleanData['system_role_id'])->first();

            if($check_if_exist !== null){
                return response()->json(['message' => 'System already exist.'], Response::HTTP_FORBIDDEN);
            }

            $special_access_role = SpecialAccessRole::create($cleanData);
            
            Helpers::registerSystemLogs($request, $special_access_role['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response() -> json([
                'data' => new SpecialAccessRoleResource($special_access_role),
                'message' => 'New special role added.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id, Request $request)
    {
        try{
            $special_access_role = SpecialAccessRole::find($id);

            if (!$special_access_role) 
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response() -> json([
                'data' => new SpecialAccessRoleResource($special_access_role), 
                'message' => 'Special access role details found.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id, AuthPinApprovalRequest $request)
    {
        try{
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !==  $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

            $special_access_role = SpecialAccessRole::findOrFail($id);

            if(!$special_access_role)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $special_access_role -> delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');

            return response() -> json(['message' => 'Special access role record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response() -> json(['message' => $th -> getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
