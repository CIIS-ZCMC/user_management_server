<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimeAdjustmentRequest extends FormRequest
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
            'employee_profile_id' => 'required|integer',
            'biometric_id' => 'required|integer',
            'date.*' => 'required|date_format:Y-m-d',
            'firstIn.*' => 'date_format:H:i|nullable',
            'firstOut.*' => 'date_format:H:i|nullable',
            'secondIn.*' => 'date_format:H:i|nullable',
            'secondOut.*' => 'date_format:H:i|nullable',
            'remarks.*' => 'required|string|min:10',
        ];
    }
}
