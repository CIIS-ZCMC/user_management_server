<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HeadUnitTrailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $head_employee = $this->head;
        $head = $head_employee->name();

        $unit = Unit::where('code', $this->sector_code)->first();
        $unit_name = $unit['name'];
        $unit_code = $unit['code'];
        $attachment = $this->attachment_url;

        return [
            'head' => $head,
            'unit_name' => $unit_name,
            'unit_code' => $unit_code,
            'attachment' => $attachment,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at
        ];
    }
}
