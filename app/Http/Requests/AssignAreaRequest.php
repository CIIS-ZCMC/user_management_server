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
            'employee_profile_id' => 'required|string|size:36',
            'division_id' => 'nullable|string|size:36',
            'department_id' => 'nullable|string|size:36',
            'section_id' => 'nullable|string|size:36',
            'unit_id' => 'nullable|string|size:36',
            'designation_id' => 'nullable|string|size:36',
            'plantilla_id' => 'nullable|string|size:36',
        ];
    }
}
