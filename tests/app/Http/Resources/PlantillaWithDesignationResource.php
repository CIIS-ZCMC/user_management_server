<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlantillaWithDesignationResource extends JsonResource
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
            'is_vacant' => $this->is_vacant,
            'number' => $this->number,
            'assigned_at' => $this->assigned_at,
            'area' => $this->assignedArea === null ? 'NONE': $this->assignedArea->area()
          
        ];
    }
}
