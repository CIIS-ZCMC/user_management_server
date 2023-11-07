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
        $employee_profile = $this->employeeProfile;
        $name = $employee_profile->name;
        $employment_type = $this->employementType->name;

        return [
            'name' => $name,
            'profile_url' => $this->profile_url,
            'date_hired' => $this->date_hired,
            'biometric_id' => $this->biometric_id,
            'employment_end_at' => $this->employment_end_at,
            'employment_type' => $employment_type,
            'personal_information_id' => $this->personal_information_id,
        ];
    }
}
