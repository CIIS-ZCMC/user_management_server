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
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'period' => 'required|integer',
            'file_date' => 'required|integer',
            'monthly' => 'required|numeric',
            'annual' => 'required|numeric',
            'is_active' => 'required|boolean',
            'is_special' => 'required|boolean',
            'is_country' => 'required|boolean',
            'is_illness' => 'required|boolean',
            'is_days_recommended' => 'required|boolean',
            'leave_type_requirements' => 'required|array',
            'update_leave_type_requirements' => 'nullable|array',
        ];
    }
}
