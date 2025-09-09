<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChildManyRequest extends FormRequest
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
            'children.*.first_name' => 'required|string|max:255',
            'children.*.middle_name' => 'nullable|string|max:255',
            'children.*.last_name' => 'required|string|max:255',
            'children.*.gender' => 'required|string|max:255',
            'children.*.birthdate' => 'required|date:Y-m-d',
            'children.*.personal_information_id' => 'required|integer'
        ];
    }
}
