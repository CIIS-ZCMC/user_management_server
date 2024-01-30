<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaveTypeRequest extends FormRequest
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
            'is_special' => 'required|boolean',
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'period' => 'required|integer',
            'is_days_recommended' => 'required|boolean',
            'file_date' => 'required|string',
            'is_country' => 'required|boolean',
            'is_illness' => 'required|boolean',
            'leave_credit_year' => 'required|numeric',
            'requirements.*' => 'nullable',
            'attachments.*' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5180',
        ];
    }
}
