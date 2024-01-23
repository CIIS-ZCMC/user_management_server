<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $personal_information = $this->personalInformation;

        $name = $personal_information->name();
        $assigned_area = $this->assignedArea;
        $area_details = $assigned_area->findDetails();
        $area = $area_details;
        $is_regular_employee = $assigned_area->plantilla_id === null? false: true;
        $designation = $assigned_area->plantilla_id===null?$assigned_area->designation:$assigned_area->plantilla->designation;
        $designation = $designation->name;

        $employment_type = $this->employmentType;
        $employment_status = $employment_type->name;

        $account_status = $this->deactivated_at === null? 'Active':$this->deactivated_at;

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'name' => $name,
            'profile_url' => $this->profile_url,
            'area' => $area,
            'is_regular_employee' => $is_regular_employee,
            'designation' => $designation,
            'designation_code' => $designation->code,
            'date_hired' => $this->date_hired,
            'employment_status' => $employment_status,
            'account_status' => $account_status
        ];
    }
}
