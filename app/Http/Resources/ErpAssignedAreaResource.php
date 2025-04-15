<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErpAssignedAreaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'employee_profile_id' => $this->employee_profile_id,
            'designation_id' => $this->designation_id ?? null,
            'division_id' => $this->division_id ?? null,
            'department_id' => $this->department_id ?? null,
            'section_id' => $this->section_id ?? null,
            'unit_id' => $this->unit_id ?? null,
        ];
    }
}
