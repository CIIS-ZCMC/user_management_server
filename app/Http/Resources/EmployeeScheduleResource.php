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
        // $employee = [
        //     "name" => $this->employee->personalInformation->name(),
        //     "profile_url" => $this->employee->profile_url,
        //     "designation" => [
        //         "name" => $this->employee->assignedArea->designation->name,
        //         "code" => $this->employee->assignedArea->designation->code,
        //     ],
        //     "area" => $this->employee->assignedArea->findDetails()['details']->name,

        // ];

        // $schedule = [
        //     "id" => $this->schedule->id,
        //     "date" => $this->schedule->date,
        //     "time_shift" => [
        //         "first_in" => $this->schedule->timeShift->first_in,
        //         "first_out" => $this->schedule->timeShift->first_out,
        //         "second_in" => $this->schedule->timeShift->second_in,
        //         "second_out" => $this->schedule->timeShift->second_out,
        //     ],
        //     "remarks" => $this->remarks
        // ];

        // return [
        //     "id" => $this->id,
        //     "employee_profile" => $employee,
        //     "schedule" => $schedule,
        // ];

        return [
            'employee_id' => $this->resource['employee_id'],
            'schedule'    => $this->resource['schedule'],
        ];
    }
}
