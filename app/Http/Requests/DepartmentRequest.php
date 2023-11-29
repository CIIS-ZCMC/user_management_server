<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'attachment' => 'nullable|file|mimes:jpeg,png,pdf,doc,docx',
            'head_job_specification' => 'required|string|max:255',
            'training_officer_job_specification' => 'required|string|max:255',
            'division_id' => 'required|integer'
        ];
    }
}
