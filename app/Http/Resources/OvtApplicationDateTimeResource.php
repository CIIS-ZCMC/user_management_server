<?php

namespace App\Http\Resources;

use App\Models\OvtApplicationEmployee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OvtApplicationDateTimeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray($request)
    {
        return [
            "time_from" => $this->name,
            "time_to" => $this->quantity,
            "date" => $this->man_hour,
            'employees' => OvtApplicationEmployee::collection($this->employees),
        ];
    }
}
