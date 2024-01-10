<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\PasswordApprovalRequest;
use App\Models\PlantillaNumber;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Services\RequestLogger;
use App\Http\Requests\AssignAreaRequest;
use App\Http\Resources\AssignAreaResource;
use App\Models\AssignArea;
use App\Models\EmployeeProfile;

class AssignAreaController extends Controller
{ 
    private $CONTROLLER_NAME = 'AssignedArea Module';
    private $PLURAL_MODULE_NAME = 'assigned_area modules';
    private $SINGULAR_MODULE_NAME = 'assigned_area module';

    protected $requestLogger;

    public function __construct(RequestLogger $requestLogger)
    {
        $this->requestLogger = $requestLogger;
    }

    public function index(Request $request)
    {
        try{
            $assigned_areas = AssignArea::all();

            $this->requestLogger->registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');

            return response()->json([
                'data' => AssignAreaResource::collection($assigned_areas), 
                'message' => 'Record of employee assigned area retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByEmployeeID($id, Request $request)
    {
        try{
            $employe_profile = EmployeeProfile::where('employee_id', $id)->first();

            if(!$employe_profile)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }


            $assigned_area = AssignArea::where('employee_profile_id',$employe_profile['id'])->first();

            if(!$assigned_area)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new AssignAreaResource($assigned_area), 
                'message' => 'Employee assigned area found.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'findByEmployeeID', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(AssignAreaRequest $request)
    {
        try{
            $cleanData = [];

            $employee_profile_id = strip_tags($request->employee_profile_id);

            $employee = EmployeeProfile::find($employee_profile_id);

            if(!$employee){
                return response()->json(['message' => "No record found for employee profile."], Response::HTTP_NOT_FOUND);
            }

            $plantilla_number = strip_tags($request->plantilla_number);
            $plantilla_number = PlantillaNumber::where('number', $plantilla_number)->first();

            if(!$plantilla_number){
                return response()->json(['message' => "Plantilla number ". $request->plantilla_number." doesn't exist"], Response::HTTP_BAD_REQUEST);
            }

            $cleanData['plantilla_id'] = $plantilla_number->plantilla->id;

            $division_id = strip_tags($request->division_id);
            $department_id = strip_tags($request->department_id);
            $section_id = strip_tags($request->section_id);
            $unit_id = strip_tags($request->unit_id);

            if($division_id === null && $department_id === null && $section_id === null && $unit_id === null){
                return response()->json(['message' => 'Please select area for employee to assign.'], Response::HTTP_BAD_REQUEST);
            }

            foreach ($request->all() as $key => $value) {
                if($key === 'user') continue;
                if ($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $assigned_area = AssignArea::create($cleanData);

            $this->requestLogger->registerSystemLogs($request, $assigned_area['id'], true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' =>  new AssignAreaResource($assigned_area),
                'message' => 'New employee assign area registered.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function show($id, Request $request)
    {
        try{
            $assigned_area = AssignArea::findOrFail($id);

            if(!$assigned_area)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in fetching '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' => new AssignAreaResource($assigned_area), 
                'message' => 'Assigned area record found.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, AssignAreaRequest $request)
    {
        try{
            $assigned_area = AssignArea::find($id);

            if(!$assigned_area)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if ($value === null) {
                    $cleanData[$key] = $value;
                    continue;
                } 
                $cleanData[$key] = strip_tags($value);
            }

            $assigned_area->update($cleanData);

            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');

            return response()->json([
                'data' =>  new AssignAreaResource($assigned_area),
                'message' => 'New employee assign area registered.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
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

            $assigned_area = AssignArea::findOrFail($id);

            if(!$assigned_area)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $assigned_area->delete();
            
            $this->requestLogger->registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Assigned area record deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
            $this->requestLogger->errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
