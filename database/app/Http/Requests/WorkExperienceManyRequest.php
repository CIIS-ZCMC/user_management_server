<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkExperienceManyRequest extends FormRequest
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
            'work_experiences.date_from' => 'required|date:Y-m-d',
            'work_experiences.date_to' => 'required|date:Y-m-d',
            'work_experiences.position_title' => "required|string|max:255",
            'work_experiences.appointment_status' => "required|string|max:255",
            'work_experiences.salary' => "required|string|max:255",
            'work_experiences.salary_grade_and_step' => "nullable|string|max:255",
            'work_experiences.company' => "required|string|max:255",
            'work_experiences.government_office' => "required|string|max:255",
            'work_experiences.personal_information_id' => "required|integer"
        ];
    }
}
