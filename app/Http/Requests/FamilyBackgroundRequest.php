<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FamilyBackgroundRequest extends FormRequest
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
            'spouse' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date:Y-m-d',
            'occupation' => 'nullable|string|max:255',
            'employer' => 'nullable|string|max:255',
            'business_address' => 'nullable|string|max:255',
            'telephone_no' => 'nullable|string|max:255',
            'tin_no' => 'nullable|string|max:255',
            'rdo_no' => 'nullable|string|max:255',
            'father_first_name' => 'required|string|max:255',
            'father_middle_name' => 'nullable|string|max:255',
            'father_last_name' => 'required|string|max:255',
            'father_ext_name' => 'nullable|string|max:255',
            'mother_first_name' => 'required|string|max:255',
            'mother_middle_name' => 'nullable|string|max:255',
            'mother_last_name' => 'required|string|max:255',
            'mother_ext_name' => 'nullable|string|max:255',
            'personal_information_id' => 'required|integer'
        ];
    }
}
