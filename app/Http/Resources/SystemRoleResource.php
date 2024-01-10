<?php

namespace App\Http\Resources;

use App\Models\AssignArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemRoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $system_name = $this->system_id === null? 'NONE':$this->system->name;
        $role = $this->role;
        $role_id = $this->role->id;
        $total_permission = $this->roleModulePermissions;
        
        $countAssignedAreas = AssignArea::leftJoin('designations as d', 'd.id', '=', 'assigned_areas.designation_id')
            ->leftJoin('plantillas as p', 'p.id', '=', 'assigned_areas.plantilla_id')
            ->join('position_system_roles as psr', function ($join) {
                $join->on('psr.system_role_id', '=', DB::raw($this->id))
                    ->on('psr.designation_id', '=', DB::raw('COALESCE(assigned_areas.designation_id, p.designation_id)'));
            })
            ->count('assigned_areas.employee_profile_id');

        return [
            'id' => $this->id,
            'role_id' => $role_id,
            'name' => $role->name,
            'code' => $role->code,
            'system_id' =>  $this->system_id,
            'system_name' => $system_name,
            'total_permission' => count($total_permission),
            'total_user' => $countAssignedAreas,
            'effective_at' => $this->effective_at
        ];
    }
}
