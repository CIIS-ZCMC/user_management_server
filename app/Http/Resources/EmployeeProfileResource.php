<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $personal_information = $this->peronsalInformation;
        $nameExtension = $personal_information->name_extension === NULL?'':' '.$personal_information->name_extion.' ';
        $nameTitle = $personal_information->name_title===NULL?'': ' '.$personal_information->name_title;

        $name = $personal_information->first_name.' '.$personal_information->last_name.$nameExtension.$nameTitle;
        $department = $this->department->name;
        $jobPosition = $this->jobPosition->name;
        $jobStation = $this->jobStation->name;

        return [
            'employee_id' => $this->employee_id,
            'name' => $name,
            'department' => $department,
            'job_position' => $jobPosition,
            'job_station' => $jobStation
        ];
    }
}
