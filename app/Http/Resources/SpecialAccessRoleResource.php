<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpecialAccessRoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee = $this->employees;
        $total_use = count($employee);
        $systemRole = $this->systemRole;
        $name = $systemRole->name;
        $descripition = $systemRole -> description;
        $system_id = $systemRole -> system_id;


        return [
            'system_role_id' => $this->system_role_id,
            'name' => $name,
            'description' => $descripition,
            'system_id' => $system_id,
            'total_use' => $total_use,
            'effective_at' => $this->effective_at
        ];
    }
}
