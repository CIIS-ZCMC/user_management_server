<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Resources\PlantillaWithDesignationResource;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use App\Helpers\Helpers;
use App\Http\Requests\PasswordApprovalRequest;
use App\Http\Requests\PlantillaRequest;
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
use App\Models\EmployeeProfile;
use App\Models\AssignArea;

class PlantillaController extends Controller
{
    private $CONTROLLER_NAME = 'Plantilla';
    private $PLURAL_MODULE_NAME = 'plantillas';
    private $SINGULAR_MODULE_NAME = 'plantilla';

    public function index(Request $request)
    {
        try {

            $plantillas = PlantillaNumber::where('is_dissolve', false)->get();

            return response()->json([
                'data' => PlantillaNumberAllResource::collection($plantillas),
                'message' => 'Plantilla list retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function reAssignPlantilla($id, Request $request)
    {
        try {

           
            /*
            toassign : New Plantilla_number_ID
            password : userPassword
            */
            $user = $request->user;
            $cleanData['password'] = strip_tags($request->password);;
            $decryptedPassword = Crypt::decryptString($user['password_encrypted']);
            if (!Hash::check($cleanData['password'] . env("SALT_VALUE"), $decryptedPassword)) {
                return response()->json(['message' => "Request rejected invalid password."], Response::HTTP_UNAUTHORIZED);
            }
            $employee_profile = EmployeeProfile::findOrFail($id);
            $to_assign = $request->toassign;
            /* plantilla_id | plantilla_numbers */
            $user_Current_Plantilla = $employee_profile->assignedArea->plantilla_number_id;
            if ($user_Current_Plantilla) {

               
                $New = PlantillaAssignedArea::where('plantilla_number_id', $to_assign)->first();
                $newPlantilla = PlantillaNumber::where('id',$to_assign)->first()->plantilla;
                $newdivision_id = null;
                $newdepartment_id = null;
                $newsection_id = null;
                $newunit_id = null;

                if($New->division_id !== NULL){
                $newdivision_id = $New->division_id;
                }
                if($New->department_id !== NULL){
                $newdepartment_id =$New->department_id;
                }
                if($New->section_id !== NULL){
                  $newsection_id  = $New->section_id;
                }
                if($New->unit_id !== NULL){
                    $newunit_id=$New->unit_id;
                }

                AssignArea::create([
                    'salary_grade_step' => 1,
                    'employee_profile_id' => $id,
                    'division_id' => $newdivision_id,
                    'department_id' => $newdepartment_id,
                    'section_id' => $newsection_id,
                    'unit_id' => $newunit_id,
                    'designation_id' => $newPlantilla->designation_id,
                    'plantilla_id' => $newPlantilla->id,
                    'plantilla_number_id' => $to_assign,
                    'effective_at' => now()
                ]);
                PlantillaNumber::where('id', $user_Current_Plantilla)->update([
                    'is_dissolve' => 1,
                    'is_vacant' => 0,
                    'employee_profile_id' => NULL,
                ]);
                PlantillaNumber::where('id', $to_assign)->update([
                    'employee_profile_id' => $id,
                    'is_vacant' => 0, 
                    'is_dissolve' => 0,
                ]);

                return response()->json([
                    'message' => 'Plantilla reassigned successfully!'
                ], Response::HTTP_OK);
            }
            return response()->json([
                'message' => 'No plantilla records found for this user.'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'reAssignPlantilla', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function reAssignArea($id, Request $request)
    {
        try {
            /**
             * id = plantilla number id
             * area = area id (division, department, section, unit)
             * sector = area sector
             * effective_at = when the data will be use
             */
            $plantilla_number = PlantillaNumber::find($id);

            if (!$plantilla_number) {
                return response()->json(['message' => 'No record found for plantilla number with id ' . $id], Response::HTTP_NOT_FOUND);
            }

            $key = null;


            if ($request->sector === null) {
                return response()->json(['message' => 'Invalid request.'], Response::HTTP_BAD_REQUEST);
            }

            switch (strip_tags($request->sector)) {
                case 'division':
                    $division = Division::find(strip_tags((int) $request->area));
                    if (!$division) {
                        return response()->json(['message' => 'No record found for division with id ' . $id], Response::HTTP_NOT_FOUND);
                    }
                    $key = 'division_id';
                    break;
                case 'department':
                    $department = Department::find(strip_tags((int) $request->area));
                    if (!$department) {
                        return response()->json(['message' => 'No record found for department with id ' . $id], Response::HTTP_NOT_FOUND);
                    }
                    $key = 'department_id';
                    break;
                case 'section':
                    $section = Section::find(strip_tags((int) $request->area));
                    if (!$section) {
                        return response()->json(['message' => 'No record found for section with id ' . $id], Response::HTTP_NOT_FOUND);
                    }
                    $key = 'section_id';
                    break;
                case 'unit':
                    $unit = Unit::find(strip_tags((int) $request->area));
                    if (!$unit) {
                        return response()->json(['message' => 'No record found for unit with id ' . $id], Response::HTTP_NOT_FOUND);
                    }
                    $key = 'unit_id';
                    break;
                default:
                    return response()->json(['message' => 'Undefined area.'], Response::HTTP_BAD_REQUEST);
            }

            $area_list = ['division_id', 'department_id', 'section_id', 'unit_id'];
            $new_data_of_area = [];

            foreach ($area_list as $area) {
                if ($area === $key) {
                    $new_data_of_area[$key] = (int) $request->area;
                    continue;
                }
                $new_data_of_area[$area] = null;
            }

            $plantilla_number->assignedArea->update([...$new_data_of_area, 'effective_at' => $request->effective_at]);
            $employee_profile = $plantilla_number->employeeProfile;

            $employee_profile->assignedArea->update([...$new_data_of_area, 'effective_at' => $request->effective_at]);

            // if($plantilla_number->employee_profile_id !== null && Carbon::parse($request->effective_at)->isPast()){

            //     Artisan::call('app:plantilla-number-re-assign-task:to-run', [
            //         '--taskId' => $plantilla_number->id,
            //         '--area' => $request->area,
            //         '--sector' => $request->sector
            //     ]);
            // }

            return response()->json([
                'data' => new PlantillaNumberAllResource($plantilla_number),
                'message' => 'Plantilla list retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function plantillaWithDesignation($id, Request $request)
    {
        try {
            $designation = Designation::find($id);

            if (!$designation) {
                return response()->json(['message' => 'No record found for designation with id ' . $id], Response::HTTP_NOT_FOUND);
            }

            $plantillas = $designation->plantilla;
            $plantilla_numbers = [];

            foreach ($plantillas as $plantilla) {
                foreach ($plantilla->plantillaNumbers as $value) {
                    if ($value->is_vacant && $value->assigned_at !== null) {
                        $plantilla_numbers[] = $value;
                    }
                }
            }

            return response()->json([
                'data' => PlantillaWithDesignationResource::collection($plantilla_numbers),
                'message' => 'Plantilla number by designation.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function findByDesignationID($id, Request $request)
    {
        try {
            $designation = Designation::find($id);

            if (!$designation) {
                return response()->json(['message' => 'No record found for designation with id ' . $id], Response::HTTP_NOT_FOUND);
            }

            $plantillas = $designation->plantilla;
            $plantilla_numbers = [];

            foreach ($plantillas as $plantilla) {
                foreach ($plantilla->plantillaNumbers as $value) {
                    if ($value->assigned_at === null) {
                        $plantilla_numbers[] = $value;
                    }
                }
            }

            return response()->json([
                'data' => [
                    'plantilla_numbers' => PlantillaWithDesignationResource::collection($plantilla_numbers),
                    'total_vacant_items' => count($designation->plantilla) === 0 ? 0 : PlantillaNumber::where('plantilla_id', $designation->plantilla[0]->id)->where('assigned_at', null)->count(),
                    'total_plantilla' => count($designation->plantilla) === 0 ? 0 : PlantillaNumber::where('plantilla_id', $designation->plantilla[0]->id)->count()
                ],
                'message' => 'Plantilla number by designation.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(PlantillaRequest $request)
    {
        try {

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if ($key === 'plantilla_number') {
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

            foreach ($cleanData['plantilla_number'] as $value) {
                try {
                    $existing = PlantillaNumber::where('number', $value)->first();

                    if (!is_string($value) || $existing !== null) {
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
                } catch (\Throwable $th) {
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

            if (count($failed) === count($cleanData['plantilla_number'])) {
                $data = [];
                $message = 'Failed to register plantilla numbers.';
            }

            if (count($failed) > 0) {
                $data = [
                    'new_plantilla' => PlantillaNumberAllResource::collection($plantilla_numbers),
                    'failed' => $failed
                ];
                $message = 'Some plantilla number failed to register.';
            }

            Helpers::registerSystemLogs($request, null, true, 'Success in creating ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json([
                'data' => $data,
                'message' => $message
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function areasForPlantillaAssign(Request $request)
    {
        try {
            $divisions = Division::all();
            $departments = Department::all();
            $sections = Section::all();
            $units = Unit::all();

            $all_areas = [];

            foreach ($divisions as $division) {
                $area = [
                    'area' => $division->id,
                    'name' => $division->name,
                    'sector' => 'division'
                ];
                $all_areas[] = $area;
            }

            foreach ($departments as $department) {
                $area = [
                    'area' => $department->id,
                    'name' => $department->name,
                    'sector' => 'department'
                ];
                $all_areas[] = $area;
            }

            foreach ($sections as $section) {
                $area = [
                    'area' => $section->id,
                    'name' => $section->name,
                    'sector' => 'section'
                ];
                $all_areas[] = $area;
            }

            foreach ($units as $unit) {
                $area = [
                    'area' => $unit->id,
                    'name' => $unit->name,
                    'sector' => 'unit'
                ];
                $all_areas[] = $area;
            }

            return response()->json([
                'data' => $all_areas,
                'message' => 'List of areas'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'assignPlantillaToAreas', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function assignPlantillaToAreas($id, Request $request)
    {
        try {
            $plantilla_number = PlantillaNumber::find($id);

            if (!$plantilla_number) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            $cleanData['effective_at'] = $plantilla_number->plantilla->effective_at;

            $cleanData['plantilla_number_id'] = $plantilla_number->id;
            $key = '';

            switch ($request->sector) {
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

            $key_list = ['division_id', 'department_id', 'section_id', 'unit_id'];

            foreach ($key_list as $value) {
                if ($value === $key) continue;
                $cleanData[$value] = null;
            }
            $plantilla_assign_area = PlantillaAssignedArea::create($cleanData);
            $plantilla_number->update(['assigned_at' => now()]);

            if (!$plantilla_assign_area) {
                return response()->json(['message' => "Failed to assign plantilla number."], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return response()->json([
                'data' => new PlantillaNumberAllResource($plantilla_number),
                'message' => 'Plantilla assign successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'assignPlantillaToAreas', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function assignMultiplePlantillaToArea($id, Request $request)
    {
        try {
            $plantilla = Plantilla::find($id);

            if (!$plantilla) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $total_of_plantilla_to_distribute = strip_tags($request->total_of_plantilla_to_distribute);
            $sector = strip_tags($request->sector);
            $area_id = strip_tags($request->area_id);

            $cleanData = [];

            switch ($sector) {
                case 'division':
                    $key = 'division_id';
                    $division = Division::find($area_id);
                    if (!$division) return response()->json(['message' => 'No division record found with ID ' . $area_id . '.'], Response::HTTP_NOT_FOUND);
                    break;
                case 'department':
                    $key = 'department_id';
                    $department = Department::find($area_id);
                    if (!$department) return response()->json(['message' => 'No department record found with ID ' . $area_id . '.'], Response::HTTP_NOT_FOUND);
                    break;
                case 'section':
                    $key = 'section_id';
                    $section = Section::find($area_id);
                    if (!$section) return response()->json(['message' => 'No section record found with ID ' . $area_id . '.'], Response::HTTP_NOT_FOUND);
                    break;
                case 'unit':
                    $key = 'unit_id';
                    $unit = Unit::find($area_id);
                    if (!$unit) return response()->json(['message' => 'No unit record found with ID ' . $area_id . '.'], Response::HTTP_NOT_FOUND);
                    break;
                default:
                    return response()->json(['message' => 'In valid area ID.'], Response::HTTP_BAD_REQUEST);
            }

            $cleanData[$key] =  strip_tags($request->area_id);

            $key_list = ['division_id', 'department_id', 'section_id', 'unit_id'];

            foreach ($key_list as $value) {
                if ($value === $key) continue;
                $cleanData[$value] = null;
            }

            $plantilla_numbers = PlantillaNumber::where('plantilla_id', $plantilla->id)
                ->where('is_vacant', 1)->limit($total_of_plantilla_to_distribute)->get();

            $plantilla_result = [];

            foreach ($plantilla_numbers as $plantilla_number) {
                $cleanData['plantilla_number_id'] = $plantilla_number->id;
                $plantilla_assign_area = PlantillaAssignedArea::create($cleanData);
                $plantilla_number->update(['assigned_at' => now(), 'is_vacant' => 0]);
                $plantilla_number['plantilla_assign_area'] = $plantilla_assign_area;
                $plantilla_result[] = $plantilla_number;
            }

            return response()->json([
                'data' => $plantilla_result,
                'message' => 'Plantilla assign successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'assignPlantillaToAreas', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id, Request $request)
    {
        try {
            $plantilla = Plantilla::find($id);

            if (!$plantilla) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'data' => new PlantillaResource($plantilla),
                'message' => 'Plantilla record retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'show', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function showPlantillaNumber($id, Request $request)
    {
        try {
            $plantilla_number = PlantillaNumber::find($id);

            if (!$plantilla_number) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            Helpers::registerSystemLogs($request, null, true, 'Success in fetching ' . $this->PLURAL_MODULE_NAME . '.');

            return response()->json([
                'data' => new PlantillaNumberAllResource($plantilla_number),
                'message' => 'Plantilla list retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id, PlantillaRequest $request)
    {
        try {
            $plantilla = Plantilla::find($id);

            if (!$plantilla) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if ($key === 'plantilla_number') {
                    $cleanData[$key] = $value;
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $plantilla->update($cleanData);
            $plantilla_requirement = $plantilla->requirement;
            $plantilla_requirement->update($cleanData);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json([
                'data' => new PlantillaResource($plantilla),
                'message' => 'Plantilla update successfully.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id, PasswordApprovalRequest $request)
    {
        try {
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password . env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $plantilla = Plantilla::findOrFail($id);

            if (!$plantilla) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $plantilla_numbers = $plantilla->plantillaNumbers;

            $deletion_prohibited = false;

            foreach ($plantilla_numbers as $plantilla_number) {
                if ($plantilla_number->employee_profile_id !== null) {
                    $deletion_prohibited = true;
                    break;
                }
            }

            if ($deletion_prohibited) {
                return response()->json(['message' => "Some plantilla number are already in used deletion prohibited."], Response::HTTP_BAD_REQUEST);
            }


            foreach ($plantilla_numbers as $plantilla_number) {
                $plantilla_number->delete();
            }


            $requirement = $plantilla->requirement;
            $requirement->delete();
            $plantilla->delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(['message' => 'Plantilla record and plantilla number are deleted.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroyPlantillaNumber($id, PasswordApprovalRequest $request)
    {
        try {
            $password = strip_tags($request->password);

            $employee_profile = $request->user;

            $password_decrypted = Crypt::decryptString($employee_profile['password_encrypted']);

            if (!Hash::check($password . env("SALT_VALUE"), $password_decrypted)) {
                return response()->json(['message' => "Password incorrect."], Response::HTTP_UNAUTHORIZED);
            }

            $plantilla_number = PlantillaNumber::findOrFail($id);

            if (!$plantilla_number) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            $plantilla_number->delete();

            Helpers::registerSystemLogs($request, $id, true, 'Success in deleting ' . $this->SINGULAR_MODULE_NAME . '.');

            return response()->json(['message' => 'Plantilla number deleted successfully.'], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'destroy', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
