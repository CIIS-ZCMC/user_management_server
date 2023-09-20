<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OtherInformationRequest extends FormRequest
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
        if($this->personal_information_id === null)
        {
            return [
                'hobbies' => 'required|string|max:255',
                'recognition' => 'nullable|string|max:255',
                'organization' => 'nullable|string|max:255',
            ];
        }
        
        return [
            'hobbies' => 'required|string|max:255',
            'recognition' => 'nullable|string|max:255',
            'organization' => 'nullable|string|max:255',
            'personal_information_id' => 'required|string|size:36',
        ];
    }
}
