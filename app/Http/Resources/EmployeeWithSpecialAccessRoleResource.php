<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeWithSpecialAccessRoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $special_access_roles = $this->specialAccessRole;

        return [
            'id' => $this->id,
            'name' => $this->name(),
            'job_position' => $this->assignedArea->designation->name,
            'area' => $this->assignedArea->findDetails()['details']['name'],
            'special_access_role' => SpecialAccessRoleAccessManagementResource::collection($special_access_roles),
            'effective_at' => $special_access_roles[0]->effective_at,
            'meta' => [
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at
            ]
        ];
    }
}
