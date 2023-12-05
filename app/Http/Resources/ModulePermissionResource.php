<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModulePermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $module_name = $this->system_module_id===null?"NONE":$this->module->name;
        $permission_name = $this->permission_id===null?"NONE":$this->permission->name;
        $status = $this->active?'ACTIVE':'DEACTIVATED';

        return [
            'id' => $this->id,
            'module_name' => $module_name,
            'permission_name' => $permission_name,
            'code' => $this->code,
            'status' => $status
        ];
    }
}
