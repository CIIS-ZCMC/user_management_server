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
        $salary_grade = $this->salaryGrade;
        $salary_grade_number = $salary_grade['salary_grade_number'];
        $salary_grade_amount = $salary_grade['amount'];

        return [
            'name' => $this->name,
            'code' => $this->code,
            'salary_grade_number' => $this->salary_grade_number,
            'salary_grade_amount' => $this->salary_grade_amount
        ];
    }
}
