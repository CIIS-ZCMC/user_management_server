<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\PasswordApprovalRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Requests\DesignationRequest;
use App\Http\Resources\DesignationResource;
use App\Http\Resources\DesignationWithSystemRoleResource;
use App\Http\Resources\DesignationTotalEmployeeResource;
use App\Http\Resources\DesignationTotalPlantillaResource;
use App\Http\Resources\DesignationEmployeesResource;
use App\Models\Designation;
use App\Models\PositionSystemRole;

class DesignationController extends Controller
{
    private $CONTROLLER_NAME = 'Designation';
    private $PLURAL_MODULE_NAME = 'designations';
    private $SINGULAR_MODULE_NAME = 'designation';


    public function test(Request $request)
    { 
       $name = Helpers::checkSaveFile($request->attachment, 'test/profiles');    
       return response()->json(['data' => $name], Response::HTTP_OK);
    }

    public function index(Request $request)
    {
        try{
            $cacheExpiration = Carbon::now()->addDay();

            $designations = Cache::remember('designations', $cacheExpiration, function(){
                return Designation::all();
            });

            return response()->json([
                'data' => DesignationResource::collection($designations),
                'message' => 'Designation records retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function totalEmployeePerDesignation(Request $request)
    {
        try{
            $total_employee_per_designation = Designation::withCount('assignAreas')->get();

            return response()->json([
                'data' => DesignationTotalEmployeeResource::collection($total_employee_per_designation),
                'message' => 'Total employee per disignation retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'totalEmployeePerDesignation', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function totalPlantillaPerDesignation(Request $request)
    {
        try{
            $total_plantilla_per_designation = Designation::withCount('plantilla')->get();

            return response()->json([
                'data' => DesignationTotalPlantillaResource::collection($total_plantilla_per_designation),
                'message' => 'Total plantilla per designation retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'totalEmployeePerDesignation', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
 
    public function employeeListInDesignation($id, Request $request)
    {
        try{
            $employee_with_designation = Designation::with('assignAreas.employeeProfile')->findOrFail($id);

            return response()->json([
                'data' => new DesignationEmployeesResource($employee_with_designation),
                'message' => 'Designation employee list retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'employeesOfSector', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(DesignationRequest $request)
    {
        try{
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $designation = Designation::create($cleanData);

            Helpers::registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new DesignationResource($designation),
                'message' => 'New designation added.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function assignSystemRole(Request $request)
    {
        try{
            $failed = [];
            $designations = [];

            foreach($request->designations as $id){
                $designation_id = strip_tags($id);
                $designation = Designation::find($designation_id);
                
                if(!$designation)
                {
                    $failed[] = $id;
                    continue;
                }
                
                foreach($request->system_roles as $system_role){
                    $system_role_id = strip_tags($system_role);

                    PositionSystemRole::create([
                        'system_role_id' => $system_role_id,
                        'designation_id' => $designation->id
                    ]);
                }
                
                $designations[] = $designation;
            }

         
            if(count($failed) > 0){
                return response()->json([
                    'data' => DesignationWithSystemRoleResource::collection($designations),
                    'message' => "Some designation failed to assign system role."
                ], Response::HTTP_OK);
            }

            Helpers::registerSystemLogs($request, null, true, 'Success in assigned system role to designation '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => DesignationWithSystemRoleResource::collection($designations),
                'message' => 'System role successfully assign to designation.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME, 'assignSystemRole', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $designation = Designation::find($id);

            if(!$designation)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => new DesignationResource($designation),
                'message' => 'Designation record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, DesignationRequest $request)
    {
        try{
            $designation = Designation::find($id);

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                $cleanData[$key] = strip_tags($value);
            }

            $designation -> update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new DesignationResource($designation), 
                'message' => 'Designation details updated.'], 
                Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
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

            $designation = Designation::findOrFail($id);

            if(!$designation)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            if(count($designation->plantila??[]) > 0 || count($designation->positionSystemRoles??[]) > 0)
            {
                return response()->json(['message' => 'Some data is using this designation record deletion is prohibited.'], Response::HTTP_BAD_REQUEST);
            }

            $designation -> delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Designation record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
