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
        $personal_information = $this->personalInformation;
        $employment_type = $this->employmentType->name;

        return [
            'id' => $this->id,
            'name' => $personal_information->employeeName(),
            'profile_url' => config('app.server_domain')."/profiles/".$this->profile_url,
            'date_hired' => $this->date_hired,
            'biometric_id' => $this->biometric_id,
            'employment_end_at' => $this->employment_end_at,
            'employment_type' => $employment_type,
            'personal_information_id' => $this->personal_information_id,
        ];
    }
}
