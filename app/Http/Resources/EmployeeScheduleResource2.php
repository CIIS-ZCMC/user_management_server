<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeScheduleResource2 extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Process the schedule data
        $schedule = $this->resource->map(function ($value) {
            return [
                'id' => $value->id,
                'date' => $value->schedule->date,
                'time_shift' => [
                    'id' => $value->schedule->timeShift->id,
                    'label' => $value->schedule->timeShift->shiftDetails(),
                    'total_hour' => $value->schedule->timeShift->total_hours,
                    'color' => $value->schedule->timeShift->color,
                ]
            ];
        });

        return [
            'employee_id' => $this->resource->isEmpty() ? null : $this->resource->first()->employee_profile_id,
            'position_type' => $this->resource->first()->employee->findDesignation()->position_type,
            'shifting' => $this->resource->first()->employee->shifting,
            'schedule' => $schedule,
        ];
    }
}
