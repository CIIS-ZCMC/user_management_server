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
            'status'                => $this->status,
            'reason'                => $this->reason,
            'schedule'              => $this->schedule,
            'requested_employee'    => $this->requestedEmployee ? new EmployeeProfileResource($this->requestedEmployee) : null,
            'reliever_employee'     => $this->relieverEmployee ? new EmployeeProfileResource($this->relieverEmployee) : null,
            'approve_by'            => $this->approval ? EmployeeProfileResource::collection($this->approval) : [],
            'deleted_at'            => (string) $this->deleted_at,
            'created_at'            => (string) $this->created_at,
            'updated_at'            => (string) $this->updated_at,
        ];
    }
}
