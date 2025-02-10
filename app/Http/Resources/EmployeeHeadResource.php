<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeHeadResource extends JsonResource
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
            'profile_url' => $this->profile_url,
            'position' => $this->position()['position'],
            'name' => $this->personalInformation->employeeName(),
            'designation' => $this->assignedArea->designation->name,
            'assigned_area' => $this->assignedArea->findDetails()['details'],
        ];
    }
}
