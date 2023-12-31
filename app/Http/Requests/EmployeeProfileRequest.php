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
            'personal_information_id' => 'required|integer',
            'employment_type_id' => 'required|integer',
            'attachment' => 'nullable|file|mimes:jpeg,png,pdf,doc,docx',
            'date_hired' => 'required|date:Y-m-d',
            'allow_time_adjustment' => 'required|integer',
            'plantilla_number_id' => 'nullable|integer',
            'sector' => 'required|string|max:255',
            'sector_id' => 'required|integer',
            'salary_grade_step' => 'required|integer',
            'designation_id' => 'nullable|integer'
        ];
    }
}
