<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $schedules = [];
        foreach ($this->schedule as $schedule) {
            $schedules[] = [
                'id' => $schedule->pivot->id,
                'date' => $schedule->date,
                'time_shift' => [
                    'id' => $schedule->timeShift->id,
                    'label' => $schedule->timeShift->timeShiftDetails(),
                    'total_hour' => $schedule->timeShift->total_hours,
                    'color' => $schedule->timeShift->color,
                ],
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->personalInformation->name(),
            'employee_id' => $this->employee_id,
            'biometric_id' => $this->biometric->biometric_id ?? null,
            'schedule' => $schedules
        ];
    }
}
