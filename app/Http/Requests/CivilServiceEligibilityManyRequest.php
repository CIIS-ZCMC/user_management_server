<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CivilServiceEligibilityManyRequest extends FormRequest
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
            'eligibilities.*.career_service' => 'required|string|max:255',
            'eligibilities.*.rating' => 'nullable|numeric',
            'eligibilities.*.date_of_examination' => 'required|date:Y-m-d',
            'eligibilities.*.place_of_examination' => 'required|string|max:255',
            'eligibilities.*.license_number' => 'nullable|string|max:255',
            'eligibilities.*.license_release_at' => 'nullable|date:Y-m-d',
        ];
    }
}
