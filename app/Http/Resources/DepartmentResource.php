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
            $head_designation = $this->headJobSpecification;
            $head_job_specification = $head_designation['name'];
            $head_status = $this->head_status? 'On Site':'On Leave';
            $approving_officer = $this->head_status? 'Head':'OIC';

            $to_job_specification = $this->trainingOfficerJobSpecification;
            $training_officer_job_specification = $to_job_specification['name'];


            $head = $this->head;
            $head_personal_information = $head->personalInformation;
            $head = $head_personal_information->name;

            $officer_in_charge = 'NONE';
            $training_officer = 'NONE';

            if($this->oic_employee_profile_id !== null)
            {
                $oic = $this->oic();
                $oic_personal_information = $oic->personalInformation;
                $officer_in_charge = $oic_personal_information->name;
            }

            if($this->training_officer_employee_profile_id !== null)
            {
                $training_officer_employee = $this->trainingOfficer();
                $to_personal_information = $training_officer_employee->personalInformation;
                $training_officer_employee = $to_personal_information->name;
                $training_officer = $training_officer_employee;
            }

            return [
                'name' => $this->name,
                'code' => $this->code,
                'head_job_specification' => $head_job_specification,
                'head' => $head,
                'head_status' => $head_status,
                'training_officer_job_specification' => $training_officer_job_specification,
                'training_officer' => $training_officer,
                'approving_officer' => $approving_officer,
                'officer_in_charge' => $officer_in_charge
            ];
        }

        $head_designation = $this->headJobSpecification;
        $head_job_specification = $head_designation['name'];

        $to_job_specification = $this->trainingOfficerJobSpecification;
        $training_officer_job_specification = $to_job_specification['name'];

        return [
            'name' => $this->name,
            'code' => $this->code,
            'head_job_specification' => $head_job_specification,
            'head' => 'NONE',
            'head_status' => 'NONE',
            'training_officer_job_specification' => $this->training_officer_job_specification,
            'training_officer' => 'NONE',
            'approving_officer' => 'NO RECORD',
            'officer_in_charge' => 'NONE'
        ];
    }
}
