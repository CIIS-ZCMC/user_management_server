<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlantillaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */ 
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slot' => $this->slot,
            'available' => $this->slot-$this->total_used_plantilla_no,
            'effective_at' => $this->effective_at,
            'designation' => new DesignationResource($this->designation),
            'plantilla_requirement' => new PlantillaRequirementResource($this->requirement),
            'plantilla_numbers' => PlantillaNumberResource::collection($this->plantillaNumbers)
        ];
    }
}
