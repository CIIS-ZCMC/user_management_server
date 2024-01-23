<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'date'              => $this->date,
            'is_weekend'        => $this->remarks,
            'is_on_call'        => $this->is_on_call,
            'status'            => $this->status,
            'remarks'           => $this->remarks,
            'time_shift'        => $this->timeShift ? new TimeShiftResource($this->timeShift) : null,
            'holiday'           => $this->holiday ? new HolidayResource($this->holiday) : null,
            'employee_profile'  => $this->employee ? EmployeeProfileResource::collection($this->employee) : [],
            'deleted_at'        => (string) $this->deleted_at,
            'created_at'        => (string) $this->created_at,
            'updated_at'        => (string) $this->updated_at,
        ];
    }
}
