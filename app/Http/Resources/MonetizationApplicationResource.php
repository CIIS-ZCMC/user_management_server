<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonetizationApplicationResource extends JsonResource
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
            'employee' => $this->owner,
            'reason' => $this->reason,
            'attachment' => env('SERVER_DOMAIN').$this->attachment,
            'credit_value' => $this->credit_value,
            'date' => $this->date,
            'time' => $this->time,
            'recommending' => $this->recommending,
            'approving' => $this->approving,
            'created_at' => $this->created_at,
            'logs' => $this->logs
        ];
    }
}
