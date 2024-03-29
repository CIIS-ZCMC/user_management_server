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
            'republic_act' => 'required|string',
            'description' => 'required|string',
            'file_date' => 'required|string',
            'file_after' => 'nullable|numeric',
            'file_before' => 'nullable|numeric',
            'is_country' => 'required|boolean',
            'is_illness' => 'required|boolean',
            'is_study' => 'required|boolean',
            'is_days_recommended' => 'required|boolean',
            'leave_credit_year' => 'required|numeric',
            'requirements.*' => 'nullable',
            'attachments.*' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5180',
        ];
    }
}
