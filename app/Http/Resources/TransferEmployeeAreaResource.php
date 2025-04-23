<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferEmployeeAreaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $assigned_area = $this->assignedArea;

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'assign_area_id' => $assigned_area['id'], 
            'plantilla_id' => $assigned_area['plantilla_id'],
            'plantilla_number_id' => $assigned_area['plantilla_number_id'],
            'division_id' => $assigned_area['division_id'],
            'department_id' => $assigned_area['department_id'],
            'section_id' => $assigned_area['section_id'],
            'unit_id' => $assigned_area['unit_id']
        ];
    }
}
