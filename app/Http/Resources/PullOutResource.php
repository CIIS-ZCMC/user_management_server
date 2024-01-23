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
        return [
            'id'            => $this->id,
            'employee'      => $this->employee ? EmployeeProfileResource::collection($this->employee) : [],
            'requested_by'  => $this->requested_by,
            'approve_by'    => $this->approve_by,
            'pull_out_date' => $this->pull_out_date,
            'approval_date' => $this->approval_date,
            'status'        => $this->status,
            'reason'        => $this->reason,
            'deleted_at'    => (string) $this->deleted_at,
            'created_at'    => (string) $this->created_at,
            'updated_at'    => (string) $this->updated_at,
        ];
    }
}
