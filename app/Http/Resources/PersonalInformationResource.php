<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonalInformationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee = $this->employeeProfile;
        $employee_id = $employee['employee_id'];

        return [
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'name_extension' => $this->name_extension,
            'years_of_service' => $this->years_of_service,
            'name_title' => $this->name_title,
            'sex' => $this->sex,
            'date_of_birth' => $this->date_of_birth,
            'place_of_birth' => $this->place_of_birth,
            'civil_status' => $this->civil_status,
            'date_of_marriage' => $this->date_of_marriage,
            'citizenship' => $this->citizenship,
            'country' => $this->country,
            'height' => $this->height,
            'weight' => $this->weight,
            'blood_type' => $this->blood_type,
            'employee_id' => $this->employee_id
        ];
    }
}
