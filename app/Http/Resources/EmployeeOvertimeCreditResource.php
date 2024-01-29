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
        $employeeProfile = [
            'name'=> $this->employeeProfile->personalInformation->name(),
            'profile_url' => $this->profile_url,
            'designation' => [
                'name' => $this->employeeProfile->assignedArea->designation->name,
                'code' => $this->employeeProfile->assignedArea->designation->code,
            ],
            'area' => $this->employeeProfile->assignedArea->findDetails()['details']->name,
        ];

        return [
            'id' => $this->id,
            'employee_profile_id' => $employeeProfile,
            'earned_credit_by_hour' => $this->earned_credit_by_hour,
            'used_credit_by_hour' => $this->used_credit_by_hour,
            'max_credit_monthly' => $this->max_credit_monthly,
            'max_credit_annual' => $this->max_credit_annual,
        ];
    }
}
