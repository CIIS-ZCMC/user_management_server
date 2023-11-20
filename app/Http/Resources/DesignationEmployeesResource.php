<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignationEmployeesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {   
        $assign_areas = $this->assignAreas;

        return [
            "designation_id" => $this->id,
            "designation_name" => $this->name,
            'employee_list' => EmployeeOfAssignAreaResource::collection($this->assignAreas) 
        ];
    }
}
