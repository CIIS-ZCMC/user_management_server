<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingResource extends JsonResource
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
            'title' => $this->title,
            'inclusive_date' => $this->inclusive_date,
            'hours' => $this->total_hours ?? 'NONE',
            'type_of_ld' => $this->type_of_ld,
            'conducted_by' =>  $this->conducted_by ?? 'NONE',
        ];
    }
}
