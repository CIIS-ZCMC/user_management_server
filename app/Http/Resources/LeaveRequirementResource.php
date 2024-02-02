<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequirementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $logs = $this->logs;

        $added_by = [];
        $date_added = null;

        foreach( $logs as $log ) {
            if($date_added === null) $date_added = $log->created_at; 
            $employee = [
                'employee_id' => $log->employee->employee_id,
                'name' => $log->employee->personalInformation->name(),
                'designation_name' => $log->employee->assignedArea->designation->name,
                'designation_code' => $log->employee->assignedArea->designation->code,
            ];
            $added_by[] = $employee;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'added_by' => $added_by,
            'date_added' => $date_added,
            'logs'  => $this->logs ? RequirementLog::collection($this->logs) : [],
        ];
    }
}
