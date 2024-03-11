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
        $employee = [
            "name" => $this->employee->personalInformation->name(),
            "profile_url" => $this->employee->profile_url,
            "designation" => [
                "name" => $this->employee->assignedArea->designation->name,
                "code" => $this->employee->assignedArea->designation->code,
            ],
            "area" => $this->employee->assignedArea->findDetails()['details']->name,

        ];

        $schedule = [
            "start" => $this->schedule->date,
            'title' => $this->schedule->timeShift->timeShiftDetails(),
            'color' => $this->schedule->timeShift->color,
        ];

        return [
            'employee_id' => $this->employee->id,
            'schedule' => $schedule
        ];
    }
        // return [
        //     "employee_id" => $this->id,
        //     "employee_profile" => $employee,
        //     "schedule" => $schedule,
        // ];
}
