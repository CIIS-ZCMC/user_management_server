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
            'date_hired' => 'required|date:Y-m-d',
            'job_type' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'password_created_date' => 'required|date:Y-m-d',
            'password_expiration_date' => 'required|date:Y-m-d',
            'department_id' => 'required|string|size:36',
            'employment_position_id' => 'required|string|size:36',
            'personal_information_id' => 'required|string|size:36',
        ];
    }
}
