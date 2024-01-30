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

        $requesting_officer = $employee;
        $approving_officer = $employee;

        return [
            'id'                    => $this->id,
            'employee'              => $employee,
            'requesting_officer'    => $this->requesting_officer,
            'approving_officer'     => $this->approving_officer,
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
