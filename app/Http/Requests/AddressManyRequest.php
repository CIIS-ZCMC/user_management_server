<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressManyRequest extends FormRequest
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
            'is_permanent' => 'required|boolean',
            'address.id' => 'nullable|integer',
            'address.address' => 'required|string|max:255',
            'address.is_residential_and_permanent' => 'required|boolean',
            'address.is_residential' => 'required|boolean',
            'address.telephone_no' => 'nullable|string|max:255',
            'address.personal_information_id' => 'nullable|integer'
        ];
    }
}
