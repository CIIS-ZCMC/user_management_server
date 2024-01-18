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
            'date'          => $this->date,
            'reason'        => $this->reason,
            'status'        => $this->status,
            'employee'      => $this->employee ? new EmployeeProfileResource($this->employee) : null,
            'requested_by'  => $this->requested_by,
            'approve_by'    => $this->approve_by,
            'deleted_at'    => (string) $this->deleted_at,
            'created_at'    => (string) $this->created_at,
            'updated_at'    => (string) $this->updated_at,
        ];
    }
}
