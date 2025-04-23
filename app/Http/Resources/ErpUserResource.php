<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErpUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'employee_profile_id' => $this->id,
            'employee_id' => $this->employee_id,
            'designation_id' => $this->assignedArea->designation_id ?? null,
            'division_id' => $this->assignedArea->division_id ?? null,
            'department_id' => $this->assignedArea->department_id ?? null,
            'section_id' => $this->assignedArea->section_id ?? null,
            'unit_id' => $this->assignedArea->unit_id ?? null,
            'name' => $this->personalInformation->employeeName(),
            'email' => $this->personalInformation->contact->email_address ?? null,
            'profile_url' => $this->profile_url ?? null,
        ];
    }
}
