<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PullOutResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee = [
            'name'=> $this->employee->personalInformation->name(),
            'profile_url' => $this->profile_url,
            'designation' => [
                'name' => $this->employee->assignedArea->designation->name,
                'code' => $this->employee->assignedArea->designation->code,
            ],
            'area' => $this->employee->assignedArea->findDetails()['details']->name,
        ];

        $requesting_officer = [
            'name'=> $this->requestedBy->personalInformation->name(),
            'profile_url' => $this->profile_url,
            'designation' => [
                'name' => $this->requestedBy->assignedArea->designation->name,
                'code' => $this->requestedBy->assignedArea->designation->code,
            ],
            'area' => $this->requestedBy->assignedArea->findDetails()['details']->name,
        ];

        $approving_officer = [
            'name'=> $this->approveBy->personalInformation->name(),
            'profile_url' => $this->profile_url,
            'designation' => [
                'name' => $this->approveBy->assignedArea->designation->name,
                'code' => $this->approveBy->assignedArea->designation->code,
            ],
            'area' => $this->approveBy->assignedArea->findDetails()['details']->name,
        ];


        return [
            'id'                    => $this->id,
            'employee_profile_id'   => $employee,
            'requesting_officer'    => $requesting_officer,
            'approving_officer'     => $approving_officer,
            'pull_out_date'         => $this->pull_out_date,
            'approval_date'         => $this->approval_date,
            'status'                => $this->status,
            'reason'                => $this->reason,
            'deleted_at'            => (string) $this->deleted_at,
            'created_at'            => (string) $this->created_at,
            'updated_at'            => (string) $this->updated_at,
        ];
    }
}
