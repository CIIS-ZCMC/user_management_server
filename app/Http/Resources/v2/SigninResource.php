<?php

namespace App\Http\Resources\v2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\AssignArea;
use App\Models\Designation;
use App\Models\PersonalInformation;

class SigninResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'name' => $this->name(),
            'personal_information' => $this->personalInformation($this->personalInformation),
            'contact' => $this->contact($this->personalInformation),
            'designation' => $this->designation($this->assignedArea),
        ];
    }

    protected function personalInformation(PersonalInformation $personalInformation)
    {
        return [
            'first_name' => $personalInformation->first_name,
            'middle_name' => $personalInformation->middle_name,
            'last_name' => $personalInformation->last_name,
            'name_extension' => $personalInformation->name_extension,
            'sex' => $personalInformation->sex,
            'date_of_birth' => $personalInformation->date_of_birth,
            'place_of_birth' => $personalInformation->place_of_birth,
            'civil_status' => $personalInformation->civil_status,
            'religion' => $personalInformation->religion,
            'citizenship' => $personalInformation->citizenship,
            'country' => $personalInformation->country,
            'height' => $personalInformation->height,
            'weight' => $personalInformation->weight,
            'blood_type' => $personalInformation->blood_type,
            'religion' => $personalInformation->religion,
        ];
    }

    protected function contact(PersonalInformation $personalInformation)
    {
        return [
            'email_address' => $personalInformation->contact->email_address,
            'phone_number' => $personalInformation->contact->phone_number,
        ];
    }

    protected function designation(AssignArea $assignArea)
    {
        return [
            'position' => $this->jobDetails($assignArea->designation, $assignArea->salary_grade_step),
            'area' => [
                'name' => $assignArea->findDetails()['details']['name'],
                'code' => $assignArea->findDetails()['sector'],
            ]
        ];
    }

    protected function jobDetails(Designation $designation, int $salaryGradeStep)
    {
        return [
            'name' => $designation->name,
            'code' => $designation->code,
            'salary_grade' => $designation->salaryGrade->salary_grade_number,
            'step' => $salaryGradeStep,
            'salary_amount' => $designation->salaryGrade->salaryGradeAmount($salaryGradeStep)
        ];
    }
}
