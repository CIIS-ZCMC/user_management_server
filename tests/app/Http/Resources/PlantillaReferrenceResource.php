<?php

namespace App\Http\Resources;

use App\Models\PlantillaNumber;
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
        $name = $this->designation->name;

        $platilla_numbers = PlantillaNumber::where('plantilla_id', $this->id)->where('assigned_at', null)->get();

        return [
            'id' => $this->id,
            'name' => $name,
            'date' => $this->effective_at,
            'vacant' => $this->slot-$this->total_used_plantilla_no,
            'total' => $this->slot,
        ];
    }
}
