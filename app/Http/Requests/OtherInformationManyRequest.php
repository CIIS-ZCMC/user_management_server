<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OtherInformationManyRequest extends FormRequest
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
            'others.title' => 'required|string|max:255',
            'others.skills_hobbies' => 'nullable|boolean',
            'others.recognition' => 'nullable|boolean',
            'others.organization' => 'nullable|boolean',
            'others.personal_information_id' => 'required|integer'
        ];
    }
}
