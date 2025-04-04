<?php

namespace App\Http\Resources;

use App\Models\EmployeeLeaveCreditLogs;
use App\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeLeaveCreditResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            'employee_profile_id' => $this->employee_profile_id,
            'name' => $this->employeeProfile->personalInformation->name(),
            'leave_type' => $this->leaveType,
            'total_leave_credits' => (float)$this->total_leave_credits,
            'used_leave_credits' => (float)$this->used_leave_credits,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'logs' => $this->logs ? EmployeeLeaveCreditLogResource::collection($this->logs) : null,
        ];
    }
}
