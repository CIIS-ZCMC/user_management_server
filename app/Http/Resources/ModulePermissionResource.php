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
        $status = $this->deactivated?'DEACTIVATED':'ACTIVE';
        $permission = $this->permission->name;
        $system_module = $this->systemModule;
        $module = $system_module -> name;
        $code = $system_module->code;

        return [
            'code' => $this->code,
            'description' => $this->description,
            'status' => $status,
            'permission' => $permission,
            'model' => $module,
            'code' => $code
        ];
    }
}
