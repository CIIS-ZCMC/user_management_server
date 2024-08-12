<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeesAssignedAreaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Ensure the relationships are loaded and not null
        $personal_information = $this->employeeProfile->personalInformation ?? null;
        $assigned_area = $this->employeeProfile->assignedArea ?? null;
        $employment_type = $this->employeeProfile->employmentType ?? null;

        if ($personal_information) {
            $name = $personal_information->employeeName();
        } else {
            $name = 'Unknown'; // Default name if personalInformation is null
        }

        if ($assigned_area) {
            $area_details = $assigned_area->findDetails();
            $area = $area_details;
            $is_regular_employee = $assigned_area->plantilla_number_id !== null;
            $designation = $assigned_area->plantilla_id === null ? $assigned_area->designation : $assigned_area->plantilla->designation;
            $designation_name = $designation->name ?? 'Unknown';
            $designation_code = $designation->code ?? 'N/A';
        } else {
            $area = 'Unknown';
            $is_regular_employee = false;
            $designation_name = 'Unknown';
            $designation_code = 'N/A';
        }

        if ($employment_type) {
            $employment_status = $employment_type->name;
        } else {
            $employment_status = 'Unknown';
        }

        $account_status = $this->employeeProfile->deactivated_at === null ? 'Active' : $this->employeeProfile->deactivated_at;

        return [
            'id' => $this->id,
            'employee_id' => $this->employeeProfile->employee_id,
            'name' => $name,
            'profile_url' => config("app.server_domain") . "/photo/profiles/" . $this->employeeProfile->profile_url,
            'area' => $area,
            'is_regular_employee' => $is_regular_employee,
            'designation' => $designation_name,
            'designation_code' => $designation_code,
            'date_hired' => $this->employeeProfile->date_hired,
            'employment_status' => $employment_status,
            'account_status' => $account_status,
            'renewal_date' => $this->employeeProfile->renewal === null ? "N/A" : $this->employeeProfile->renewal
        ];
    }
}
