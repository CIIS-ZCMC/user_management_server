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
            'first_in'      => 'required|date_format:H:i:s',
            'first_out'     => 'required|date_format:H:i:s',
            'section_name'  => 'required|string',
        ];
    }
}
