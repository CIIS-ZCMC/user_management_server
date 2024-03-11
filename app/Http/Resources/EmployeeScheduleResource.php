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
    public function toArray(Request $request): array
    {
        $schedules = [];

        foreach ($this->employee->schedule as $schedule) {
            if ($schedule->timeShift) {
                $scheduleData = [
                    "id"    => $schedule->timeShift->id, // time shift id
                    "start" => $schedule->date,
                    'title' => $schedule->timeShiftDetails(),
                    'color' => $schedule->timeShift->color,
                ];

                $schedules[] = $scheduleData;
            }
        }

        return [
            'employee_id' => $this->employee->id,
            'schedule' => $schedules
        ];
    }
}
