<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\PositionSystemRolePermissionResource;

class PositionSystemRoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $designation = $this->designation;
        $system_role = $this->systemRole;

        $designation_name = $designation['name'];
        $designation_code = $designation['code'];
        $system_role_name = $system_role['name'];
        $system_role_code = $system_role['code'];
        $status = $this->deactivated_at === null? 'Deactivated': 'Active';

        $role_module_permissions = $system_role->roleModulePermission;
        $permissions = [];

        if(count($role_module_permissions) > 0){
            $permissions = PositionSystemRolePermissionResource::collection($role_module_permissions);
        }

        return [
            'id' => $this->id,
            'designation_name' => $designation_name,
            'designation_code' => $designation_code,
            'system_role_name' => $system_role_name,
            'system_role_code' => $system_role_code,
            'status' => $status,
            'deactivated_at' => $this->deactivated_at,
            'permissions' => $permissions
        ];
    }
}
