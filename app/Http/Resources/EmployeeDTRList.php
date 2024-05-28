<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeDTRList extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $assign_area = $this->assignedArea;
        $area = null;
        $area_under = [];
        $sector = '';

        // dd($assign_area->division_id !== null);
        if (isset($assign_area) && $assign_area->division_id !== null) {
            $area = $assign_area->division->name;
            $sector = 'division';
        }

        if (isset($assign_area) && $assign_area->department_id !== null) {
            if ($assign_area->department !== null) {
                $area = $assign_area->department->name;
            }

            $sector = 'department';
        }

        if (isset($assign_area) && $assign_area->section_id !== null) {
            $area = $assign_area->section->name;
            $sector = 'section';
        }

        if (isset($assign_area) && $assign_area->unit_id !== null) {
            $area = $assign_area->unit->name;
            $sector = 'unit';
        }

        if ($sector === 'department') {
            if ($assign_area->department !== null) {
                $area_under[] = $assign_area->department->division->name;
            }
        }

        // if ($sector === 'section') {
        //     if ($assign_area->section->department !== null) {
        //         $area_under[] = $assign_area->section->department->division->name;
        //         $area_under[] = $assign_area->section->department->name;
        //     } else {
        //         $area_under[] = $assign_area->section->division->name;
        //     }
        // }

        if ($sector === 'unit') {
            if ($assign_area->unit->name !== null) {
                $area_under[] = $assign_area->unit->name;
                $area_under[] = $assign_area->unit->code;
            } else {
                $area_under[] = $assign_area->section->division->name;
            }
            $area_under[] = $assign_area->unit->name;
        }

        return [
            'id' => $this->id,
            'name' => $this->personalInformation->name(),
            'biometric_id' => $this->biometric_id,
            'job_position' => isset($assign_area) ? $assign_area->designation->name : null,
            'area' => $area,
            // 'area_under' => $area_under,

        ];
    }
}
