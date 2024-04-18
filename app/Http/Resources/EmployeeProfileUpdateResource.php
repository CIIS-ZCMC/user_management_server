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
                "employee_id" => $this->personalInformation->employee->employee_id,
                "type" => $this->type,
                "date_requested" => $this->created_at,
                "educational_background" => new EducationalBackgroundResource($this)
            ];
        }   

        if($this->type === 'Eligibility'){
            return [
                "id"=> $this->id,
                "name" => $this->personalInformation->name(),
                "employee_id" => $this->personalInformation->employee->employee_id,
                "type" => $this->type,
                "date_requested" => $this->created_at,
                "eligibility" => new CivilServiceEligibilityResource($this)
            ];
        }

        return [
            "id"=> $this->id,
            "name" => $this->personalInformation->name(),
            "employee_id" => $this->personalInformation->employee->employee_id,
            "type" => $this->type,
            "date_requested" => $this->created_at,
            "training" => new TrainingResource($this)
        ];
    }
}
