<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

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
                'profile_url'=>config("app.server_domain") . "/photo/profiles/" . $this->employeeProfile->profile_url,
            ],
            "leave_type" => $this->leaveType,
            'reason' => $this->reason,
            'attachment' => [
                'attachment' => $this->attachment,
                'attachment_size' => $this->attachment_size,
                'attachment_path' => config("app.server_domain")."/leave_monetization/".$this->attachment_path,
            ],
            'credit_value' => $this->credit_value,
            'is_qualified' => $this->is_qualified,
            'status' => $this->status,
            'remarks'=> $this->remarks,
            "hrmo_officer" => [
                "employee_id" => $this->hrmoOfficer->employee_id,
                "name" => $this->hrmoOfficer->personalInformation->name(),
                "designation" => $this->hrmoOfficer->assignedArea->designation->name,
                "designation_code" => $this->hrmoOfficer->assignedArea->designation->code,
                "profile_url" => config("app.server_domain") . "/photo/profiles/" . $this->hrmoOfficer->profile_url,
            ],
            "recommending_officer" => [
                "employee_id" => $this->recommendingOfficer->employee_id,
                "name" => $this->recommendingOfficer->personalInformation->name(),
                "designation" => $this->recommendingOfficer->assignedArea->designation->name,
                "designation_code" => $this->recommendingOfficer->assignedArea->designation->code,
                "profile_url" => config("app.server_domain") . "/photo/profiles/" . $this->recommendingOfficer->profile_url,
            ],
            "approving_officer" => [
                "employee_id" => $this->approvingOfficer->employee_id,
                "name" => $this->approvingOfficer->personalInformation->name(),
                "designation" => $this->approvingOfficer->assignedArea->designation->name,
                "designation_code" => $this->approvingOfficer->assignedArea->designation->code,
                "profile_url" => config("app.server_domain") . "/photo/profiles/" . $this->approvingOfficer->profile_url,
            ],
            'logs' => $this->logs ? LeaveApplicationLog::collection($this->logs):[],
            'created_at' => $this->created_at,
            
        ];
    }
}
