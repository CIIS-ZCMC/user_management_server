<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlantillaReferrenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $name = $this->designation->name." (".$this->created_at.")";

        return [
            'id' => $this->id,
            'name' => $name,
            'vacant' => $this->slot-$this->total_used_plantille_no,
            'total' => $this->slot,
        ];
    }
}
