<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransferEmployeeAreaResource;
use App\Models\AssignAreaTrail;
use App\Models\Department;
use App\Models\Division;
use App\Models\EmployeeProfile;
use App\Models\PlantillaAssignedArea;
use App\Models\Section;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TransferEmployeeAreaController extends Controller
{
    public function index(Request $request)
    {
        $employee_ids = $request->employee_ids;

        $employees = EmployeeProfile::with(['assignedArea'])
            ->whereIn('employee_id',  $employee_ids)->get();
        
        return response()->json([
            'employees' => TransferEmployeeAreaResource::collection($employees),
            'message' => "Successfully retrieve employees"
        ], Response::HTTP_OK);
    }

    public function update(Request $request)
    {
        DB::beginTransaction();
        $employee_ids = $request->employee_ids;
        $area_id = $request->area_id;
        $employees = [];

        foreach($employee_ids as $employee_id){
            $sector = null;
    
            $employee = EmployeeProfile::where('employee_id', $employee_id)->first();
    
            if(!$employee){
                return response()->json(['message' => 'No employee found with given ID.'], Response::HTTP_NOT_FOUND);
            }
    
            $assigned_area = $employee->assignedArea;
    
            if($assigned_area->division_id !== null){
                $sector = "division";
                
                $is_exist = Division::find($area_id);
                if(!$is_exist){
                    DB::rollBack();
                    return response()->json(['message' => "No division record base on ID given."], Response::HTTP_NOT_FOUND);
                }
            }
    
            if($assigned_area->department_id !== null){
                $sector = "department";
                
                $is_exist = Department::find($area_id);
                if(!$is_exist){
                    DB::rollBack();
                    return response()->json(['message' => "No department record base on ID given."], Response::HTTP_NOT_FOUND);
                }
            }
    
            if($assigned_area->section_id !== null){
                $sector = "section";
                
                $is_exist = Section::find($area_id);
                if(!$is_exist){
                    DB::rollBack();
                    return response()->json(['message' => "No section record base on ID given."], Response::HTTP_NOT_FOUND);
                }
            }
    
            if($assigned_area->unit_id !== null){
                $sector = "unit";
                
                $is_exist = Unit::find($area_id);
                if(!$is_exist){
                    DB::rollBack();
                    return response()->json(['message' => "No unit record base on ID given."], Response::HTTP_NOT_FOUND);
                }
            }
    
            $new_area = [$sector.'_id' => $area_id];
    
            try{
                $assigned_area->update($new_area);
                
                if($assigned_area->plantilla_number_id !== null){
                    $plantilla_assign_area = PlantillaAssignedArea::where('plantilla_number_id',$assigned_area->plantilla_number_id)->first();
                    $plantilla_assign_area->update($new_area);
                }
                
                $employees[] = $employee;
            }catch(\Throwable $th){
                DB::rollBack();
                return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        DB::commit();

        return response()->json([
            'updated_employee_details' => TransferEmployeeAreaResource::collection($employees),
            'message' => "Successfully transfer employees to new area." 
        ], Response::HTTP_OK);
    }

    public function destroy(Request $request)
    {
        $sector = $request->sector;
        $area_id = $request->area_id;

        if($sector === 'division'){
            $division = Division::find($area_id);

            if(count($division->assignArea) > 0){
                return response()->json(['message' => "Some data in assign area is using this division with ID ".$area_id], Response::HTTP_FORBIDDEN);
            }
    
            if(count($division->assignedAreaTrails) > 0){
                AssignAreaTrail::where('division_id', $division->id)->delete();
            }

            if(count($division->chiefTrails) > 0){
                return response()->json(['message' => "Some data in chief trails is using this division with ID ".$area_id], Response::HTTP_FORBIDDEN);
            }
    
            if(count($division->oicTrails) > 0){
                return response()->json(['message' => "Some data in OIC trails is using this division with ID ".$area_id], Response::HTTP_FORBIDDEN);
            }
    
            if(count($division->departments) > 0){
                return response()->json(['message' => "Some data in departments is using this division with ID ".$area_id], Response::HTTP_FORBIDDEN);
            }
    
            if(count($division->sections) > 0){
                return response()->json(['message' => "Some data in sections is using this division with ID ".$area_id], Response::HTTP_FORBIDDEN);
            }
    
            if(count($division->units) > 0){
                return response()->json(['message' => "Some data in units is using this division with ID ".$area_id], Response::HTTP_FORBIDDEN);
            }
    
            if(count($division->plantillaAssignAreas) > 0){
                return response()->json(['message' => "Some data in plantilla assign area is using this division with ID ".$area_id], Response::HTTP_FORBIDDEN);
            }

            $division->delete();
        
            return response()->json([
                'message' => 'Successfully delete division.'
            ], Response::HTTP_OK); 
        }

        if($sector === 'department'){
            $department = Department::find($area_id);

            if(count($department->assignArea) > 0){
                return response()->json(['message' => "Some data in assign area is using this department with ID ".$area_id], Response::HTTP_FORBIDDEN);
            }
    
            if(count($department->assignAreaTrails) > 0){
                AssignAreaTrail::where('department_id', $department->id)->delete();
            }

            if(count($department->headTrails) > 0){
                return response()->json(['message' => "Some data in head trails is using this department with ID ".$area_id], Response::HTTP_FORBIDDEN);
            }
    
            if(count($department->oicTrails) > 0){
                return response()->json(['message' => "Some data in OIC trails is using this department with ID ".$area_id], Response::HTTP_FORBIDDEN);
            }
    
            if(count($department->sections) > 0){
                return response()->json(['message' => "Some data in sections is using this department with ID ".$area_id], Response::HTTP_FORBIDDEN);
            }
    
            if(count($department->plantillaAssignAreas) > 0){
                return response()->json(['message' => "Some data in plantilla assign area is using this department with ID ".$area_id], Response::HTTP_FORBIDDEN);
            }

            $department->delete();
        
            return response()->json([
                'message' => 'Successfully delete department.'
            ], Response::HTTP_OK); 
        }

        if($sector === 'section'){
            $section = Section::find($area_id);

            if(count($section->assignArea) > 0){
                return response()->json(['message' => "Some data in assign area is using this section with ID ".$area_id], Response::HTTP_FORBIDDEN);
            }
    
            if(count($section->assignedAreaTrails) > 0){
                AssignAreaTrail::where('section_id', $section->id)->delete();
            }
    
            if(count($section->supervisorTrails) > 0){
                return response()->json(['message' => "Some data in superviser trails is using this section with ID ".$area_id], Response::HTTP_FORBIDDEN);
            }
    
            if(count($section->oicTrails) > 0){
                return response()->json(['message' => "Some data in OIC trails is using this section with ID ".$area_id], Response::HTTP_FORBIDDEN);
            }
    
            if(count($section->units) > 0){
                return response()->json(['message' => "Some data in units is using this section with ID ".$area_id], Response::HTTP_FORBIDDEN);
            }
    
            if(count($section->plantillaAssignAreas) > 0){
                return response()->json(['message' => "Some data in plantilla assign area is using this section with ID ".$area_id], Response::HTTP_FORBIDDEN);
            }

            $section->delete();
        
            return response()->json([
                'message' => 'Successfully delete section.'
            ], Response::HTTP_OK);      
        }

        $unit = Unit::find($area_id);

        if(count($unit->assignArea) > 0){
            return response()->json(['message' => "Some data in assign area is using this unit with ID ".$area_id], Response::HTTP_FORBIDDEN);
        }

        if(count($unit->assignedAreaTrails) > 0){
            AssignAreaTrail::where('unit_id', $unit->id)->delete();
        }

        if(count($unit->headTrails) > 0){
            return response()->json(['message' => "Some data in head trails is using this unit with ID ".$area_id], Response::HTTP_FORBIDDEN);
        }

        if(count($unit->oicTrails) > 0){
            return response()->json(['message' => "Some data in OIC trails is using this unit with ID ".$area_id], Response::HTTP_FORBIDDEN);
        }
    
        if(count($unit->plantillaAssignAreas) > 0){
            return response()->json(['message' => "Some data in plantilla assign area is using this unit with ID ".$area_id], Response::HTTP_FORBIDDEN);
        }

        $unit->delete();
        
        return response()->json(['message' => 'Successfully delete unit.'], Response::HTTP_OK);
    }
}
