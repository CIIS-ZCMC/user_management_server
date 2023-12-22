<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryGradeResource extends JsonResource
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
            'salary_grade_number' => $this->salary_grade_number,
            'one' => $this->one,
            'two' => $this->two,
            'three' => $this->three,
            'four' => $this->four,
            'five' => $this->five,
            'six' => $this->six,
            'seven' => $this->seven,
            'eight' => $this->eight,
            'tranch' => $this->tranch,
            'effective_at' => $this->effective_at
        ];
    }
}
