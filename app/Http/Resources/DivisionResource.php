<?php

namespace App\Http\Resources;

use Carbon\Carbon;
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
            $chief_status = $this->chief_status? 'On Site':'On Leave';
            $approving_officer = 'Chief';

            $chief = $this->chief;
            $chief_personal_information = $chief->personalInformation;
            $chief = $chief_personal_information->name();

            $officer_in_charge = 'NONE';

            if($this->oic_employee_profile_id !== null)
            {
                $oic = $this->oic;
                $oic_personal_information = $oic->personalInformation;
                $officer_in_charge = $oic_personal_information->name();

                if(Carbon::parse($this->oic_effective_at)->lte(Carbon::now())){
                    $approving_officer = 'officer in charge';
                }
            }

            return [
                'id' => $this->id,
                'name' => $name,
                'code' => $code,
                'chief' => $chief,
                'chief_designation' => $this->chief->assignedArea->designation,
                'chief_profile_url' => env('SERVER_DOMAIN') . "/photo/profiles/". $this->chief->profile_url,
                'chief_status' => $chief_status,
                'approving_officer' => $approving_officer,
                'officer_in_charge' => $officer_in_charge
            ];
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'chief' => 'NONE',  
            'chief_profile_url' => 'NONE',
            'chief_designation' => 'NONE',
            'chief_status' => 'No Chief',
            'approving_officer' => 'NONE',
            'officer_in_charge' => 'NONE'
        ];
    }
}
