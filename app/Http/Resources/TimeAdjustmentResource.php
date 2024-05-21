<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
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
            'dtr_date' => $this->date,
            'first_in' => Carbon::parse($this->first_in)->format('H:i A'),
            'first_out' => Carbon::parse($this->first_out)->format('H:i A'),
            'second_in' => Carbon::parse($this->second_in)->format('H:i A'),
            'second_out' => Carbon::parse($this->second_out)->format('H:i A'),
            'remarks' => $this->remarks,
            'file_name' => $this->attachment,
            'file_path' => config('app.server_domain') . "/time_adjustment/",
            'file_size' => $this->filesize(),
            'attachment' => config('app.server_domain') . "/time_adjustment/" . $this->attachment,
            'status' => $this->status,
            'approval_date' => $this->approval_date,
            'daily_time_record' => $this->dailyTimeRecord ? new DailyTimeRecordResource($this->dailyTimeRecord) : null,
            'employee_profile' => $this->employee ? $employee : [],
            // 'recommending_officer' => $this->recommendingOfficer ? $recommending_officer : [],
            'approving_officer' => $this->approvingOfficer ? $approving_officer : [],
            'deleted_at' => (string) $this->deleted_at,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
        ];
    }
}
