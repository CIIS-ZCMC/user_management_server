<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyTimeRecordResource extends JsonResource
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
            'biometric_id' => $this->biometric_id,
            'dtr_date' => $this->dtr_date,
            'time_in' => $this->first_in,
            'break_out' => $this->first_out,
            'break_in' => $this->second_in,
            'time_out' => $this->second_out,
            'overtime' => $this->overtime
        ];
    }
}
