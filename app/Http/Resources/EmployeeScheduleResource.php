<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        // Process the schedule data
        $schedule = $this->resource->map(function ($value) {
            return [
                'id' => $value->schedule->timeShift->id,
                'start' => $value->schedule->date,
                'title' => $value->schedule->timeShift->timeShiftDetails(),
                'color' => $value->schedule->timeShift->color,
                'status' => $value->schedule->status,
            ];
        });

        return [
            'employee_id' => $this->resource->isEmpty() ? null : $this->resource->first()->employee_profile_id,
            'position_type' => $this->employeeProfile->findDesignation()->position_type,
            'shifting' => $this->employeeProfile->shifting,
            'schedule' => $schedule,
        ];
    }
}
