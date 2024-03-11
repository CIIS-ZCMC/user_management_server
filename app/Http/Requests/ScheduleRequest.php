<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleRequest extends FormRequest
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
            // 'employee.*.employee_id' => 'required|array',
            // 'employee*.employee_id' => 'required|array',
            // 'month' => 'required|date_format:M|',
            // 'time_shift_id' => 'required|integer',
            // 'is_weekend.*' => 'required|integer',
            // 'date_start' => 'nullable|date_format:Y-m-d',
            // 'date_end' => 'nullable|date_format:Y-m-d',
            // 'selected_days.*' => 'nullable|string',
        ];
    }
}
