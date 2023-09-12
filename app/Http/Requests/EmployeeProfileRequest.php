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
        return false;
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
            'date_hired' => 'required|date',
            'job_type' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'password_created_date' => 'required|date',
            'password_expiration_date' => 'required|date',
            'department_id' => 'required|integer',
            'employment_position_id' => 'required|integer',
            'personal_information_id' => 'required|integer',
        ];
    }
}
