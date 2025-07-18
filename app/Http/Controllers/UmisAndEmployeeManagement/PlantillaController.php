<?php

namespace App\Http\Controllers\UmisAndEmployeeManagement;

use App\Http\Controllers\Controller;

use App\Http\Requests\AuthPinApprovalRequest;
use App\Http\Resources\PlantillaReferrenceResource;
use App\Http\Resources\PlantillaWithDesignationResource;
use App\Models\Designation;
use App\Models\EmploymentType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
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
use App\Models\AssignAreaTrail;

class PlantillaController extends Controller
{
    private $CONTROLLER_NAME = 'Plantilla';
    private $PLURAL_MODULE_NAME = 'plantillas';
    private $SINGULAR_MODULE_NAME = 'plantilla';

    public function index(Request $request)
    {
        try {
            $search = $request->query('search');
            $current_page = $request->query('currentPage') ?? 1;  
            $limit = $request->query("limit");
            $salary_grade = $request->query('salaryGrade');
            $area_name = $request->query('areaName');

            $plantillas = PlantillaNumber::where('is_dissolve', false)
                ->when($search, function ($query) use ($search) {
                    return $query->where('number', 'like', "$search%");
                })
                ->when($salary_grade, function ($query) use ($salary_grade) {
                    return $query->whereHas('plantilla.designation.salaryGrade', function ($query) use ($salary_grade) {
                        $query->where('salary_grade_number', $salary_grade);
                    });
                })->paginate($limit, ['*'], 'page', $current_page);

            if($area_name) {
                $filteredPlantillas = $plantillas->filter(function ($plantilla) use ($area_name) {
                    $areaDetails = $plantilla->assignedArea->area()['details'];
                    return strpos(strtolower($areaDetails['name']), strtolower($area_name)) !== false;
                });

                return PlantillaNumberAllResource::collection($filteredPlantillas)
                    ->additional([
                        'meta' => [],
                        'message' => 'Plantilla list retrieved.'
                    ]);
            }

            return PlantillaNumberAllResource::collection($plantillas)
              ->additional([
                  'meta' => [],
                  'message' => 'Plantilla list retrieved.'
              ]);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Change this when the client is updated already to handle pagination
    public function index2(Request $request)
    {
        try {
            $search = $request->query('search');
            $current_page = $request->query('currentPage');
            $limit = $request->query("limit");
            $offset = $current_page === 1 ? 0 : $current_page * $limit;
            $salary_grade = $request->query('salaryGrade');
            $area_name = $request->query('areaName');

            
            $total_page_count = PlantillaNumber::where('is_dissolve', false)
                ->when($search, function ($query) use ($search) {
                    return $query->where('number', 'like', "$search%");
                })
                ->when($salary_grade, function ($query) use ($salary_grade) {
                    return $query->whereHas('plantilla.designation.salaryGrade', function ($query) use ($salary_grade) {
                        $query->where('salary_grade_number', $salary_grade);
                    });
                })
                ->count();

            $plantillas = PlantillaNumber::where('is_dissolve', false)
                ->when($search, function ($query) use ($search) {
                    return $query->where('number', 'like', "$search%");
                })
                ->when($salary_grade, function ($query) use ($salary_grade) {
                    return $query->whereHas('plantilla.designation.salaryGrade', function ($query) use ($salary_grade) {
                        $query->where('salary_grade_number', $salary_grade);
                    });
                })
                ->limit($limit)
                ->offset($offset)
                ->get();

            if($area_name) {
                $filteredPlantillas = $plantillas->filter(function ($plantilla) use ($area_name) {
                    $areaDetails = $plantilla->assignedArea->area()['details'];
                    return strpos(strtolower($areaDetails['name']), strtolower($area_name)) !== false;
                });

                return response()->json([
                    'data' => PlantillaNumberAllResource::collection($filteredPlantillas),
                    'total_page_count' => $filteredPlantillas < $limit? 1 : ceil(count($filteredPlantillas) / $limit),
                    'message' => 'Area Plantilla list retrieved.'
                ], Response::HTTP_OK);
            }

            return response()->json([
                'data' => PlantillaNumberAllResource::collection($plantillas),
                'total_page_count' => $total_page_count < $limit ? 1 : ceil($total_page_count / $limit),
                'message' => 'Plantilla list retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function plantillaNumberBaseOnJobPositionAndEmploymentType(Request $request)
    {
        try {
            $employee_type_id = $request->employee_type_id;
            $designation_id = $request->designation_id;

            /**
             * If Both employment Type and Job position not specified.
             */
            if($employee_type_id === null && $designation_id === null){

                $plantillas = PlantillaNumber::where('is_dissolve', false)->where('plantilla_numbers.employee_profile_id', null)->get();

                return response()->json([
                    'data' => PlantillaNumberAllResource::collection($plantillas),
                    'message' => 'Plantilla list retrieved.'
                ], Response::HTTP_OK);
            }

            /**
             * If Employment Type is specified but not the Job position
             */
            if($employee_type_id !== null && $designation_id === null){

                $plantillas = PlantillaNumber::where('employment_type_id', $employee_type_id)->where('employee_profile_id', null)
                    ->where('is_dissolve', false)->get();

                return response()->json([
                    'data' => PlantillaNumberAllResource::collection($plantillas),
                    'message' => 'Plantilla list retrieved.'
                ], Response::HTTP_OK);
            }

            /**
             * If Job position is specified but not the Employment Type
             */
            if($employee_type_id === null && $designation_id !== null){

                $plantillas = PlantillaNumber::select('plantilla_numbers.*')
                    ->join('plantillas as p', 'p.id', 'plantilla_numbers.plantilla_id')
                    ->where('p.designation_id', $designation_id)
                    ->where('plantilla_numbers.employee_profile_id', null)
                    ->where('plantilla_numbers.is_dissolve', false)->get();

                return response()->json([
                    'data' => PlantillaNumberAllResource::collection($plantillas),
                    'message' => 'Plantilla list retrieved.'
                ], Response::HTTP_OK);
            }

            /**
             * If Both area specified
             */

            $plantillas = PlantillaNumber::select('plantilla_numbers.*')
                    ->join('plantillas as p', 'p.id', 'plantilla_numbers.plantilla_id')
                    ->where('p.designation_id', $designation_id)
                    ->where('plantilla_numbers.employment_type_id', $employee_type_id)
                    ->where('plantilla_numbers.employee_profile_id', null)
                    ->where('plantilla_numbers.is_dissolve', false)->get();

            return response()->json([
                'data' => PlantillaNumberAllResource::collection($plantillas),
                'message' => 'Plantilla list retrieved.'
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** [API] - plantilla-referrence-to-assignarea [METHOD] - GET */
    public function plantillaReferrenceToAssignArea(Request $request)
    {
        try {
            $plantillas = Plantilla::all();

            return response()->json([
                'data' => PlantillaReferrenceResource::collection($plantillas),
                'message' => 'Plantilla list to assign area retrieved.'
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
            // $user = $request->user;
            // $cleanData['pin'] = strip_tags($request->password);

            // if ($user['authorization_pin'] !==  $cleanData['pin']) {
            //     return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            // }

            DB::beginTransaction();
            $employee_profile = EmployeeProfile::findOrFail($id);
            $to_assign = $request->toassign;
            
            /* plantilla_id | plantilla_numbers */
            $user_Current_Plantilla = $employee_profile->assignedArea->plantilla_number_id;
            if ($user_Current_Plantilla) {
                /*
                save old assignare to trails
                - make the old empprof null
                */
                $old_assignedArea = AssignArea::where('plantilla_number_id', $user_Current_Plantilla)->first();

                AssignAreaTrail::create([
                    'salary_grade_step' => $old_assignedArea->salary_grade_step,
                    'employee_profile_id' => $old_assignedArea->employee_profile_id,
                    'division_id' => $old_assignedArea->division_id,
                    'department_id' => $old_assignedArea->department_id,
                    'section_id' => $old_assignedArea->section_id,
                    'unit_id' => $old_assignedArea->unit_id,
                    'designation_id' => $old_assignedArea->designation_id,
                    'plantilla_id' => $old_assignedArea->plantilla_id,
                    'plantilla_number_id' => $old_assignedArea->plantilla_number_id,
                    'started_at' => $old_assignedArea->effective_at,
                    'end_at' => now()
                ]);

                $plantilla_assigned_area = PlantillaAssignedArea::where('plantilla_number_id', $to_assign)->first();
                $newPlantilla = PlantillaNumber::where('id', $to_assign)->first()->plantilla;
                $area = [];


                DB::rollBack();
    
                return response()->json([
                    'data' => $to_assign,
                    'message' => 'No plantilla records found for this user.'
                ], Response::HTTP_NOT_FOUND);
                if($plantilla_assigned_area->division_id !== null){
                    $area[] = ["division_id" => $$plantilla_assigned_area->division_id];
                }

                if($plantilla_assigned_area->department_id !== null){
                    $area[] = ["department_id" => $$plantilla_assigned_area->department_id];
                }

                if($plantilla_assigned_area->section_id !== null){
                    $area[] = ["section_id" => $$plantilla_assigned_area->section_id];
                }

                if($plantilla_assigned_area->unit_id !== null){
                    $area[] = ["unit_id" => $$plantilla_assigned_area->unit_id];
                }

                AssignArea::create([
                    ...$area,
                    'salary_grade_step' => 1,
                    'employee_profile_id' => $id,
                    'designation_id' => $newPlantilla->designation_id,
                    'plantilla_id' => $newPlantilla->id,
                    'plantilla_number_id' => $to_assign,
                    'effective_at' => now()
                ]);

                $plantilla_number = PlantillaNumber::where('id', $user_Current_Plantilla)->first();


                // CHANGE PLANTILLA EMP TYPE to EMPLOYEE PROFILE EMP TYPE
                // if ($employee_profile->employmentType->name === 'Permanent CTI') {
                //     $plantilla_number->update([
                //         'is_dissolve' => 1,
                //         'is_vacant' => 0,
                //         'employee_profile_id' => NULL,
                //     ]);
                // } else {
                    $plantilla_number->update(['is_vacant' => 1, 'employee_profile_id' => NULL]);
                    $plantilla = $plantilla_number->plantilla;
                    $plantilla->update(['total_used_plantilla_no' => $plantilla->total_used_plantilla_no + 1]);
                // }

                PlantillaNumber::where('id', $to_assign)->update([
                    'employee_profile_id' => $id,
                    'is_vacant' => 0,
                    'is_dissolve' => 0,
                ]);

                $plantilla = $newPlantilla;
                $plantilla->update(['total_used_plantilla_no' => $plantilla->total_used_plantilla_no + 1]);
                $old_assignedArea->delete();

                DB::commit();

                return response()->json([
                    'message' => 'Plantilla reassigned successfully!'
                ], Response::HTTP_OK);
            }

            DB::rollBack();

            return response()->json([
                'message' => 'No plantilla records found for this user.'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $th) {
            DB::rollBack();
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

            $plantilla_numbers = PlantillaNumber::where('is_vacant', 1)
            ->where('assigned_at', NULL)
            ->where('is_dissolve', 0)
            ->whereHas('plantilla', function($query) use ($id) {
                return $query->where('designation_id', $id);
            })->get();

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
                if ($key === 'plantilla_numbers') {
                    $cleanData[$key] = json_decode($value, true);
                    continue;
                }
                $cleanData[$key] = strip_tags($value);
            }

            $plantilla = Plantilla::create($cleanData);

            $cleanData['plantilla_id'] = $plantilla->id;

            DB::beginTransaction();

            PlantillaRequirement::create($cleanData);

            $plantilla_numbers = [];

            foreach ($cleanData['plantilla_numbers'] as $number) {
                try {
                    
                    $existing = PlantillaNumber::where('number', $number['plantilla_number'])->first();

                    if ($existing) {
                        DB::rollBack();
                        return response()->json(['message' => "Plantilla number already exist."], Response::HTTP_FORBIDDEN);
                    }

                    if (!is_string($number['plantilla_number'] ) ) {
                        DB::rollBack();
                        return response()->json(['message' => "Invalid type require string."], Response::HTTP_FORBIDDEN);
                    }
                   
                    $plantilla_number_new = PlantillaNumber::create([
                        'number' => $number['plantilla_number'],
                        'employment_type_id' => $number['employment_type_id'],
                        'plantilla_id' => $plantilla->id
                    ]);

                    $plantilla_numbers[] = $plantilla_number_new;
                } catch (\Throwable $th) {
                    DB::rollBack();
                    return response()->json(['message' => 'Failed to registered plantilla'], Response::HTTP_FORBIDDEN);
                }
            }

            $data = PlantillaNumberAllResource::collection($plantilla_numbers);
            $message = 'Plantilla created successfully.';
            DB::commit();

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
            $divisions = Division::orderBy('name', 'asc')->get();
            $departments = Department::orderBy('name', 'asc')->get();
            $sections = Section::orderBy('name', 'asc')->get();
            $units = Unit::orderBy('name', 'asc')->get();

            $all_areas = [];

            foreach ($divisions as $division) {
                $area = [
                    'area' => $division->id,
                    'name' => $division->name,
                    'sector' => 'division',
                    'code' => $division->code,
                    'area_id' => $division->area
                ];
                $all_areas[] = $area;
            }

            foreach ($departments as $department) {
                $area = [
                    'area' => $department->id,
                    'name' => $department->name,
                    'sector' => 'department',
                    'code' =>  $department->code,
                    'area_id' => $department->area_id
                ];
                $all_areas[] = $area;
            }

            foreach ($sections as $section) {
                $area = [
                    'area' => $section->id,
                    'name' => $section->name,
                    'sector' => 'section',
                    'code' => $section->code,
                    'area_id' => $section->area_id
                ];
                $all_areas[] = $area;
            }

            foreach ($units as $unit) {
                $area = [
                    'area' => $unit->id,
                    'name' => $unit->name,
                    'sector' => 'unit',
                    'code' => $unit->code,
                    'area_id' => $unit->area_id
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
            $key = strtolower($request->sector).'_id';
            
            $cleanData[$key] = strip_tags($request->area);

            $key_list = ['division_id', 'department_id', 'section_id', 'unit_id'];

            foreach ($key_list as $value) {
                if ($value === $key)
                    continue;
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
                    if (!$division)
                        return response()->json(['message' => 'No division record found with ID ' . $area_id . '.'], Response::HTTP_NOT_FOUND);
                    break;
                case 'department':
                    $key = 'department_id';
                    $department = Department::find($area_id);
                    if (!$department)
                        return response()->json(['message' => 'No department record found with ID ' . $area_id . '.'], Response::HTTP_NOT_FOUND);
                    break;
                case 'section':
                    $key = 'section_id';
                    $section = Section::find($area_id);
                    if (!$section)
                        return response()->json(['message' => 'No section record found with ID ' . $area_id . '.'], Response::HTTP_NOT_FOUND);
                    break;
                case 'unit':
                    $key = 'unit_id';
                    $unit = Unit::find($area_id);
                    if (!$unit)
                        return response()->json(['message' => 'No unit record found with ID ' . $area_id . '.'], Response::HTTP_NOT_FOUND);
                    break;
                default:
                    return response()->json(['message' => 'In valid area ID.'], Response::HTTP_BAD_REQUEST);
            }

            $cleanData[$key] = strip_tags($request->area_id);

            $key_list = ['division_id', 'department_id', 'section_id', 'unit_id'];

            foreach ($key_list as $value) {
                if ($value === $key)
                    continue;
                $cleanData[$value] = null;
            }

            $plantilla_numbers = PlantillaNumber::where('plantilla_id', $plantilla->id)
                ->where('is_vacant', 1)->limit($total_of_plantilla_to_distribute)->get();

            $plantilla_result = [];

            foreach ($plantilla_numbers as $plantilla_number) {
                $cleanData['plantilla_number_id'] = $plantilla_number->id;
                $plantilla_assign_area = PlantillaAssignedArea::create($cleanData);
                $plantilla_number->update(['assigned_at' => now(), 'is_vacant' => 1]);
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
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
            }

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

    public function destroy($id, Request $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
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

    public function destroyPlantillaNumber($id, Request $request)
    {
        try {
            $user = $request->user;
            $cleanData['pin'] = strip_tags($request->password);

            if ($user['authorization_pin'] !== $cleanData['pin']) {
                return response()->json(['message' => "Request rejected invalid approval pin."], Response::HTTP_FORBIDDEN);
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