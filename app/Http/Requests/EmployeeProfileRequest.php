<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeProfileRequest extends FormRequest
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
            'emplyee_id' => 'required|integer',
            'profile_url' => 'nullable|string|max:255',
            'date_hired' => 'required|date:Y-m-d',
            'password' => 'required|string|max:255',
            'agency_employee_no' => 'required|string|max:255',
            'employment_type_id' => 'required|string|size:36',
            'personal_information_id' => 'required|string|size:36',
        ];
    }
}
