<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

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
                'area_id' => $this->area,
                'head' => $head,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
                'head_profile_url' => config("app.server_domain") . "/photo/profiles/".  $this->head->profile_url,
                'head_designation' => $this->head->assignedArea->designation,
                'section' => new SectionResource($this->section),
                'head_status' => $head_status,
                'approving_officer' => $approving_officer,
                'officer_in_charge' => $officer_in_charge
            ];
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'area_id' => $this->area_id,
            'name' => $this->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'section' => new SectionResource($this->section),
            'head' => 'NONE',
            'head_designation' => 'NONE',
            'head_status' => 'No Chief',
            'approving_officer' => 'NONE',
            'officer_in_charge' => 'NONE'
        ];
    }
}
