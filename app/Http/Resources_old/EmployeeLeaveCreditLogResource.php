<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeLeaveCreditLogResource extends JsonResource
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
             'employee_leave_credit_id' => $this->employee_leave_credit_id,
             'previous_credit' => $this->previous_credit,
             'leave_credits' => $this->leave_credits,
             'reason' => $this->reason,
             'created_at' => $this->created_at,
             'updated_at' => $this->updated_at,
         ];
     }
}
