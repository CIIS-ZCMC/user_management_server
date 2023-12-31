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
            'license_no' => 'nullable|string|max:255',
            'govt_issued_id' => 'nullable|integer',
            'ctct_issued_date' => 'nullable|date:Y-m-d',
            'ctc_issued_at' => 'nullable|string|max:255',
            'person_administrative_oath' => 'nullable|string|max:255',
            'employee_profile_id' => 'required|integer',
        ];
    }
}
