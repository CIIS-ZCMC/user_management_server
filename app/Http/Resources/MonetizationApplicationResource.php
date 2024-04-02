<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonetizationApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $area = $this->employeeProfile->assignedArea->findDetails();
        return [
            'id' => $this->id,
            "employee_profile" => [
                'employee_id' => $this->employeeProfile->id,
                'name' => $this->employeeProfile->personalInformation->name(),
                'designation_name' => $this->employeeProfile->assignedArea->designation->name,
                'designation_code' => $this->employeeProfile->assignedArea->designation->code,
                'area' => $area['details']->name,
                'area_code' => $area['details']->code,
                'area_sector' => $area['sector'],
                'profile_url'=>env('SERVER_DOMAIN') . "/photo/profiles/" . $this->employeeProfile->profile_url,
            ],
            "leave_type" => $this->leaveType,
            'reason' => $this->reason,
            'attachment' => env('SERVER_DOMAIN').$this->attachment,
            'credit_value' => $this->credit_value,
            'status' => $this->status,
            "hrmo_officer" => [
                "employee_id" => $this->hrmoOfficer->employee_id,
                "name" => $this->hrmoOfficer->personalInformation->name(),
                "designation" => $this->hrmoOfficer->assignedArea->designation->name,
                "designation_code" => $this->hrmoOfficer->assignedArea->designation->code,
                "profile_url" => env('SERVER_DOMAIN') . "/photo/profiles/" . $this->hrmoOfficer->profile_url,
            ],
            "recommending_officer" => [
                "employee_id" => $this->recommending->employee_id,
                "name" => $this->recommending->personalInformation->name(),
                "designation" => $this->recommending->assignedArea->designation->name,
                "designation_code" => $this->recommending->assignedArea->designation->code,
                "profile_url" => env('SERVER_DOMAIN') . "/photo/profiles/" . $this->recommending->profile_url,
            ],
            "approving_officer" => [
                "employee_id" => $this->approving->employee_id,
                "name" => $this->approving->personalInformation->name(),
                "designation" => $this->approving->assignedArea->designation->name,
                "designation_code" => $this->approving->assignedArea->designation->code,
                "profile_url" => env('SERVER_DOMAIN') . "/photo/profiles/" . $this->approving->profile_url,
            ],
            'logs' => $this->logs ? LeaveApplicationLog::collection($this->logs):[],
            'created_at' => $this->created_at,
            
        ];
    }
}
