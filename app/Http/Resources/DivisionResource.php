<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DivisionResource extends JsonResource
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
            $designation = $this->chiefRequirement();
            $job_specification = $designation['name'];
            $chief_status = $this->chief_status? 'On Site':'On Leave';
            $approving_officer = $this->chief_status? 'Chief':'OIC';

            $chief = $this->chief;
            $chief_personal_information = $chief->personalInformation;
            $chief = $chief_personal_information->name;

            $officer_in_charge = 'NONE';

            if($this->oic_employee_profile_id !== null)
            {
                $oic = $this->oic();
                $oic_personal_information = $oic->personalInformation;
                $officer_in_charge = $oic_personal_information->name;
            }

            return [
                'name' => $name,
                'code' => $code,
                'job_specification' => $job_specification,
                'chief' => $chief,
                'chief_status' => $chief_status,
                'approving_officer' => $approving_officer,
                'officer_in_charge' => $officer_in_charge
            ];
        }

        $chief_designation = $this->chiefRequirement();
        $job_specification = $chief_designation['name'];


        return [
            'code' => $this->code,
            'name' => $this->name,
            'job_specification' => $job_specification,
            'chief' => 'NONE',
            'chief_status' => 'No Chief',
            'approving_officer' => 'NONE',
            'officer_in_charge' => 'NONE'
        ];
    }
}
