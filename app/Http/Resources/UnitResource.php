<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if($this->head_employee_profile_id !== null)
        {
            $name = $this->name;
            $code = $this->code;
            $designation = $this->headJobSpecification;
            $job_specification = $designation['name'];
            $head_status = $this->head_status? 'On Site':'On Leave';
            $approving_officer = $this->head_status? 'Head':'OIC';

            $head = $this->head;
            $head_personal_information = $head->personalInformation;
            $head = $head_personal_information->name;

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
                'head' => $head,
                'head_status' => $head_status,
                'approving_officer' => $approving_officer,
                'officer_in_charge' => $officer_in_charge
            ];
        }

        $head_designation = $this->headRequirement;
        $job_specification = $head_designation['name'];


        return [
            'code' => $this->code,
            'name' => $this->name,
            'job_specification' => $job_specification,
            'head' => 'NONE',
            'head_status' => 'No Chief',
            'approving_officer' => 'NONE',
            'officer_in_charge' => 'NONE'
        ];
    }
}
