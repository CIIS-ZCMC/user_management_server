<?php

namespace App\Http\Resources;

use Carbon\Carbon;
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
            $head_status = $this->head_status? 'On Site':'On Leave';
            $approving_officer = 'Head';
            $head = $this->head->personalInformation->name();

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
                'head' => $head,
                'head_status' => $head_status,
                'approving_officer' => $approving_officer,
                'officer_in_charge' => $officer_in_charge
            ];
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'head' => 'NONE',
            'head_status' => 'No Chief',
            'approving_officer' => 'NONE',
            'officer_in_charge' => 'NONE'
        ];
    }
}
