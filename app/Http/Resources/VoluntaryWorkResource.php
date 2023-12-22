<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoluntaryWorkResource extends JsonResource
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
            'name_address_organization' => $this->name_address_organization,
            'inclusive_from' => $this->inclusive_from,
            'inclusive_to' => $this->inclusive_to,
            'hours' => $this->hours,
            'position' => $this->position
        ];
    }
}
