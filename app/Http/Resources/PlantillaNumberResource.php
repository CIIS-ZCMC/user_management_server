<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlantillaNumberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'number' => $this->number,
            'is_vacant' => $this->is_vacant?true:false,
            'employee' => $this->employee??'NONE',
            'assigned_at' => $this->assigned_at??'NONE',
            'plantilla_area_assigned' => new PlantillaAssignAreaResource($this->assignedArea)??"NONE"
        ];
    }
}
