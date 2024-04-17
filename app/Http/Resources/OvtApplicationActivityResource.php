<?php

namespace App\Http\Resources;

use App\Models\OvtApplicationDatetime;
use App\Models\OvtApplicationLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OvtApplicationActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            "name" => $this->name,
            "quantity" => $this->quantity,
            "man_hour" => $this->man_hour,
            "period_covered" => $this->period_covered,
            'dates' => OvtApplicationDateTimeResource::collection($this->dates),
        ];
    }
}
