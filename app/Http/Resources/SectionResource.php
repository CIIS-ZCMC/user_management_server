<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    { 
        if($this->supervisor_employee_profile_id !== null)
        {
            $name = $this->name;
            $code = $this->code;
            $supervisor_status = $this->supervisor_status? 'On Site':'On Leave';
            $approving_officer = $this->supervisor_status? 'Chief':'OIC';

            $supervisor = $this->supervisor;
            $supervisor_personal_information = $supervisor->personalInformation;
            $supervisor = $supervisor_personal_information->name;

            $officer_in_charge = 'NONE';

            if($this->oic_employee_profile_id !== null)
            {
                $oic = $this->oic();
                $oic_personal_information = $oic->personalInformation;
                $officer_in_charge = $oic_personal_information->name;
            }

            return [
                'id' => $this->id,
                'name' => $name,
                'code' => $code,
                'supervisor' => $supervisor,
                'supervisor_status' => $supervisor_status,
                'approving_officer' => $approving_officer,
                'officer_in_charge' => $officer_in_charge
            ];
        }
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'supervisor' => 'NONE',
            'supervisor_status' => 'NONE',
            'approving_officer' => 'NONE',
            'officer_in_charge' => 'NONE'
        ];
    }
}
