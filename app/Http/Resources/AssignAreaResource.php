<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignAreaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $area_code = null;
        $area_name = null;
        $area_id = null;

        $employee_profile = $this->employeeProfile;
        $employee_id = $employee_profile['employee_id'];

        if($this->division_id !== null){
            $area_code = 'Division';
            $area_name = $this->division->name;
            $area_id = $this->division->area_id;
        }
        
        if($this->department_id !== null){
            $area_code = 'Department';
            $area_name = $this->department->name;
            $area_id = $this->department->area_id;
        }
        
        if($this->section_id !== null){
            $area_code = 'Section';
            $area_name = $this->section->name;
            $area_id = $this->section->area_id;
        }
        
        if($this->unit_id !== null){
            $area_code = 'Unit';
            $area_name = $this->unit->name;
            $area_id = $this->unit->area_id;
        }

        $is_reqular_employee = $this->plantilla_id !== null? true:false;
        $designation = $this->plantilla === null? $this->designation->name:$this->plantilla->designation->name;


        return [
            'id' => $this->id,
            'employee_id' => $employee_id,
            'area_code' => $area_code,
            'area_id' => $this->area_id,
            'area_name' => $area_name,
            'is_regular_employee' => $is_reqular_employee,
            'designation' => $designation
        ];
    }
}
