<?php

namespace App\Http\Resources\HR;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeesReportByStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'name' => $this->name,
            'email' => $this->personalInformation->contact->email_address,
            'date_hired' => $this->date_hired,
            'area' => $this->assignedArea == null? null : $this->assignedArea->findDetails()['details']['name'],
            'job_position' => $this->job_position ?? 'Not Assigned',
            'employment_type_id' => $this->employment_type_id,
            'has_login_history' => $this->has_login_history,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
