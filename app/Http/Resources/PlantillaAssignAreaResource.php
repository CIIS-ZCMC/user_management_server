<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlantillaAssignAreaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $area_type_name = '';
        $area = null;

        if($this->division_id === null)
        {
            $area_type_name = 'Division';
            $division = $this->division;
            $area = [
                'id' => $this->id,
                'name' => $division->name,
                'code' => $division->code,
                'area_id' => $division->area_id
            ];
        }

        if($this->department_id === null)
        {
            $area_type_name = 'Department';
            $department = $this->department;
            $area = [
                'id' => $this->id,
                'name' => $department->name,
                'code' => $department->code,
                'area_id' => $department->area_id
            ];
        }

        if($this->section_id === null)
        {
            $area_type_name = 'Section';
            $section = $this->section;
            $area = [
                'id' => $this->id,
                'name' => $section->name,
                'code' => $section->code,
                'area_id' => $section->area
            ];
        }

        if($this->unit_id === null)
        {
            $area_type_name = 'Unit';
            $unit = $this->unit;
            $area = [
                'id' => $this->id,
                'name' => $unit->name,
                'code' => $unit->code,
                'area_id' => $unit->area_id
            ];
        }



        return [
            'id' => $this->id,
            'area_type_name' => $area_type_name,
            'area' => $area,
            'effective_at' => $this->effective_at
        ];
    }
}
