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
        $personal_information = $this->personalInformation;
        $nameExtension = $personal_information === null?'':' '.$personal_information->name_extension.' ';
        $nameTitle = $personal_information===null?'': ' '.$personal_information->name_title;

        $name = $personal_information->first_name.' '.$personal_information->last_name.$nameExtension.$nameTitle;
        $department = $this->department===null?"NONE":$this->department->name;
        $designation = $this->designation===null?"NONE":$this->designation->name;
        $job_station = $this->station===null?"NONE":$this->station->name;

        return [
            'employee_id' => $this->employee_id,
            'name' => $name,
            'department' => $department,
            'designation' => $designation,
            'job_station' => $job_station
        ];
    }
}
