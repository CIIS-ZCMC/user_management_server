<?php

namespace App\Http\Resources;

use App\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveTypeLogResource extends JsonResource
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
                'action_by' => $this->actioned_by ? new EmployeeProfile( $this->action_by) : null,
                'action' => $this->action,
            ];
        
    }
}
