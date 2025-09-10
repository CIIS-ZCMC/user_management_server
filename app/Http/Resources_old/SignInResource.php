<?php

namespace App\Http\Resources;

use App\Models\LegalInformation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SignInResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee_profile = $this->employeeProfile;
        $position = $employee_profile->employmentPosition->name;
        $department = $employee_profile->department->name;
        $salary_grade = $employee_profile->assignedArea->designation->salaryGrade->salary_grade_number;
        $legal_informations = LegalInformation::with(['legalInformationQuestion' => function ($query) {
            $query->orderBy('order_by', 'asc');
        }])->where('personal_information_id', $this->personalInformation->id)->get();

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'name' => $this->personalInformation->name(),
            'department' => $department,
            'position' => $position,
            'salary_grade' => $salary_grade,
            'personal_information' => [
                'personal_information' => new PersonalInformationResource($this->personalInformation),
                'contact' => new ContactResource($this->personalInformation->contact),
                'address' => AddressResource::collection($this->personalInformation->addresses),
                'identification' => new IdentificationNumberResource($this->personalInformation->identificationNumber),
            ],
            'family_background' => [
                'family' => new FamilyBackGroundResource($this->personalInformation->familyBackground),
                'children' => ChildResource::collection($this->personalInformation->children),
                'education' => new EducationalBackgroundResource($this->personalInformation->educationBackground),
            ],
            'affiliation_and_others' => [
                'civil_service_eligibility' => CivilServiceEligibilityResource::collection($this->personalInformation->civilServiceEligibility),
                'work_experience' => WorkExperienceResource::collection($this->personalInformation->workExperience),
                'voluntary_work_or_involvement' => VoluntaryWorkResource::collection($this->personalInformation->voluntaryWork),
                'training' => TrainingResource::collection($this->personalInformation->training),
                'other_information' => OtherInformationResource::collection($this->personalInformation->otherInformation),
            ],
            'legal_information' => [
                'legal_information' => EmployeeLegalInformationResource::collection($legal_informations),
                'references' => ReferenceResource::collection($this->personalInformation->references),
                'issuance_information' => new IssuanceInformationResource($this->issuanceInformation),
            ]
        ];
    }

    public static function collection($resource)
    {
        return parent::collection($resource)->withoutWrapping();
    }
}
