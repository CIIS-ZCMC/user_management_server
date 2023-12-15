<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlantillaNumberAllResource extends JsonResource
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
            'number' => $this->number,
            'is_vacant' => $this->is_vacant,
            'assigned_at' => $this->assigned_at,
            'plantilla' => $this->plantilla,
            'requirement' => $this->plantilla->requirement,
            'designation' => $this->plantilla->designation,
            'employee' => $this->employee
        ];
    }
}
