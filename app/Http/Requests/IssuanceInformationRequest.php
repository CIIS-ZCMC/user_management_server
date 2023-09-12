<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IssuanceInformationRequest extends FormRequest
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
            'license_no' => 'required|string|max:255',
            'govt_issued_id' => 'required|integer',
            'ctct_issued_date' => 'required|date',
            'ctc_issued_at' => 'required|string|max:255',
            'employee_profile_id' => 'required|integer',
        ];
    }
}
