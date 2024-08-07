<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeProfileUpdateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {   
        if($this->type === 'Educational Background'){
            return [
                "id"=> $this->id,
                "name" => $this->personalInformation->name(),
                "assigned_area" => $this->personalInformation->employeeProfile->assignedArea->findDetails()['details']->name,
                'designation' => $this->personalInformation->employeeProfile->assignedArea->designation->name,
                "employee_id" => $this->personalInformation->employeeProfile->employee_id,
                "profile_url" => config('app.server_domain')."/profiles/".$this->personalInformation->employeeProfile->profile_url,
                "type" => 'Educational Background',
                "date_requested" => $this->created_at,
                "approved_at" => $this->approved_at,
                "details" => new EducationalBackgroundResource($this)
            ];
        }   

        if($this->type === 'Eligibility'){
            return [
                "id"=> $this->id,
                "name" => $this->personalInformation->name(),
                "assigned_area" => $this->personalInformation->employeeProfile->assignedArea->findDetails()['details']->name,
                'designation' => $this->personalInformation->employeeProfile->assignedArea->designation->name,
                "employee_id" => $this->personalInformation->employeeProfile->employee_id,
                "profile_url" => config('app.server_domain')."/profiles/".$this->personalInformation->employeeProfile->profile_url,
                "type" => 'Eligibility',
                "date_requested" => $this->created_at,
                "approved_at" => $this->approved_at,
                "details" => new CivilServiceEligibilityResource($this)
            ];
        }   

        return [
            "id"=> $this->id,
            "name" => $this->personalInformation->name(),
            "assigned_area" => $this->personalInformation->employeeProfile->assignedArea->findDetails()['details']->name, 
            'designation' => $this->personalInformation->employeeProfile->assignedArea->designation->name,
            "employee_id" => $this->personalInformation->employeeProfile->employee_id,
            "profile_url" => config('app.server_domain')."/profiles/".$this->personalInformation->employeeProfile->profile_url,
            "type" => 'Training',
            "date_requested" => $this->created_at,
            "approved_at" => $this->approved_at,
            "details" => new TrainingResource($this)
        ];
    }
}
