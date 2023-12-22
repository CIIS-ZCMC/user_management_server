<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionSystemRoleOnlyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $system_role_data = $this->systemRole;
        $role = $system_role_data->role;
        
        return [
            'id' => $this->id,
            'name' => $role->name,
            'created_at' => $this->created_at
        ];
    }
}
