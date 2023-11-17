<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemRolePermissionsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'system_role_id' => new SystemRoleResource($this->systemRole),
            'module_permission' => new ModulePermissionResource($this->modulePermission) 
        ];
    }
}
