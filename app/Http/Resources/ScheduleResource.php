<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee = [
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
            'name' => $employee,
            'employee_id' => $this->employee_id,
            'biometric_id' => $this->biometric_id,
            'assigned_area' => $this->assignedArea,
            'schedule' => $this->schedule,
        ];
    }
}
