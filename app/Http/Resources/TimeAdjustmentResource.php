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
        return [
            'id'                    => $this->id,
            'employee_profile_id'   => $this->employee,
            'daily_time_record_id'  => $this->daily_time_record,
            'recommended_by'        => $this->recommended_by,
            'approve_by'            => $this->approve_by,
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
