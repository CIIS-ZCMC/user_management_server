<?php

namespace App\Http\Resources;

use App\Models\EmployeeProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveTypeResource extends JsonResource
{

    public function toArray($request)
    {
        $logs = $this->logs;

        $added_by = [];
        $file_attached = [];
        $date_added = null;

        foreach( $logs as $log ) {
            if($date_added === null) $date_added = $log->created_at; 
            $employee = [
                'id' => $log->id,
                'action' => $log->action,
                'action_by' => [
                    'name' => $log->employeeProfile->personalInformation->name(),
                    'profile_url' => $this->profile_url,
                    'designation' => [
                        'name' => $log->employeeProfile->assignedArea->designation->name,
                        'code' => $log->employeeProfile->assignedArea->designation->code,
                    ],
                    'area' => $log->employeeProfile->assignedArea->findDetails()['details']->name,
                ],
            ];
            $added_by[] = $employee;
        }

        foreach ($this->leaveTypeAttachments as $file) {
           $files = [
                'id' => $file->id,
                'leave_type_id' => $this->id,
                'name' => $file->file_name,
                'path' => env("SERVER_DOMAIN")."/requirements/".$file->path,
                'size' => $file->size,
                'created_at' => $file->created_at,
                'updated_at' => $file->updated_at
           ];

           $file_attached[] = $files;
        }
      

        return [
            'id' => $this->id,
            'name' => $this->name,
            'republic_act'=>$this->republic_act,
            'description' => $this->description,
            'period' => (double)$this->period,
            'file_date' => $this->file_date,
            'month_value' => $this->month_value,
            'annual_credit' => (double)$this->annual_credit,
            'is_active' => $this->is_active,
            'is_special' => $this->is_special,
            'is_country' => $this->is_country,
            'is_illness' => $this->is_illness,
            'is_study' => $this->is_study,
            'is_days_recommended' => $this->is_days_recommended,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            'attachments' => $file_attached,
            'requirements' => LeaveTypeRequirementResource::collection($this->leaveTypeRequirements),
            'logs'  => $added_by,
        ];
    }
}
