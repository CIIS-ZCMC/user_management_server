<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $system_roles = $this->systemRoles;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'system_roles' => count($system_roles),
            'system' => $this->systemRoles->map(function ($role) {
                return [
                    'id' => $role->system?->id,
                    'name' => $role->system?->name,
                    'code' => $role->system?->code
                ];
            }),
        ];
    }
}
