<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeEmploymentTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

    
        $personal_information = $this->personalInformation;
        $name = $personal_information->fullName();
        $assigned_area =$this->assignedArea;
        $area_details = $assigned_area ? $assigned_area->findDetails() : null;
        $area = $area_details;
        $designation = $assigned_area->plantilla_id === null ? $assigned_area->designation : $assigned_area->plantilla->designation;
        $designation_name = $designation->name;
        $employment_type = $this->employmentType;
        $employment_status = $employment_type->name;

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'name' => $name,
            'employment_type' => $personal_information->blood_type,
            'area' => $area,
            'designation' => $designation_name,
            'employment_status' => $employment_status,
        ];
        

    }
}
