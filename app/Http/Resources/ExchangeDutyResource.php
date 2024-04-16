<?php

namespace App\Http\Resources;

use App\Models\ExchangeDutyLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

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
            'profile_url' => config("app.server_domain") . "/photo/profiles/" . $this->relieverEmployee->profile_url,
            'designation' => $this->requestedEmployee->assignedArea->designation->name,
            'area' => $this->requestedEmployee->assignedArea->findDetails(),
        ];

        $reliever = [
            'id' => $this->relieverEmployee->id,
            'name' => $this->relieverEmployee->personalInformation->name(),
            'profile_url' => config("app.server_domain") . "/photo/profiles/" . $this->relieverEmployee->profile_url,
            'designation' => $this->relieverEmployee->assignedArea->designation->name,
            'area' => $this->relieverEmployee->assignedArea->findDetails(),
        ];

        $approval = [
            'id' => $this->approvingOfficer->id,
            'name' => $this->approvingOfficer->personalInformation->name(),
            'profile_url' => config("app.server_domain") . "/photo/profiles/" . $this->approvingOfficer->profile_url,
            'designation' => $this->approvingOfficer->assignedArea->designation->name,
            'area' => $this->approvingOfficer->assignedArea->findDetails(),
        ];

        return [
            'id' => $this->id,
            'requested_schedule_to_swap' => $this->requested_date_to_swap,
            'requested_schedule_to_duty' => $this->requested_date_to_duty,
            'requested_schedule' => $this->findScheduleDetails($this->requestedSchedule->id),
            'reliever_schedule' => $this->findScheduleDetails($this->relieverSchedule->id),
            'requested_employee' => $requester,
            'reliever_employee' => $reliever,
            'approve_by' => $approval,
            'status' => $this->status,
            'reason' => $this->reason,
            'created_at' => $this->created_at,
            'logs' => $this->logs ? ExchangeDutyLogResource::collection($this->logs) : [],
        ];
    }
}
