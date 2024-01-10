<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoluntaryWorkRequest extends FormRequest
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
            'personal_information_id' => 'required|integer',
            'name_address_organization' => 'required|string|max:255',
            'inclusive_from' => 'required|date:Y-m-d',
            'inclusive_to' => 'required|date:Y-m-d',
            'hours' => 'required|integer',
            'position' => 'required|string|max:255',
        ];
    }
}
