<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CivilServiceEligibilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'carrer_service' => 'required|string|max:255',
            'rating' => 'required|double',
            'date_of_examination' => 'required|date',
            'place_of_examination' => 'required|string|max:255',
            'license' => 'required|string|max:255',
        ];
    }
}
