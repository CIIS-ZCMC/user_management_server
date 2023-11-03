<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Http\Resources\DesignationEmployeeAssignAreasResource;

class DesignationEmployeesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $assigned_areas = DesignationEmployeeAssignAreasResource::collection($this->assigned_areas);
        
        return [
            "designation_id" => $this->id,
            "designation_name" => $this->name,
            'assigned_areas' => $assigned_areas
        ];
    }
}
