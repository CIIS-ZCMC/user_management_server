<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlantillaRequirementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'education' => $this->education,
            'training' => $this->training??'NONE',
            'experience' => $this->experince,
            'eligibility' => $this->eligibility??'NONE',
            'competency' => $this->competency??'NONE'
        ];
    }
}
