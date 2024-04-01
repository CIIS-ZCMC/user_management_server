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
            'personal_information.first_name' => 'required|string|max:255',
            'personal_information.middle_name' => 'nullable|string|max:255',
            'personal_information.last_name' => 'required|string|max:255',
            'personal_information.name_extension' => 'nullable|string|max:255',
            'personal_information.years_of_service' => 'nullable|string|max:255',
            'personal_information.name_title' => 'nullable|string|max:255',
            'personal_information.sex' => 'required|string|max:255',
            'personal_information.date_of_birth' => 'required|date:Y-m-d',
            'personal_information.place_of_birth' => 'required|string|max:255',
            'personal_information.civil_status' => 'required|string|max:255',
            'personal_information.citizenship' => 'required|string|max:255',
            'personal_information.height' => 'nullable|numeric',
            'personal_information.weight' => 'nullable|numeric',
            'personal_information.blood_type' => 'nullable|string|max:255',
            'personal_information.r_address' => 'required|string|max:255',
            'personal_information.r_telephone' => 'nullable|string|max:255',
            'personal_information.r_zip_code' => 'nullable|string|max:255',
            'personal_information.is_res_per' => 'nullable|integer',
            'personal_information.p_address' => 'nullable|string|max:255',
            'personal_information.p_telephone' => 'nullable|string|max:255',
            'personal_information.p_zip_code' => 'nullable|string|max:255',
            //Family Background
            'family_background.spouse' => 'nullable|string|max:255',
            'family_background.address' => 'nullable|string|max:255',
            'family_background.zip_code' => 'nullable|string|max:255',
            'family_background.date_of_birth' => 'nullable|date:Y-m-d',
            'family_background.occupation' => 'nullable|string|max:255',
            'family_background.employer' => 'nullable|string|max:255',
            'family_background.business_address' => 'nullable|string|max:255',
            'family_background.telephone_no' => 'nullable|string|max:255',
            'family_background.tin_no' => 'nullable|string|max:255',
            'family_background.rdo_no' => 'nullable|string|max:255',
            'family_background.father_first_name' => 'nullable|string|max:255',
            'family_background.father_middle_name' => 'nullable|string|max:255',
            'family_background.father_last_name' => 'nullable|string|max:255',
            'family_background.father_ext_name' => 'nullable|string|max:255',
            'family_background.mother_first_name' => 'required|string|max:255',
            'family_background.mother_middle_name' => 'nullable|string|max:255',
            'family_background.mother_last_name' => 'required|string|max:255',
            'family_background.mother_ext_name' => 'nullable|string|max:255',
            //Children
            'children.*.first_name' => 'required|string|max:255',
            'children.*.middle_name' => 'nullable|string|max:255',
            'children.*.last_name' => 'required|string|max:255',
            'children.*.gender' => 'required|string|max:255',
            'children.*.birthdate' => 'required|date:Y-m-d',
            //Contact
            'contact.phone_number' => 'required|string|max:255',  
            'contact.email_address' => 'nullable|email|max:255',
            //Educations
            'educations.*.level' => 'required|string|max:255',
            'educations.*.name' => 'required|string|max:255',
            'educations.*.degree_course' => 'nullable|string|max:255',
            'educations.*.year_graduated' => 'nullable|date:Y-m-d',
            'educations.*.highest_grade' => 'nullable|string|max:255',
            'educations.*.inclusive_from' => 'nullable|date:Y-m-d',
            'educations.*.inclusive_to' => 'nullable|date:Y-m-d',
            'educations.*.academic_honors' => 'nullable|string|max:255',
            //Identification
            'identification.gsis_id_no' => 'nullable|string|max:255',
            'identification.pag_ibig_id_no' => 'nullable|string|max:255',
            'identification.philhealth_id_no' => 'nullable|string|max:255',
            'identification.sss_id_no' => 'nullable|string|max:255',
            'identification.prc_id_no' => 'nullable|string|max:255',
            'identification.tin_id_no' => 'nullable|string|max:255',
            'identification.rdo_no' => 'nullable|string|max:255',
            'identification.bank_account_no' => 'nullable|string|max:255',
            //Work Experience
            'work_experiences.*.date_from' => 'required|date:Y-m-d',
            'work_experiences.*.date_to' => 'required|date:Y-m-d',
            'work_experiences.*.position_title' => "required|string|max:255",
            'work_experiences.*.appointment_status' => "required|string|max:255",
            'work_experiences.*.salary' => "required|string|max:255",
            'work_experiences.*.salary_grade_and_step' => "nullable|string|max:255",
            'work_experiences.*.company' => "required|string|max:255",
            'work_experiences.*.government_office' => "required|string|max:255",
            //Voluntary Work
            'voluntary_work.*.name_address_organization' => 'required|string|max:255',
            'voluntary_work.*.inclusive_from' => 'required|date:Y-m-d',
            'voluntary_work.*.inclusive_to' => 'required|date:Y-m-d',
            'voluntary_work.*.hours' => 'required|string|max:255',
            'voluntary_work.*.position' => 'nullable|string|max:255',
            //Others
            'others.*.title' => 'required|string|max:255',
            'others.*.skills_hobbies' => 'nullable|boolean',
            'others.*.recognition' => 'nullable|boolean',
            'others.*.organization' => 'nullable|boolean',
            //Legal Information
            'legal_information.*.details' => 'nullable|text',
            'legal_information.*.answer' => 'nullable|boolean',
            'legal_information.*.legal_iq_id' => 'required|integer',
            //Training
            'trainings.*.title' => 'required|string|max:255',
            'trainings.*.inclusive_from' => "required|date:Y-m-d",
            'trainings.*.inclusive_to' => "required|date:Y-m-d",
            'trainings.*.hours' => "nullable|numeric",
            'trainings.*.type_of_ld' => "required|string|max:255",
            'trainings.*.conducted_by' => "nullable|string|max:255",
            //Reference
            'reference.*.name' => 'required|string|max:255',
            'reference.*.address' => 'required|string|max:255',
            'reference.*.contact_no' => 'required|string|max:255',
            //Civil Service Eligibility
            'eligibilities.*.career_service' => 'required|string|max:255',
            'eligibilities.*.rating' => 'nullable|numeric',
            'eligibilities.*.date_of_examination' => 'required|date:Y-m-d',
            'eligibilities.*.place_of_examination' => 'required|string|max:255',
            'eligibilities.*.license_number' => 'nullable|string|max:255',
            'eligibilities.*.license_release_at' => 'nullable|date:Y-m-d',
            //Issuance Information
            'issuance_information.license_no' => 'required|string|max:255',
            'issuance_information.govt_issued_id' => 'required|string',
            'issuance_information.ctc_issued_date' => 'required|date:Y-m-d',
            'issuance_information.ctc_issued_at' => 'required|string|max:255',
            'issuance_information.person_administrative_oath' => 'required|string|max:255',
            //Employee Profile
            'date_hired' => "required|date:Y-m-d",
            'employment_type_id' => 'required|integer|size:36',
            'designation_id' => 'required|integer|size:36',
            'plantilla_number_id' => 'nullable|integer|size:36',
            'allow_time_adjustment' => 'required|integer',
            'attachment' => 'nullable|file|mimes:jpeg,png,pdf,doc,docx'
        ];
    }
}
