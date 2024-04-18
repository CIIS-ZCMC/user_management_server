<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeOvertimeCreditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

     public function toArray($request)
     {
         return [
            'id' => $this->id,
            'employee_ot_credit_id' => $this->employee_ot_credit_id,
            'cto_application_id' => $this->cto_application_id,
            'overtime_application_id' => $this->overtime_application_id,
            'action' => $this->action,
            'previous_overtime_hours' => $this->previous_overtime_hours,
            'hours' => $this->hours,
            'expired_credit_by_hour' => $this->expired_credit_by_hour,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
         ];
     }
}
