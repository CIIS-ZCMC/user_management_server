<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonthlyWorkHoursResource extends JsonResource
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
            'month_year' => $this->month_year,
            'employment_type' => [
                'id' => $this->employmentType->id,
                'name' => $this->employmentType->name,
                'work_hours' => $this->employmentType->monthlyWorkingHours->work_hours,
            ],
        ];
    }
}
