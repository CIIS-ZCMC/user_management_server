<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CtoApplicationRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'remarks' => 'required|string|max:255',
            'reason' => 'nullable|string|max:255',
            'status' => 'required|string|max:255',
            'time_from' => 'required|date_format:H:i',
            'time_to' => 'required|date_format:H:i',
            'date' => 'required|date_format:Y-m-d',
            'purpose' => 'required|string|max:512',
            'applied_credits' => 'nullable|numeric',
        ];

    }
}
