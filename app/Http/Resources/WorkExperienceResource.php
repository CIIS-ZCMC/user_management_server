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
        return [
            'id' => $this->id,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'position_title' => $this->position_title,
            'appointment_status' => $this->appointment_status,
            'salary_grade' => $this->salary_grade??'NONE',
            'salary_grade_and_step' => $this->salary_grade_and_step??'NONE',
            'company' => $this->company
        ];
    }
}
