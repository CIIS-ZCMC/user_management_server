<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EducationalBackgroundResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"=> $this->id,
            'name' => $this->name,
            'level' => $this->level,
            'degree_course' => $this->degree_course,
            'year_graduated' => $this->year_graduated,
            'highest_grade' => $this->highest_grade,
            'inclusive_from' => $this->inclusive_from,
            'inclusive_to' => $this->inclusive_to,
            'academic_honors' => $this->academic_honors,
            'attachment' =>   config('app.server_domain')."/education/".$this->attachment
        ];
    }
}
