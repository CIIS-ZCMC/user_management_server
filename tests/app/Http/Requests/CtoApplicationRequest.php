<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CtoApplicationRequest extends FormRequest
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
        $rules = [
            'cto_applications.*.date' => 'required|date_format:Y-m-d',
            'cto_applications.*.applied_credits' => 'required|integer',
            'cto_applications.*.is_am' => 'required|boolean',
            'cto_applications.*.is_pm' => 'required|boolean',
            'cto_applications.*.purpose' => 'required|string|max:512',
            'cto_applications.*.remarks' => 'required|string|max:255'
        ];

        return $rules;
    }
}
