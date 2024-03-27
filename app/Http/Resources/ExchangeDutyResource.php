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
        $requester = [
            'id' => $this->requestedEmployee->id,
            'name' => $this->requestedEmployee->personalInformation->name(),
            'profile_url' => env('SERVER_DOMAIN') . "/photo/profiles/" . $this->relieverEmployee->profile_url,
            'designation' => $this->requestedEmployee->assignedArea->designation->name,
            'area' => $this->requestedEmployee->assignedArea->findDetails(),
        ];

        $reliever = [
            'id' => $this->relieverEmployee->id,
            'name' => $this->relieverEmployee->personalInformation->name(),
            'profile_url' => env('SERVER_DOMAIN') . "/photo/profiles/" . $this->relieverEmployee->profile_url,
            'designation' => $this->relieverEmployee->assignedArea->designation->name,
            'area' => $this->relieverEmployee->assignedArea->findDetails(),
        ];

        $approval = [
            'id' => $this->approvingEmployee->id,
            'name' => $this->approvingEmployee->personalInformation->name(),
            'profile_url' => env('SERVER_DOMAIN') . "/photo/profiles/" . $this->approvingEmployee->profile_url,
            'designation' => $this->approvingEmployee->assignedArea->designation->name,
            'area' => $this->approvingEmployee->assignedArea->findDetails(),
        ];

        return [
            'id' => $this->id,
            'requested_schedule_to_swap' => $this->schedule->findScheduleDetails($this->requestedEmployee->id, $this->requested_date_to_swap),
            'requested_schedule_to_duty' => $this->schedule->findScheduleDetails($this->relieverEmployee->id, $this->requested_date_to_duty),
            'requested_employee' => $requester,
            'reliever_employee' => $reliever,
            'approve_by' => $approval,
            'status' => $this->status,
            'reason' => $this->reason,
            'created_at' => $this->created_at
        ];
    }
}
