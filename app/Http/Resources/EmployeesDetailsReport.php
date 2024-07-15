<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeesDetailsReport extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        if ($this->personalInformation === NULL) {

            $employee_profile = $this->employeeProfile;
            $personal_information = $employee_profile->personalInformation;
            $name = $personal_information->fullName();
            $assigned_area = $employee_profile->assignedArea;
            $area_details = $assigned_area ? $assigned_area->findDetails() : null;
            $area = $area_details;
            $designation = $assigned_area->plantilla_id === null ? $assigned_area->designation : $assigned_area->plantilla->designation;
            $employment_type = $employee_profile->employmentType;
            $employment_status = $employment_type->name;

            return [
                'id' => $this->id,
                'employee_id' => $this->employee_id,
                'name' => $name,
                'blood_type' => $personal_information->blood_type,
                'civil_status' => $personal_information->civil_status,
                'area' => $area,
                'designation' => $designation,
                'employment_status' => $employment_status,
            ];
        }


        $personal_information = $this->personalInformation;
        $name = $personal_information->fullName();
        $assigned_area = $this->assignedArea;
        $area_details = $assigned_area ? $assigned_area->findDetails() : null;
        $area = $area_details;
        $designation = $assigned_area->plantilla_id === null ? $assigned_area->designation : $assigned_area->plantilla->designation;
        $employment_type = $this->employmentType;
        $employment_status = $employment_type->name;

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'name' => $name,
            'blood_type' => $personal_information->blood_type,
            'civil_status' => $personal_information->civil_status,
            'area' => $area,
            'designation' => $designation,
            'employment_status' => $employment_status,
        ];
    }
}
