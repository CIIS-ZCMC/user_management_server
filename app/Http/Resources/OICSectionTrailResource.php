<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OICSectionTrailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $oic_employee = $this->oic;
        $officer_in_charge = $oic_employee->name;
        $section = Section::where('code', $this->sector_code)->first();
        $section_name = $section['name'];
        $section_code = $section['code'];
        $attachment = $this->attachment_url;

        return [
            'officer_in_charge' => $officer_in_charge,
            'section_name' => $section_name,
            'section_code' => $section_code,
            'attachment' => $attachment,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at
        ];
    }
}
