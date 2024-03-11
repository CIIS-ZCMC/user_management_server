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

        // $schedule = [
        //     "id"    => $this->schedule->timeShift->id, // time shift id
        //     "start" => $this->schedule->date,
        //     'title' => $this->schedule->timeShift->timeShiftDetails(),
        //     'color' => $this->schedule->timeShift->color,
        // ];

        $scheduleData = [];

        foreach ($this->id as $value) {
            $scheduleData[] = [
                "id"    => $value->timeShift->id,
                "start" => $value->date,
                'title' => $value->timeShift->timeShiftDetails(),
                'color' => $value->timeShift->color,
            ];
        }

        return [
            'employee_id' => $this->employee->id,
            'schedule' => $scheduleData
        ];
    }
}
