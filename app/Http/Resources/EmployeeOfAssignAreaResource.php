<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeOfAssignAreaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $area_type_assigned = '';
        $area = null;
        $is_regular = $this->plantilla_id === null? false:true;
        $employee_profile = $this->employeeProfile;
        $name = $employee_profile->name();

        if($this->division_id !== null)
        {
            $area_type_assigned = 'Division';
            $division = $this->division;
            $area = [
                'id' => $division->id,
                'name' => $division->name
            ];
        }

        if($this->department_id !== null)
        {
            $area_type_assigned = 'Department';
            $department = $this->department;
            $area = [
                'id' => $department->id,
                'name' => $department->name
            ];
        }

        if($this->section_id !== null)
        {
            $area_type_assigned = 'Section';
            $section = $this->section;
            $area = [
                'id' => $section->id,
                'name' => $section->name
            ];
        }

        if($this->unit_id !== null)
        {
            $area_type_assigned = 'Unit';
            $unit = $this->unit;
            $area = [
                'id' => $unit->id,
                'name' => $unit->name
            ];
        }

        return [
            'name' => $name,
            'is_regular' => $is_regular,
            'area_type_assigned' => $area_type_assigned,
            'area' => $area,
        ];
    }
}
