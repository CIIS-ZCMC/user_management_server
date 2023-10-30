<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OICUnitTrailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $oic_employee = $this->oic;
        $officer_in_charge = $oic_employee->name();
        $unit = Unit::where('code', $this->sector_code)->first();
        
        $unit_name = $unit['name'];
        $unit_code = $unit['code'];
        $attachment = $this->attachment_url;

        return [
            'officer_in_charge' => $officer_in_charge,
            'unit_name' => $unit_name,
            'unit_code' => $unit_code,
            'attachment' => $attachment,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at
        ];
    }
}
