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
            $schedules[] = [
                "id"    => $schedule->timeShift->id,
                "start" => $schedule->date,
                'title' => $schedule->timeShift->timeShiftDetails(),
                'color' => $schedule->timeShift->color,
            ];
        }
        
        return [
            'employee_id' => $this->employee->id,
            'schedule' => $schedules
        ];
    }
}
