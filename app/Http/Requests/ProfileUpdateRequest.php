<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
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
            'employee_profile_id' => 'required|string|size:36',
            'approved_by' => 'required|string|size:36',
            'request_at' => 'required|date:Y-m-d',
            'approved_at' => 'required|date:Y-m-d'
        ];
    }
}
