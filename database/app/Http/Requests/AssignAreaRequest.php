<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignAreaRequest extends FormRequest
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
            'employee_profile_id' => 'required|integer',
            'division_id' => 'nullable|integer',
            'department_id' => 'nullable|integer',
            'section_id' => 'nullable|integer',
            'unit_id' => 'nullable|integer',
            'designation_id' => 'nullable|integer',
            'plantilla_number' => 'nullable|integer',
            'salary_grade_step' => 'required|integer',
            'effective_at' => 'required|date:Y-m-d'
        ];
    }
}
