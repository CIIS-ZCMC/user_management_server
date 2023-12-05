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
            'step' => $this->step,
            'amount' => $this->amount,
            'effective_at' => $this->effective_at
        ];
    }
}
