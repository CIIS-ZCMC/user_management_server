<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonalScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->schedule->timeShift->id,
            'date' => $this->resource->schedule->date,
            'time' => $this->resource->schedule->timeShift->timeShiftDetails(),
            'color' => $this->resource->schedule->timeShift->color,
            'status' => $this->resource->schedule->status,
        ];
    }
}
