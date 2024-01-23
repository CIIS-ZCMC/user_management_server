<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveTypeResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'period' => $this->period,
            'file_date' => $this->file_date,
            'month_value' => $this->month_value,
            'annual_credit' => $this->annual_credit,
            'is_active' => $this->is_active,
            'is_special' => $this->is_special,
            'is_country' => $this->is_country,
            'is_illness' => $this->is_illness,
            'is_days_recommended' => $this->is_days_recommended,
            'leave_type_requirements' => LeaveTypeRequirementResource::collection($this->leaveTypeRequirements)
        ];
    }
}
