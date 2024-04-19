<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeOvertimeCreditResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'employee_profile_id' => $this->employee_profile_id,
            'name' => $this->employeeProfile->personalInformation->name(),
            'earned_credit_by_hour' => $this->earned_credit_by_hour,
            'used_credit_by_hour' => $this->used_credit_by_hour,
            'max_credit_monthly' => $this->max_credit_monthly,
            'max_credit_annual' => $this->max_credit_annual,
            'valid_until' => $this->valid_until,
            'is_expired' => $this->is_expired,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'logs' => EmployeeOvertimeCreditLogResource::collection($this->logs),
        ];
    }
}
