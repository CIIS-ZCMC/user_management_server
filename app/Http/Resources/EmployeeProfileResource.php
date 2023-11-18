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
        $nameExtension = $personal_information === null?'':' '.$personal_information->name_extension.' ';
        $nameTitle = $personal_information===null?'': ' '.$personal_information->name_title;

        $name = $personal_information->name;
        $assigned_area = $this->assignedArea;
        $area_details = $assigned_area->findDetails();
        $area_code = $area_details['code'];
        $area_name = $area_details['name'];
        $is_regular_employee = $assigned_area->plantilla_id === null? false: true;
        $designation = $assigned_area->plantilla_id===null?$assigned_area->designation:$assigned_area->plantilla->designation;
        $designation = $designation->name;

        $employment_type = $this->employmentType;
        $employment_status = $employment_type->name;

        $account_status = $this->deactivated_at === null? 'Active':$this->deactivated_at;

        return [
            'employee_id' => $this->employee_id,
            'name' => $name,
            'profile_url' => $this->profile_url,
            'area_code' => $area_code,
            'area_name' => $area_name,
            'is_regular_employee' => $is_regular_employee,
            'designation' => $designation,
            'date_hired' => $this->date_hired,
            'employment_status' => $employment_status,
            'account_status' => $account_status
        ];
    }
}
