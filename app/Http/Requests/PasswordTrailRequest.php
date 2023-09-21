<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasswordTrailRequest extends FormRequest
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
            'old_password' => 'required|string|max:255',
            'password_created_at' => 'required|date:Y-m-d',
            'expired_at' => 'required|date:Y-m-d',
            'employee_profile_id' => 'required|string|size:36',
        ];
    }
}
