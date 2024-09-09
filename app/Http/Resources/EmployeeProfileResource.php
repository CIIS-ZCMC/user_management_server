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
        $name = $personal_information ? $personal_information->employeeName() : 'N/A';

        $assigned_area = $this->assignedArea;
        if ($assigned_area) {
            $area_details = $assigned_area->findDetails();
            $area = $area_details;
            $is_regular_employee = $assigned_area->plantilla_number_id === null ? false : true;
            $designation = $assigned_area->plantilla_id === null ? $assigned_area->designation : $assigned_area->plantilla->designation;
            $designation_name = $designation ? $designation->name : 'N/A';
            $designation_code = $designation ? $designation->code : 'N/A';
        } else {
            $area = null;
            $is_regular_employee = false;
            $designation_name = 'N/A';
            $designation_code = 'N/A';
        }

        $employment_type = $this->employmentType;
        $employment_status = $employment_type ? $employment_type->name : 'N/A';

        $account_status = $this->deactivated_at === null ? 'Active' : $this->deactivated_at;

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'name' => $name,
            'profile_url' => config("app.server_domain") . "/photo/profiles/" . $this->profile_url,
            'area' => $area,
            'is_regular_employee' => $is_regular_employee,
            'designation' => $designation_name,
            'designation_code' => $designation_code,
            'date_hired' => $this->date_hired,
            'employment_status' => $employment_status,
            'account_status' => $account_status,
            'renewal_date' => $this->renewal === null ? "N/A" : $this->renewal
        ];
    }

}
