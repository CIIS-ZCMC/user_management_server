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
            'date' => 'required|date_format:Y-m-d',
            'first_in' => 'date_format:H:i|required',
            'first_out' => 'date_format:H:i|required',
            'second_in' => 'date_format:H:i|required',
            'second_out' => 'date_format:H:i|required',
            'remarks' => 'required|string',
            'attachment' => 'required|file',
            'employee_profile_id' => 'required|integer',
        ];
    }
}
