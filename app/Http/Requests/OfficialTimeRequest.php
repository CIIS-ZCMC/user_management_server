<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OfficialTimeRequest extends FormRequest
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
            'date_from'                 => 'required|date|before_or_equal:date_to',
            'date_to'                   => 'required|date|after_or_equal:date_from',
            'time_from'                 => 'required|date_format:H:i|before_or_equal:time_to',
            'time_to'                   => 'required|date_format:H:i|after_or_equal:time_from',
            'purpose'                   => 'required|string',
            'personal_order_file'       => 'required|file',
            'certificate_of_appearance' => 'required|file',
        ];
    }
}
