<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrainingRequest extends FormRequest
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
            'inclusive_date' => "required|date",
            'is_lnd' => "required|bool",
            'conducted_by' => "required|string|max:255",
            'total_hours' => "required|double",
            'personal_information_id' => "required|integer"
        ];
    }
}
