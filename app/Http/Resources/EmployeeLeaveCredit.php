<?php

namespace App\Http\Resources;

use App\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeLeaveCredit extends JsonResource
{
    public function toArray($request)
    {
        return [
            'employee_profile' => $this->employee_profile ? new EmployeeProfile( $this->EmployeeProfile) : null,
            'leave_application' => $this->leave_application ? new LeaveApplication( $this->leave_application) : null,
            'leave_type' => $this->leave_type ? new LeaveType( $this->leave_type) : null,
            'operation' => $this->operation,
            'undertime_total' => $this->undertime_total,
            'working_hours_total' => $this->working_hours_total,
            'credit_value' => $this->credit_value,
            'date' => $this->date,
        ];
    }
}
