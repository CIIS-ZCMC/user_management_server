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
            'personal_information_id' => 'required|string|size:36',
            'level' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'degree_course' => 'required|string|max:255',
            'year_graduated' => 'required|date:Y-m-d',
            'highest_grade' => 'required|string|max:255',
            'inclusive_from' => 'required|date:Y-m-d',
            'inclusive_to' => 'required|date:Y-m-d',
            'academic_honors' => 'required|string|max:255',
        ];
    }
}
