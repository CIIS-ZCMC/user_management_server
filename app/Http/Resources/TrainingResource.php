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
        $conducted_by = $this->conducted_by === null? 'NONE': $this->conducted_by;
        $hours = $this->total_hours === null? 'NONE': $this->total_hours;

        return [
            'title' => $this->title,
            'inclusive_date' => $this->inclusive_date,
            'hours' => $hours,
            'type_of_ld' => $this->type_of_ld,
            'conducted_by' => $conducted_by,
        ];
    }
}
