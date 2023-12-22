<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileUpdateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee_profile = $this->employee;
        $employee_id = $employee_profile->employee_id;
        $name = $employee_profile->name;

        $employee_approve_by = $this->approvedBy;
        $approved_by = $employee_approve_by->name;

        $request_details = $this->requestDetails;

        return [
            'id' => $this->id,
            'employee_id' => $employee_id,
            'name' => $name,
            'approved_by' => $approved_by,
            'request_at' => $this->request_at,
            'approved_at' => $this->approved_at,
            'request_details' => RequestDetailResource::collection($request_details)
        ];
    }
}
