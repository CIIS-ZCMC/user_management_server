<?php

namespace App\Http\Resources;

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeesDetailsReportByAddress extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee_profile = $this->employeeProfile ?? $this;
        $personal_information = $employee_profile->personalInformation;
        $assigned_area = $employee_profile->assignedArea;
        $employment_type = $employee_profile->employmentType;

        $name = $personal_information->fullName();
        $area_details = $assigned_area ? $assigned_area->findDetails() : null;
        $designation = $assigned_area->plantilla_id === null ? $assigned_area->designation : $assigned_area->plantilla->designation;
        $employment_status = $employment_type->name;

        return [
            'id' => $this->id,
            'employee_id' => $employee_profile->employee_id,
            'name' => $name,
            'blood_type' => $personal_information->blood_type,
            'civil_status' => $personal_information->civil_status,
            'address' => $personal_information->addresses[0]->address,
            'area' => $area_details,
            'designation' => $designation,
            'employment_status' => $employment_status,
        ];
    }
}
