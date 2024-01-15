<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\PasswordApprovalRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Helpers\Helpers;
use App\Http\Requests\PositionSystemRoleRequest;
use App\Http\Resources\PositionSystemRoleResource;
use App\Models\PositionSystemRole;
use App\Models\Designation;

class PositionSystemRoleController extends Controller
{
    private $CONTROLLER_NAME = 'Position System Role';
    private $PLURAL_MODULE_NAME = 'position system roles';
    private $SINGULAR_MODULE_NAME = 'position system role';

    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $position_system_roles = Cache::remember('position_system_roles', $cacheExpiration, function(){
                return PositionSystemRole::all();
            });

            return response()->json(['data' => PositionSystemRoleResource::collection($position_system_roles)], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Validate if Designation Exist
     * Identify its Designation System Role
     * Identify list of system it has access
     * Identify rights for every system it has access
     * This is not intended for client sidebar
     */
    public function findDesignationAccessRights($id, Request $request)
    {
        try{
            $designation = Designation::find($id);

            if(!$designation)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $position_system_role = $designation->positionSystemRoles;

            return response()->json(['data' => PositionSystemRoleResource::collection($position_system_role)], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'designationAccessRights', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(PositionSystemRoleRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $position_system_role = PositionSystemRole::create($cleanData);
            
            Helpers::registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['data' => new PositionSystemRoleResource($position_system_role),'message' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $position_system_role = PositionSystemRole::find($id);

            if(!$position_system_role)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            return response()->json(['data' => new PositionSystemRoleResource($position_system_role)], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, PositionSystemRoleRequest $request)
    {
        try{
            $position_system_role = PositionSystemRole::find($id);

            if(!$position_system_role)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $position_system_role -> update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['data' => new PositionSystemRoleResource($position_system_role),'message' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroy($id, PasswordApprovalRequest $request)
    {
        try{
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $position_system_role = PositionSystemRole::findOrFail($id);

            if(!$position_system_role)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $position_system_role -> delete();
            
            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['data' => 'Success'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
