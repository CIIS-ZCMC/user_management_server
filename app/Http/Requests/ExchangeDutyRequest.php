<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExchangeDutyRequest extends FormRequest
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
            'requested_date_to_swap' => 'required|date',
            'requested_date_to_duty' => 'required|date',
            'requested_employee_id' => 'required|integer',
            'reliever_employee_id' => 'required|integer',
            'requested_schedule_id' => 'required|integer',
            'reliever_schedule_id' => 'required|integer',
        ];
    }
}
