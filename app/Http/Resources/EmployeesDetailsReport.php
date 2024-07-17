<?php

namespace App\Http\Resources;

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
        $employee_profile = $this->employeeProfile ?? $this;
        $personal_information = $employee_profile->personalInformation;
        $assigned_area = $employee_profile->assignedArea;
        $employment_type = $employee_profile->employmentType;

        $name = $personal_information->fullName();
        $area_details = $assigned_area ? $assigned_area->findDetails() : null;
        $designation = $assigned_area->plantilla_id === null ? $assigned_area->designation : $assigned_area->plantilla->designation;
        $employment_status = $employment_type->name;

        $data = [
            'id' => $this->id,
            'employee_id' => $employee_profile->employee_id,
            'name' => $name,
            'blood_type' => $personal_information->blood_type,
            'civil_status' => $personal_information->civil_status,
            'area' => $area_details,
            'designation' => $designation,
            'employment_status' => $employment_status,
        ];

        // Conditionally add service length data if it exists
        if (isset($this->service_length)) {
            $data = array_merge($data, [
                'date_hired' => $employee_profile->date_hired,
                'total_govt_months' => $this->service_length['total_govt_months'],
                'total_govt_years' => $this->service_length['total_govt_years'],
                'total_govt_months_with_zcmc' => $this->service_length['total_govt_months_with_zcmc'],
                'total_govt_years_with_zcmc' => $this->service_length['total_govt_years_with_zcmc'],
                'total_months_zcmc_regular' => $this->service_length['total_months_zcmc_regular'],
                'total_years_zcmc_regular' => $this->service_length['total_years_zcmc_regular'],
                'total_months_zcmc_as_jo' => $this->service_length['total_months_zcmc_as_jo'],
                'total_years_zcmc_as_jo' => $this->service_length['total_years_zcmc_as_jo'],
            ]);
        }

        return $data;
    }
}
