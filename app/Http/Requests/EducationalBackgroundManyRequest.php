<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EducationalBackgroundManyRequest extends FormRequest
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
            'educations.*.level' => 'required|string|max:255',
            'educations.*.name' => 'required|string|max:255',
            'educations.*.degree_course' => 'nullable|string|max:255',
            'educations.*.year_graduated' => 'nullable|date:Y-m-d',
            'educations.*.highest_grade' => 'nullable|string|max:255',
            'educations.*.inclusive_from' => 'nullable|date:Y-m-d',
            'educations.*.inclusive_to' => 'nullable|date:Y-m-d',
            'educations.*.academic_honors' => 'nullable|string|max:255',
            'educations.*.personal_information_id' => 'required|integer',
        ];
    }
}
