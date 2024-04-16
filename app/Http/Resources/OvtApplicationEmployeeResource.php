<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OvtApplicationEmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {

        $area = $this->employeeProfile->assignedArea->findDetails();

        return [

            "remarks" => $this->remarks,
            "date" => $this->date,
            "employee_profile" => [
                'employee_id' => $this->employeeProfile->id,
                'name' => $this->employeeProfile->personalInformation->name(),
                'designation_name' => $this->employeeProfile->assignedArea->designation->name,
                'designation_code' => $this->employeeProfile->assignedArea->designation->code,
                'area' => $area['details']->name,
                'area_code' => $area['details']->code,
                'area_sector' => $area['sector'],
                'profile_url' => Cache::get("server_domain") . "/photo/profiles/" . $this->employeeProfile->profile_url,
            ],

        ];
    }
}
