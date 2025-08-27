<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OtApplicationLog extends JsonResource
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
            'process_name' => $this->process_name,
            'status' => $this->status,
            'date' => $this->date,
            // 'action_by' => $this->action_by ? new User( $this->action_by) : null,
        ];
    }
}
