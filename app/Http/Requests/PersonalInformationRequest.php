<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PersonalInformationRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'attachment' => 'nullable|file|mimes:jpeg,png,pdf,doc,docx',
            'first_name' => 'required|string|max:255',  
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'name_extension' => 'nullable|string|max:255',
            'years_of_service' => 'nullable|string|max:255',
            'name_title' => 'nullable|string|max:255',
            'sex' => 'required|string|max:255',
            'date_of_birth' => 'required|date:Y-m-d',
            'place_of_birth' => 'required|string|max:255',
            'civil_status' => 'required|string|max:255',
            'date_of_marriage' => 'nullable|date:Y-m-d',
            'citizenship' => 'required|string|max:255',
            'height' => 'required|integer',
            'weight' => 'required|integer',
            'blood_type' => 'nullable|string|max:255',
            'date_hired' => 'required|date:Y-m-d',
            'r_address' => 'required|string|max:255',
            'r_telephone' => 'nullable|string|max:255',
            'r_zip_code' => 'required|string|max:255',
            'is_res_per' => 'nullable|integer',
            'p_address' => 'nullable|string|max:255',
            'p_telephone' => 'nullable|string|max:255',
            'p_zip_code' => 'required|string|max:255',
        ];
    }
}
