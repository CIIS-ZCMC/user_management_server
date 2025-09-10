<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpecialAccessRoleAssignResource extends JsonResource
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
        
        $system_role = [
            'id' => $system_role_data->id,
            'name' => $role->name,
            'effective_at' => $system_role_data->effective_at
        ];

        $employee_data = $this->employeeProfile;

        $employee = [
            'id' => $employee_data->id,
            'name' => $employee_data->personalInformation->name()
        ];

        return [
            'id' => $this->id,
            'system_role' => $system_role,
            'employee' => $employee,
            'effective_at' => $this->effective_at
        ];
    }
}
