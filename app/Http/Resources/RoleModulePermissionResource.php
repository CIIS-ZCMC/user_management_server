<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleModulePermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $module_permission = $this->modulePermission;
        $systemRole = $this->systemRole;

        $system_role = $systemRole->name;
        $description = $module_permission->description;

        return [
            'system_role' => $system_role,
            'description' => $description,
            'created_at' => $this->created_at
        ];
    }
}
