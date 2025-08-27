<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

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
            'date' => $this->dtr_date,
            'time_in' => $this->first_in ? $this->extractTime($this->first_in) : null,
            'break_out' => $this->first_out ? $this->extractTime($this->first_out) : null,
            'break_in' => $this->second_in ? $this->extractTime($this->second_in) : null,
            'time_out' => $this->second_out ? $this->extractTime($this->second_out) : null,
            'overtime' => $this->overtime
        ];
    }

    protected function extractTime($time)
    {
        return Carbon::parse($time)->format('g:i A');
    }
}
