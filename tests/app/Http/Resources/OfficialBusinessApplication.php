<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfficialBusinessApplication extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'time_from' => $this->time_from,
            'time_to' => $this->time_to,
            'reason' => $this->reason,
            'status' => $this->ststus,
            'date' => $this->date,
            'requirements' => ObApplicationRequirement::collection($this->requirements),
            'logs' => ObApplicationLog::collection($this->logs),
        ];
    }
}
