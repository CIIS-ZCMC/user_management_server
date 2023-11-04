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
        $designation = $this->designation_id === null?"NONE":$this->designation->name;

        return [
            'plantilla_no' => $this->plantilla_no,
            'tranche' => $this->tranche,
            'date' => $this->date,
            'category' => $this->category,
            'designation' => $designation
        ];
    }
}
