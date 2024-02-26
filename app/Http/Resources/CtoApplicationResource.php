<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CtoApplicationResource extends JsonResource
{
    public function toArray($request)
    {
        $employeeProfile = [
            'name'=> $this->employeeProfile->personalInformation->name(),
            'profile_url' => $this->profile_url,
            'designation_name' => $this->employeeProfile->assignedArea->designation->name,
            'designation_code' => $this->employeeProfile->assignedArea->designation->code,
            'area' => $this->employeeProfile->assignedArea->findDetails()['details']->name,
        ];

        return [
            'id' => $this->id,
            "employee_profile" => $employeeProfile,
            'date' => $this->date,
            'applied_credits' => $this->applied_credits,
            'remarks' => $this->remarks,
            'status' => $this->status,
            'purpose' => $this->purpose,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            "recommending_officer" => [
                "employee_id" => $this->recommendingOfficer->employee_id,
                "name" => $this->recommendingOfficer->personalInformation->name(),
                "designation" => $this->recommendingOfficer->assignedArea->designation->name,
                "designation_code" => $this->recommendingOfficer->assignedArea->designation->code,
                "profile_url" => env('SERVER_DOMAIN') . "/photo/profiles/" . $this->recommendingOfficer->profile_url,
            ],
            "approving_officer" => [
                "employee_id" => $this->approvingOfficer->employee_id,
                "name" => $this->approvingOfficer->personalInformation->name(),
                "designation" => $this->approvingOfficer->assignedArea->designation->name,
                "designation_code" => $this->approvingOfficer->assignedArea->designation->code,
                "profile_url" => env('SERVER_DOMAIN') . "/photo/profiles/" . $this->recommendingOfficer->profile_url,
            ],
            'logs'  => $this->CtoApplicationLogs ? CtoApplicationLogResource::collection($this->CtoApplicationLogs) : [],
        ];
    }
}
