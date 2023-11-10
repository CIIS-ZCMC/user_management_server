<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveTypeRequirement extends JsonResource
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
            'leave_type' => $this->leave_type ? new LeaveType( $this->leave_type) : null,
            'requirements' => $this->requirements ?  LeaveTypeRequirement::collection($this->requirements) : [],
            // 'leave_type' => $this->leave_type ? new LeaveType( $this->leave_type) : null,
            // 'requirements' => $this->requirements ?  LeaveTypeRequirement::collection($this->requirements) : [],
            // 'requirement' => new Requirement($this->requirement),
        ];
    }
}
