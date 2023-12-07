<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Division;

class ChiefDivisionTrailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $chief_employee = $this->head;
        $chief = $chief_employee->name;

        $division = Division::where('code', $this->sector_code)->first();
        $division_name = $division['name'];
        $division_code = $division['code'];
        $attachment = $this->attachment_url;

        return [
            'id' => $this->id,
            'chief' => $chief,
            'division_name' => $division_name,
            'division_code' => $division_code,
            '$attachment' => $attachment,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at
        ];
    }
}
