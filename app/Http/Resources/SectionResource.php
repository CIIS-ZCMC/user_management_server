<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

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
            $approving_officer = 'Chief';
            $supervisor = $this->supervisor->personalInformation->name();
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
                'area_id' => $this->area_id,
                'division'=> new DivisionResource($this->division),
                'department'=> new DepartmentResource($this->department),
                'supervisor' => $supervisor,
                'supervisor_designation' => $this->supervisor->assignedArea->designation,
                'supervisor_profile_url' =>  config("app.server_domain") . "/photo/profiles/".  $this->supervisor->profile_url,
                'supervisor_status' => $supervisor_status,
                'approving_officer' => $approving_officer,
                'officer_in_charge' => $officer_in_charge,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at
            ];
        }
        return [
            'id' => $this->id,
            'name' => $this->name,  
            'code' => $this->code,
            'area_id' => $this->area_id,
            'division'=> new DivisionResource($this->division),
            'department'=>  new DepartmentResource($this->department),
            'supervisor' => 'NONE',
             'supervisor_designation' => 'NONE',
            'supervisor_status' => 'NONE',
            'approving_officer' => 'NONE',
            'officer_in_charge' => 'NONE',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
