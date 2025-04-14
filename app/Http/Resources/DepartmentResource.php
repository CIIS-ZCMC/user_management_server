<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

class DepartmentResource extends JsonResource
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
            $oic_designation = 'NONE';
            $training_officer = 'NONE';
            $toe_designation = 'NONE';  

            if($this->oic_employee_profile_id !== null)
            {
                $oic = $this->oic;
                $oic_personal_information = $oic->personalInformation;
                $officer_in_charge = $oic_personal_information->name();

                if(Carbon::parse($this->oic_effective_at)->lte(Carbon::now())){
                    $approving_officer = 'officer in charge';
                }
            }

            if($this->training_officer_employee_profile_id !== null)
            {
                $training_officer_employee = $this->trainingOfficer;
                $to_personal_information = $training_officer_employee->personalInformation;
                $training_officer_employee = $to_personal_information->name();
                $training_officer = $training_officer_employee;
            }

            return [
                'id' => $this->id,
                'name' => $name,
                'code' => $code,
                'head' => $head,
                'head_designation' => $this->head->assignedArea->designation,
                'head_status' => $head_status,
                'head_profile_url' => Cache::get("server_domain") . "/photo/profiles/". $this->head->profile_url,
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
            'area_id' => $this->area_id,
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
