<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OICDivisionTrailResource extends JsonResource
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

        $division = Division::where('code', $this->sector_code)->first();
        $division_name = $division['name'];
        $division_code = $division['code'];
        $attachment = $this->attachment_url;

        return [
            'officer_in_charge' => $officer_in_charge,
            'division_name' => $division_name,
            'division_code' => $division_code,
            '$attachment' => $attachment,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at
        ];
    }
}
