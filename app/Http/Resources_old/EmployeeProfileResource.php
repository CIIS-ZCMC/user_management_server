<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

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
        $name = $personal_information->employeeName();
        $assigned_area = $this->assignedArea;
        $area_details = $assigned_area ? $assigned_area->findDetails() : null;
        $area = $area_details;
        $is_regular_employee = $assigned_area->plantilla_number_id === null ? false : true;
        $designation = $assigned_area->plantilla_id === null ? $assigned_area->designation : $assigned_area->plantilla->designation;
        $designation_name = $designation->name;

        $designation_code = $designation->code;

        $employment_type = $this->employmentType;
        $employment_status = $employment_type->name;

        $account_status = $this->deactivated_at === null ? 'Active' : $this->deactivated_at;

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'name' => $name,
            'profile_url' =>  config("app.server_domain") . "/photo/profiles/" . $this->profile_url,
            'area' => $area,
            'is_regular_employee' => $is_regular_employee,
            'designation' => $designation_name,
            'designation_code' => $designation_code,
            'date_hired' => $this->date_hired,
            'employment_status' => $employment_status,
            'account_status' => $account_status,
            'renewal_date' => $this->renewal === null? "N/A": $this->renewal
        ];
    }
}
