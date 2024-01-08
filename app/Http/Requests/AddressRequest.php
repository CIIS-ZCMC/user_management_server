<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
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
            'address' => 'required|string|max:255',
            'is_residential_and_permanent' => 'required|boolean',
            'is_residential' => 'required|boolean',
            'telephone_no' => 'nullable|string|max:255',
            'personal_information_id' => 'nullable|integer'
        ];
    }
}
