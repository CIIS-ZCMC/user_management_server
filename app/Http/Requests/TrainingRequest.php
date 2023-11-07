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
            'title' => 'required|string|max:255',
            'inclusive_date' => "required|date:Y-m-d",
            'hours' => "nullable|float",
            'type_of_ld' => "required|boolean",
            'conducted_by' => "nullable|string|max:255",
            'personal_information_id' => "required|string|size:36"
        ];
    }
}
