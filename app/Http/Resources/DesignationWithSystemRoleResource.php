<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignationWithSystemRoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $salary_grade_data = $this->salaryGrade;
        $salary_grade = [
            'id' => $salary_grade_data->id,
            'salary_grade_number' => $salary_grade_data->salary_grade_number,
            'effective_at' => $salary_grade_data->effective_at,
            'tranch' => $salary_grade_data->tranch
        ];

        $system_roles = count($this->positionSystemRoles) === 0? []:PositionSystemRoleOnlyResource::collection($this->positionSystemRoles);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'salary_grade' => $salary_grade,
            'system_roles' => $system_roles
        ];
    }
}
