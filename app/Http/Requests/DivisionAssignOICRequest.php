<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DivisionAssignOICRequest extends FormRequest
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
            'employee_id' => 'required|string|max:255',
            'attachment' => 'nullable|text',
            'password' => 'required|string|max:255',
            'effective_at' => 'required|date:Y-m-d',
            'end_at' => 'required|date:Y-m-d'
        ];
    }
}