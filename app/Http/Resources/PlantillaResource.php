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
        $job_position = $this->job_position_id === null?"NONE":$this->jobPosition->name;

        return [
            'plantilla_no' => $this->plantilla_no,
            'tranche' => $this->tranche,
            'date' => $this->date,
            'category' => $this->category,
            'job_position' => $job_position
        ];
    }
}
