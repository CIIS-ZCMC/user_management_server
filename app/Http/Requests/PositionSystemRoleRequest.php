<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PositionSystemRoleRequest extends FormRequest
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
            'designation_id' => 'required|string|size:36',
            'system_role_id' => 'required|string|size:36',
        ];
    }
}
