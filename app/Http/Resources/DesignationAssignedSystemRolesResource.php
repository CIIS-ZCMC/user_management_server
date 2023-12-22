<?php

namespace App\Http\Resources;

use App\Models\SystemRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignationAssignedSystemRolesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $position_system_roles = $this->positionSystemRoles;
        $systems = [];
        $modules = [];

        foreach($position_system_roles as $position_system_role){

            $module_data = SystemRole::join('role_module_permissions', 'system_roles.id', '=', 'role_module_permissions.system_role_id')
                ->join('module_permissions', 'role_module_permissions.module_permission_id', '=', 'module_permissions.id')
                ->join('system_modules', 'module_permissions.system_module_id', '=', 'system_modules.id')
                ->select('system_modules.id', 'system_modules.name', 'system_modules.code as module_code')
                ->distinct()->where('system_roles.id', $position_system_role->system_role_id)->get();

            $modules = $module_data;

            $system = SystemRole::join('systems', 'systems.id', '=', 'system_roles.system_id')
                ->select('systems.id', 'systems.name', 'systems.code')
                ->where('system_roles.id', $position_system_role->system_role_id)
                ->distinct()->get();

            $systems = $system;
        }
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'systems' => $systems,
            'system_roles' => PositionSystemRoleOnlyResource::collection($position_system_roles),
            'modules' => $modules,
            'created_at' => $this->created_at
        ];
    }
}
