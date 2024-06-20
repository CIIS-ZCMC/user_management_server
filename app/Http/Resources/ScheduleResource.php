<?php

namespace App\Http\Resources;

use App\Models\MonthlyWorkHours;
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
                    'label' => $schedule->timeShift->shiftDetails(),
                    'total_hour' => $schedule->timeShift->total_hours,
                    'color' => $schedule->timeShift->color,
                ],
            ];
        }

        // Assuming the schedule dates are needed for monthlyWorkingHours
        $scheduleDates = array_map(function ($schedule) {
            return $schedule['date'];
        }, $schedules);

        $totalWorkingHours = $this->total_working_hours ?? 0;
        $monthlyWorkingHours = count($scheduleDates) > 0 ? $this->schedule->first()->monthlyWorkingHours($scheduleDates[0]) : 0;

        return [
            'id' => $this->id,
            'name' => $this->personalInformation->name(),
            'employee_id' => $this->employee_id,
            'biometric_id' => $this->biometric->biometric_id ?? null,
            'employment_type' => $this->employmentType,
            'designation' => $this->findDesignation()->name,
            'assigned_area' => $this->assignedArea->findDetails(),
            'position_type' => $this->findDesignation()->position_type,
            'shifting' => $this->shifting,
            // 'position' => $this->position(),
            'total_working_hours' => $totalWorkingHours,
            'monthly_working_hours' => $monthlyWorkingHours,
            'schedule' => $schedules
        ];
    }
}
