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
        })->toArray();

        // Ensure $schedule is an empty array if there are no schedules
        $schedule = empty($schedule) ? [] : $schedule;


        // Extract schedule dates
        $scheduleDates = array_map(function ($schedule) {
            return $schedule['date'];
        }, $schedule);

        $totalWorkingHours = collect($schedule)->sum(function ($item) {
            return $item['time_shift']['total_hour'];
        });

        $monthlyWorkingHours = count($scheduleDates) > 0 ? $this->resource->first()->schedule->first()->monthlyWorkingHours($scheduleDates[0]) : 0;

        return [
            'employee_id' => $this->resource->isEmpty() ? null : $this->resource->first()->employee_profile_id,
            'position_type' => $this->resource->isEmpty() ? null : optional($this->resource->first()->employee->findDesignation())->position_type,
            'shifting' => $this->resource->isEmpty() ? null : optional($this->resource->first()->employee)->shifting,
            'total_working_hours' => $this->resource->isEmpty() ? null : $totalWorkingHours,
            'monthly_working_hours' => $this->resource->isEmpty() ? null : $monthlyWorkingHours,
            'schedule' => $this->resource->isEmpty() ? [] : $schedule,
        ];
    }
}
