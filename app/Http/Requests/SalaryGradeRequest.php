<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalaryGradeRequest extends FormRequest
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
            'salary_grade_number' => 'required|integer',
            'one' => 'required|numeric',
            "two" => 'required|numeric',
            "three" => 'required|numeric',
            "four" => 'required|numeric',
            "five" => 'required|numeric',
            "six" => 'required|numeric',
            "seven" => 'required|numeric',
            "eight" => 'required|numeric',
            'tranch' => 'required|string|max:2555',
            'effective_at' => 'required|date:Y-m-d',
        ];
    }
}
