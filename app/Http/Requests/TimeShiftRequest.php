<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimeShiftRequest extends FormRequest
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
            'first_in'          => 'required|date_format:H:i|before_or_equal:first_out',
            'first_out'         => 'required|date_format:H:i|after_or_equal:first_in',
            'second_in'         => 'nullable|date_format:H:i|after_or_equal:first_out',
            'second_out'        => 'nullable|date_format:H:i|after_or_equal:second_in',
        ];
    }
}
