<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignationReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        
        $designation = $this->designation;
        // $salary_grade = $this->salaryGrade;
        $salary_grade = $designation->salaryGrade;

        return [
            'id' => $this->designation_id,
            'name' => $designation->name,
            'code' => $designation->code,
            'probation' =>  $designation->probation,
            'position_type' => $designation->position_type,
            'employee_count' => $this->employee_count,
            'salary_grade' => $salary_grade ,
            'step' => $this->salary_grade_step
        ];
    }
}