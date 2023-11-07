<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IdentificationNumberRequest extends FormRequest
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
            'gsis_id_no' => 'nullable|string|max:255',
            'pag_ibig_id_no' => 'nullable|string|max:255',
            'philhealth_id_no' => 'nullable|string|max:255',
            'sss_id_no' => 'nullable|string|max:255',
            'prc_id_no' => 'nullable|string|max:255',
            'tin_id_no' => 'nullable|string|max:255',
            'rdo_no' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:255',
            'personal_information_id' => 'required|string|size:36',
        ];
    }
}
