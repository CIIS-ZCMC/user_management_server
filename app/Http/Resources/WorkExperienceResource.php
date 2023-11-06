<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkExperienceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $salary_grade = $this->salary_grade===null?'NONE':$this->salary_grade;
        $salary_grade_and_step = $this->salary_grade_and_step===null?'NONE':$this->salary_grade_and_step;

        return [
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'position_title' => $this->position_title,
            'appointment_status' => $this->appointment_status,
            'salary_grade' => $salary_grade,
            'salary_grade_and_step' => $salary_grade_and_step,
            'company' => $this->company
        ];
    }
}
