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
        $number_of_employee_using = count($employee);
        $systemRole = $this->systemRole;
        $name = $systemRole->name;
        $system = $systemRole->system;


        return [
            'id' => $this->id,
            'system_role_id' => $this->system_role_id,
            'name' => $name,
            'system' => $system,
            'number_of_employee_using' => $number_of_employee_using,
            'effective_at' => $this->effective_at
        ];
    }
}
