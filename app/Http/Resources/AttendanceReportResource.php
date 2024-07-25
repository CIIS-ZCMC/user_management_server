<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceReportResource extends JsonResource
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
            'employee_id' => $this->employee_id,
            'employee_name' => $this->employee_name,
            'employment_type' => $this->employment_type,
            'designation_name' => $this->designation_name,
            'designation_code' => $this->designation_code,
            'sector' => $this->sector,
            'area_name' => $this->area_name,
            'area_code' => $this->area_code,
            // 'tardiness_count' => $this->tardiness_count,
            // 'total_undertime_minutes' => $this->total_undertime_minutes,
        ];
    }
}
