<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EducationalBackgroundRequest extends FormRequest
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
            'level' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'degree_course' => 'nullable|string|max:255',
            'year_graduated' => 'nullable|date:Y-m-d',
            'highest_grade' => 'nullable|string|max:255',
            'inclusive_from' => 'nullable|date:Y-m-d',
            'inclusive_to' => 'nullable|date:Y-m-d',
            'academic_honors' => 'nullable|string|max:255',
            'personal_information_id' => 'required|integer',
        ];
    }
}
