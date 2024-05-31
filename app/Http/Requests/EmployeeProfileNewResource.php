<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeProfileNewResource extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //Personal Information
            'personal_information' => 'required|string',
            //Family Background
            'family_background' => 'nullable|string',
            //Children
            'children' => 'required|string',
            //Contact
            'contact' => 'required|string',
            //Educations
            'educations' => 'required|string',
            //Identification
            'identification' => 'required|string',
            //Work Experience
            'work_experiences' => 'required|String',
            //Voluntary Work
            'voluntary_work' => 'required|string',
            //Others
            'others' => 'required|string',
            //Legal Information
            'legal_information' => 'required|string',
            //Training
            'trainings' => 'required|string',
            //Reference
            'reference' => 'required|string',
            //Civil Service Eligibility
            'eligibilities' => 'required|string',
            //Issuance Information
            'issuance_information' => 'required|string',
            //Employee Profile
            'date_hired' => "required|date:Y-m-d",
            'employment_type_id' => 'required|integer',
            'designation_id' => 'required|integer',
            // 'plantilla_number_id' => 'nullable|integer',
            'allow_time_adjustment' => 'required|integer',
            'solo_parent' => 'nullable|integer',
            'salary_grade_id' => 'nullable|number',
            'salary_grade_step' => 'nullable|integer',
            'sector' => 'nullable|string',
            // 'attachment' => 'nullable|file|mimes:jpeg,png,pdf,doc,docx'
        ];
    }
}
