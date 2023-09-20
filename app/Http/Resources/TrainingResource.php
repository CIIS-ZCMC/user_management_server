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
        $total_hours = $this->total_hours === null? 'NONE': $this->total_hours;

        return [
            'inclusive_date' => $this->inclusive_date,
            'is_lnd' => $this->is_lnd?true:false,
            'conducted_by' => $conducted_by,
            'total_hours' => $total_hours
        ];
    }
}
