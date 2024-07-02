<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return [
        //     'id' => $this->id,
        //     'date' => $this->dtr_date,
        //     'first_in' => $this->first_in,
        //     'first_out' => $this->first_out,
        //     'second_in' => $this->second_in,
        //     'second_out' => $this->second_out,
        //     'total_working_hours' => $this->total_working_hours,
        //     'total_working_minutes' => $this->total_working_minutes,
        //     'overtime_minutes' => $this->overtime_minutes,
        //     'undertime_minutes' => $this->undertime_minutes,
        //     'employee' => [
        //         'id' => $this->employeeProfile->id,
        //         'employee_id' => $this->employeeProfile->employee_id,
        //         'employee_name' => $this->employeeProfile->personalInformation->employeeName(),
        //         'employment_status' => $this->employeeProfile->employment_status,
        //         'designation' => [
        //             'name' => $this->employeeProfile->findDesignation()['name'],
        //             'code' => $this->employeeProfile->findDesignation()['code'],
        //         ],
        //         'assigned_area' => [
        //             'name' => $this->employeeProfile->assignedArea->findDetails()['details']['name'],
        //             'code' => $this->employeeProfile->assignedArea->findDetails()['details']['code'],
        //         ],
        //     ]
        // ];

        return [
            'id' => $this->employeeProfile->id,
            'employee_id' => $this->employeeProfile->employee_id,
            'employee_name' => $this->employeeProfile->personalInformation->employeeName(),
            'employment_type' => $this->employeeProfile->employment_type_id,
            'designation_name' => $this->employeeProfile->findDesignation()['name'],
            'designation_code' => $this->employeeProfile->findDesignation()['code'],
            'sector' => $this->employeeProfile->assignedArea->findDetails()['sector'],
            'area_name' => $this->employeeProfile->assignedArea->findDetails()['details']['name'],
            'area_code' => $this->employeeProfile->assignedArea->findDetails()['details']['code'],
        ];
    }
}
