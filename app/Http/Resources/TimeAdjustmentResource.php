<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

class TimeAdjustmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee = [
            'name' => $this->employee->personalInformation->name(),
            'profile_url' => Cache::get("server_domain") . "/photo/profiles/" . $this->employee->profile_url,
            'designation' => [
                'name' => $this->employee->assignedArea->designation->name,
                'code' => $this->employee->assignedArea->designation->code,
            ],
            'area' => $this->employee->assignedArea->findDetails()['details']->name,
        ];

        // $recommending_officer = [
        //     'name' => $this->recommendingOfficer->personalInformation->name(),
        //     'profile_url' => Cache::get("server_domain") . "/photo/profiles/" . $this->recommendingOfficer->profile_url,
        //     'designation' => [
        //         'name' => $this->recommendingOfficer->assignedArea->designation->name,
        //         'code' => $this->recommendingOfficer->assignedArea->designation->code,
        //     ],
        //     'area' => $this->recommendingOfficer->assignedArea->findDetails()['details']->name,
        // ];

        $approving_officer = [
            'name' => $this->approvingOfficer->personalInformation->name(),
            'profile_url' => Cache::get("server_domain") . "/photo/profiles/" . $this->approvingOfficer->profile_url,
            'designation' => [
                'name' => $this->approvingOfficer->assignedArea->designation->name,
                'code' => $this->approvingOfficer->assignedArea->designation->code,
            ],
            'area' => $this->approvingOfficer->assignedArea->findDetails()['details']->name,
        ];

        return [
            'id' => $this->id,
            'daily_time_record' => $this->dailyTimeRecord ? new DailyTimeRecordResource($this->dailyTimeRecord) : null,
            'employee_profile' => $employee,
            // 'recommending_officer' => $recommending_officer,
            'approving_officer' => $approving_officer,
            'approval_date' => $this->approval_date,
            'dtr_date' => $this->date,
            'first_in' => $this->first_in,
            'first_out' => $this->first_out,
            'second_in' => $this->second_in,
            'second_out' => $this->second_out,
            'remarks' => $this->remarks,
            'status' => $this->status,
            'deleted_at' => (string) $this->deleted_at,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,

        ];
    }
}
