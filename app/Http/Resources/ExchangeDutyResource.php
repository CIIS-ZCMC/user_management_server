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
        return [
            'id'                    => $this->id,
            'reason'                => $this->reason,
            'approve_by'            => $this->approve_by,
            'deleted_at'            => (string) $this->deleted_at,
            'created_at'            => (string) $this->created_at,
            'updated_at'            => (string) $this->updated_at,
            'requested_employee'    => $this->employee ? new EmployeeProfileResource($this->employee) : null,
            'reliever_employee'     => $this->employee ? new EmployeeProfileResource($this->employee) : null,
        ];
    }
}
