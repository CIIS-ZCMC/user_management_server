<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeDutyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $requestedEmployee = [
            'name'=> $this->employee->personalInformation->name(),
            'profile_url' => $this->profile_url,
            'designation' => [
                'name' => $this->employee->assignedArea->designation->name,
                'code' => $this->employee->assignedArea->designation->code,
            ],
            'area' => $this->employee->assignedArea->findDetails()['details']->name,
        ];

        $relieverEmployee = $requestedEmployee;
        $approveBy = $requestedEmployee;

        return [
            'id'                    => $this->id,
            'status'                => $this->status,
            'reason'                => $this->reason,
            'schedule'              => $this->schedule,
            'requested_employee'    => $requestedEmployee,
            'reliever_employee'     => $relieverEmployee,
            'approve_by'            => $approveBy,
            'deleted_at'            => (string) $this->deleted_at,
            'created_at'            => (string) $this->created_at,
            'updated_at'            => (string) $this->updated_at,
        ];
    }
}
