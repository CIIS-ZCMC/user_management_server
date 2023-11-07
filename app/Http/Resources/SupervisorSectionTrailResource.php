<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupervisorSectionTrailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $supervisor_employee = $this->supervisor;
        $supervisor = $supervisor_employee->name;
        $section = Section::where('code', $this->sector_code)->first();
        $section_name = $section['name'];
        $section_code = $section['code'];
        $attachment = $this->attachment_url;

        return [
            'supervisor' => $supervisor,
            'section_name' => $section_name,
            'section_code' => $section_code,
            'attachment' => $attachment,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at
        ];
    }
}
