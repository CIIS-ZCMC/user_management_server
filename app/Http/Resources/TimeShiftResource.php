<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeShiftResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'first_in'      => $this->first_in,
            'first_out'     => $this->first_out,
            'second_in'     => $this->second_in,
            'second_out'    => $this->second_out,
            'total_hours'   => $this->total_hours,
            'deleted_at'    => (string) $this->deleted_at,
            'created_at'    => (string) $this->created_at,
            'updated_at'    => (string) $this->updated_at,
        ];
    }
}
