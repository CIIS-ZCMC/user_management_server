<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Resources\PlantillaWithDesignationResource;
use App\Models\Designation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Requests\PlantillaRequest;
use App\Http\Resources\DesignationEmployeesResource;
use App\Http\Resources\PlantillaResource;
use App\Http\Resources\PlantillaNumberAllResource;
use App\Models\Plantilla;
use App\Models\PlantillaNumber;
use App\Models\PlantillaRequirement;
use App\Models\PlantillaAssignedArea;
use App\Models\Division;
use App\Models\Department;
use App\Models\Section;
use App\Models\Unit;

class PlantillaController extends Controller
{
    private $CONTROLLER_NAME = 'Plantilla';
    private $PLURAL_MODULE_NAME = 'plantillas';
    private $SINGULAR_MODULE_NAME = 'plantilla';
    
    public function index(Request $request)
    {
        try{

            $plantillas = PlantillaNumber::all();
            
            return response()->json([
                'data' => PlantillaNumberAllResource::collection($plantillas),
                'message' => 'Plantilla list retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function plantillaWithDesignation($id, Request $request)
    {
        try{
            $designation = Designation::find($id);

            if(!$designation){
                return response()->json(['message' => 'No record found for designation with id '.$id], Response::HTTP_NOT_FOUND);
            }

            $plantillas = $designation->plantilla;
            $plantilla_numbers = [];

            foreach($plantillas as $plantilla){
                foreach($plantilla->plantillaNumbers as $value){
                    if($value->is_vacant && $value->assigned_at !== null){
                        $plantilla_numbers[] = $value;
                    }
                }
            }
            
            return response()->json([
                'data' => PlantillaWithDesignationResource::collection($plantilla_numbers),
                'message' => 'Plantilla number by designation.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByDesignationID($id, Request $request)
    {
        try{
            $sector_employees = Plantilla::with('assigned_areas')->findOrFail($id);

            return response()->json([
                'data' => DesignationEmployeesResource::collection($sector_employees),
                'message' => 'Plantilla record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'employeesOfSector', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function store(PlantillaRequest $request)
    {
        try{

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($key === 'plantilla_number'){
                    $cleanData[$key] = json_decode($value);
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $plantilla = Plantilla::create($cleanData);

            $cleanData['plantilla_id'] = $plantilla->id;

            PlantillaRequirement::create($cleanData);

            $failed = [];

            $plantilla_numbers = [];

            foreach($cleanData['plantilla_number'] as $value)
            {
                try{
                    if(!is_string($value))
                    {
                        $failed_to_register = [
                            'plantilla_number' => $value,
                            'remarks' => 'Invalid type require string.'
                        ];
                        
                        $failed[] = $failed_to_register;
                        continue;
                    }

                    $plantilla_number_new = PlantillaNumber::create([
                        'number' => $value,
                        'plantilla_id' => $plantilla->id
                    ]);

                    $plantilla_numbers[] = $plantilla_number_new;
                }catch(\Throwable $th){
                    $failed_to_register = [
                        'plantilla_number' => $value,
                        'remarks' => 'Something went wrong.'
                    ];
                    $failed[] = $failed_to_register;
                    continue;
                }
            }

            $data = PlantillaNumberAllResource::collection($plantilla_numbers);
            $message = 'Plantilla created successfully.';

            if(count($failed) === count($cleanData['plantilla_number']))
            {
                $data = [];
                $message = 'Failed to register plantilla numbers.';
            }

            if(count($failed) > 0)
            {
                $data = [
                    'new_plantilla' => PlantillaNumberAllResource::collection($plantilla_numbers),
                    'failed' => $failed
                ];
                $message = 'Some plantilla number failed to register.';
            }

            Helpers::registerSystemLogs($request, null, true, 'Success in creating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response() -> json([
                'data' => $data,
                'message' => $message
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function areasForPlantillaAssign(Request $request)
    {
        try{
            $divisions = Division::all();
            $departments = Department::all();
            $sections = Section::all();
            $units = Unit::all();

            $all_areas = [];

            foreach($divisions as $division){
                $area = [
                    'area' => $division->id,
                    'name' => $division->name,
                    'sector' => 'division'
                ];
                $all_areas[] = $area;
            }
            
            foreach($departments as $department){
                $area = [
                    'area' => $department->id,
                    'name' => $department->name,
                    'sector' => 'department'
                ];
                $all_areas[] = $area;
            }
            
            foreach($sections as $section){
                $area = [
                    'area' => $section->id,
                    'name' => $section->name,
                    'sector' => 'section'
                ];
                $all_areas[] = $area;
            }
            
            foreach($units as $unit){
                $area = [
                    'area' => $unit->id,
                    'name' => $unit->name,
                    'sector' => 'section'
                ];
                $all_areas[] = $area;
            }            

            return response() -> json([
                'data' => $all_areas,
                'message' => 'List of areas'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'assignPlantillaToAreas', $th->getMessage());
           return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function assignPlantillaToAreas($id, Request $request)
    {
        try{
            $plantilla_number = PlantillaNumber::find($id);

            if(!$plantilla_number)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            $cleanData['plantilla_number_id'] = $plantilla_number->id;
            $key = '';
            
            switch($request->sector){
                case 'division':
                    $key = 'division_id';
                    break;
                case 'department':
                    $key = 'department_id';
                    break;
                case 'section':
                    $key = 'section_id';
                    break;
                default:
                    $key = 'unit_id';
                    break;

            }
            $cleanData[$key] =  strip_tags($request->area);

            $plantilla_assign_area = PlantillaAssignedArea::create($cleanData);

            if(!$plantilla_assign_area){
                return response()->json(['message' => "Failed to assign plantilla number."], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return response() -> json([
                'data' => new PlantillaNumberAllResource($plantilla_number),
                'message' => 'Plantilla assign successfully.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'assignPlantillaToAreas', $th->getMessage());
           return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id, Request $request)
    {
        try{
            $plantilla = Plantilla::find($id);

            if(!$plantilla)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }
            
            return response()->json([
                'data' => new PlantillaResource($plantilla),
                'message' => 'Plantilla record retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function showPlantillaNumber($id, Request $request)
    {
        try{
            $plantilla_number = PlantillaNumber::find($id);

            if(!$plantilla_number)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            Helpers::registerSystemLogs($request, null, true, 'Success in fetching '.$this->PLURAL_MODULE_NAME.'.');
            
            return response()->json([
                'data' => new PlantillaNumberAllResource($plantilla_number),
                'message' => 'Plantilla list retrieved.'
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function update($id, PlantillaRequest $request)
    {
        try{
            $plantilla = Plantilla::find($id);

            if(!$plantilla)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if($key === 'plantilla_number'){
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $plantilla -> update($cleanData);
            $plantilla_requirement = $plantilla->requirement;
            $plantilla_requirement->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json([
                'data' => new PlantillaResource($plantilla),
                'message' => 'Plantilla update successfully.'], Response::HTTP_OK);
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

            $plantilla = Plantilla::findOrFail($id);

            if(!$plantilla)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $plantilla_numbers = $plantilla->plantillaNumbers;

            $deletion_prohibited = false;

            foreach($plantilla_numbers as $plantilla_number)
            {
                if($plantilla_number->employee_profile_id !== null)
                {
                    $deletion_prohibited = true;
                    break;
                }
            }

            if($deletion_prohibited)
            {
                return response()->json(['message' => "Some plantilla number are already in used deletion prohibited."], Response::HTTP_BAD_REQUEST);
            }


            foreach($plantilla_numbers as $plantilla_number)
            { 
                $plantilla_number->delete();
            }


            $requirement = $plantilla->requirement;
            $requirement->delete();
            $plantilla -> delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Plantilla record and plantilla number are deleted.'], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function destroyPlantillaNumber($id, PasswordApprovalRequest $request)
    {
        try{
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password.env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $plantilla_number = PlantillaNumber::findOrFail($id);

            if(!$plantilla_number)
            {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $plantilla_number -> delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting '.$this->SINGULAR_MODULE_NAME.'.');
            
            return response()->json(['message' => 'Plantilla number deleted successfully.'], Response::HTTP_OK);
        }catch(\Throwable $th){
             Helpers::errorLog($this->CONTROLLER_NAME,'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
