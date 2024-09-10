<?php

namespace App\Http\Resources;

use App\Models\OvtApplicationEmployee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\OvtApplicationEmployeeResource;
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
            "overtime_application_id " => $this->overtime_application_id,
            "time_from" => $this->time_from,
            "time_to" => $this->time_to,
            "date" => $this->date,
            'employees' => OvtApplicationEmployeeResource::collection($this->employees),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
