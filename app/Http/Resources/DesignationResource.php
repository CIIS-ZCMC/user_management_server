<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignationResource extends JsonResource
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
            'amount' => $salary_grade_data->one,
            'effective_at' => $salary_grade_data->effective_at,
            'tranch' => $salary_grade_data->tranch
        ];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'salary_grade' => $salary_grade,
            'created_at' => $this->created_at
        ];
    }
}
