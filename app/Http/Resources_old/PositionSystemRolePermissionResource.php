<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionSystemRolePermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $module_permission = $this->modulePermission;
        $system_module = $module_permission->module;
        $permission = $module_permission->permission;

        $module = $system_module['name'];
        $action = $permission['action'];

        return [
            'id' => $this->id,
            'module' => $module,
            'action' => $action
        ];
    }
}
