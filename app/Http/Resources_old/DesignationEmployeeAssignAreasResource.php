<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignationEmployeeAssignAreasResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee = $this->employee;
        $personal_information = $employee->personalInformation;
        $name = $personal_information['first_name'].' '.$personal_information['last_name'];
        $employee_id = $employee['employee_id'];

        return [
            'id' => $this->id,
            'employee_id' => $employee_id,
            'name' => $name
        ];
    }
}
