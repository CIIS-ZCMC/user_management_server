<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InActiveEmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $personal_information = $this->employeeProfile->personalInformation;
        $profile_url = null;

        if($this->employeeProfile->profile_url !== null) {
            config('app.server_domain')."/profiles/".$this->employeeProfile->profile_url;
        }

        return [
            'id' => $this->id,
            'name' => $personal_information->employeeName(),
            'profile_url' => $profile_url,
            'date_hired' => $this->date_hired,
            'employment_end_at' => $this->date_resigned,
            'personal_information_id' => $personal_information->id,
        ];
    }
}
