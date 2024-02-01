<?php

namespace App\Http\Resources;

use App\Http\Resources\LeaveApplicationDateTime as ResourcesLeaveApplicationDateTime;
use App\Http\Resources\LeaveApplicationRequirement as ResourcesLeaveApplicationRequirement;
use App\Models\LeaveApplicationDateTime;
use App\Models\LeaveApplicationRequirement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveApplication extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        //MULTIPLE FILES
        // $files_result = [];
        // foreach($this->files as $file){
        //     $files_result[] = [
        //         'file_name' => $this->file_name,
        //         'file_path' => 'http://localhost:8000/server/'.$this->file_name
        //     ];
        // }

        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'location' => $this->location,
            'specific_location' => $this->specific_location,
            'with_pay' => $this->with_pay,
            'whole_day' => $this->whole_day,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'date' => $this->date,
            'leave_type' => $this->leave_type ? new LeaveType( $this->leave_type) : null,
            'requirements' => ResourcesLeaveApplicationRequirement::collection($this->requirements),
            'dates' => ResourcesLeaveApplicationDateTime::collection($this->dates),
            'logs' => LeaveApplicationLog::collection($this->logs),
            // 'leave_application_requirement'=> $this->leave_application_requirement ? ResourcesLeaveApplicationRequirement::collection($this->leave_application_requirement) : [],
            // 'leave_application_log'=> $this->leave_application_log ? LeaveApplicationLog::collection($this->leave_application_log) : [],
            // 'leave_application_date_time'=> $this->leave_application_date_time ? ResourcesLeaveApplicationDateTime::collection($this->leave_application_date_time) : [],
            //MULTIPLE FILES
            // 'files_result' => $files_result,
            // SINGLE FILE
            // 'file_name' => $this->file_name,
            // 'file_path' => 'http://localhost:8000/server/'.$this->file_name

        ];
    }
}
