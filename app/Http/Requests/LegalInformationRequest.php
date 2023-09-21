<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LegalInformationRequest extends FormRequest
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
        if($this->employee_profile_id===null){
            return [
                'details' => 'nullable|text',
                'answer' => 'required|boolean',
                'legal_iq_id' => 'required|string|size:36',
            ];
        }

        return [
            'employee_profile_id' => 'required|string|size:36',
            'details' => 'nullable|text',
            'answer' => 'required|boolean',
            'legal_iq_id' => 'required|string|size:36',
        ];
    }
}
