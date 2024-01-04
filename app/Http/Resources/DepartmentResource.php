<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if($this->chief_employee_profile_id !== null)
        {
            $name = $this->name;
            $code = $this->code;
            $head_status = $this->head_status? 'On Site':'On Leave';
            $approving_officer = $this->head_status? 'Head':'OIC';


            $head = $this->head;
            $head_designation = $head->assignArea->designation;
            $head_personal_information = $head->personalInformation;
            $head = $head_personal_information->name;

            $officer_in_charge = 'NONE';
            $oic_designation = 'NONE';
            $training_officer = 'NONE';
            $toe_designation = 'NONE';  

            if($this->oic_employee_profile_id !== null)
            {
                $oic = $this->oic();
                $oic_designation = $oic->assignArea->designation;
                $oic_personal_information = $oic->personalInformation;
                $officer_in_charge = $oic_personal_information->name;
            }

            if($this->training_officer_employee_profile_id !== null)
            {
                $training_officer_employee = $this->trainingOfficer();
                $toe_designation = $training_officer_employee->assignArea->designation;
                $to_personal_information = $training_officer_employee->personalInformation;
                $training_officer_employee = $to_personal_information->name;
                $training_officer = $training_officer_employee;
            }

            return [
                'name' => $name,
                'code' => $code,
                'head' => $head,
                'head_designation' => $head_designation,
                'head_status' => $head_status,
                'training_officer' => $training_officer,
                'training_officer_designation' => $toe_designation,
                'approving_officer' => $approving_officer,
                'officer_in_charge' => $officer_in_charge,
                'oic_designation' => $oic_designation,
                'division'=> new DivisionResource($this->division),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'head' => 'NONE',
            'head_designation' => 'NONE',
            'head_status' => 'NONE',
            'training_officer' => 'NONE',
            'training_officer_designation' => 'NONE',
            'approving_officer' => 'NO RECORD',
            'officer_in_charge' => 'NONE',
            'oic_designation' => 'NONE',
            'division'=> new DivisionResource($this->division),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
