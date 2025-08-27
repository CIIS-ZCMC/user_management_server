<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeesByAreaAssignedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee = $this->employeeProfile;

        return [
            'id' => $employee->id,
            'employee_id' => $employee->employee_id,
            'name' => $employee->personalInformation->name()
        ];
    }
}
