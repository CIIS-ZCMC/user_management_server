<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CtoApplicationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            "employee_profile" => [
                "employee_account" => $this->employeeProfile,
                "personal_information" => $this->employeeProfile->personalInformation
            ],
            'date' => $this->date,
            'time_from' => $this->time_from,
            'time_to' => $this->time_to,
            'file_date' => $this->file_date,
            'remarks' => $this->remarks,
            'status' => $this->status,
            'purpose' => $this->purpose,
            "hrmo_officer" => [
                "employee_id" => $this->hrmoOfficer->employee_id,
                "hrmo_full_name" => $this->hrmoOfficer->personalInformation->fullName(),
                "designation" => $this->hrmoOfficer->designation->name,
                "designation_code" => $this->hrmoOfficer->designation->code
            ],
            "recommending_officer" => [
                "employee_id" => $this->recommendingOfficer->employee_id,
                "hrmo_full_name" => $this->recommendingOfficer->personalInformation->fullName(),
                "designation" => $this->recommendingOfficer->designation->name,
                "designation_code" => $this->recommendingOfficer->designation->code
            ],
            "approving_officer" => [
                "employee_id" => $this->approvingOfficer->employee_id,
                "hrmo_full_name" => $this->approvingOfficer->personalInformation->fullName(),
                "designation" => $this->approvingOfficer->designation->name,
                "designation_code" => $this->approvingOfficer->designation->code
            ],
            'logs' => $this->logs
        ];
    }
}
