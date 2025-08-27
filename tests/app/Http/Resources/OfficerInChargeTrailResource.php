<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfficerInChargeTrailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee = $this->employeeProfile;
        $employee_id = $employee->employee_id;
        $name = $employee->name;
        $area_section = null;
        $area_name = null;

        if($this->division_id !== null)
        {
            $area_code = 'Division';
            $area_name = $this->division->name;
        }

        if($this->department_id !== null)
        {
            $area_code = 'Department';
            $area_name = $this->department->name;
        }

        if($this->section_id !== null)
        {
            $area_code = 'Section';
            $area_name = $this->section->name;
        }

        if($this->unit_id !== null)
        {
            $area_code = 'Unit';
            $area_name = $this->unit->name;
        }

        return [
            'employee_id' => $employee_id,
            'name' => $name,
            'area_code' => $area_code,
            'area_name' => $area_name,
            'attachment_url' => $this->attachment_url,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at
        ];
    }
}
