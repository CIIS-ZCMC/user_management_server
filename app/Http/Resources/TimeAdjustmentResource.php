<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'name'=> $this->employee->personalInformation->name(),
            'profile_url' => $this->profile_url,
            'designation' => [
                'name' => $this->employee->assignedArea->designation->name,
                'code' => $this->employee->assignedArea->designation->code,
            ],
            'area' => $this->employee->assignedArea->findDetails()['details']->name,
        ];

        $recommendedBy = $employee;
        $approveBy = $employee;


        $daily_time_record = [
            'id'=> $this->id,
            'biometric_id'=> $this->biometric_id,
            'dtr_date' => $this->dtr_date,
        ];

        return [
            'id'                    => $this->id,
            'daily_time_record'     => $daily_time_record,
            'employee_profile'      => $employee,
            'recommended_by'        => $recommendedBy,
            'approve_by'            => $approveBy,
            'approval_date'         => $this->approval_date,
            'first_in'              => $this->first_in,
            'first_out'             => $this->first_out,
            'second_in'             => $this->second_in,
            'second_out'            => $this->second_out,
            'remarks'               => $this->remarks,
            'status'                => $this->status,
            'deleted_at'            => (string) $this->deleted_at,
            'created_at'            => (string) $this->created_at,
            'updated_at'            => (string) $this->updated_at,

        ];
    }
}
