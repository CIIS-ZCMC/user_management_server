<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use function PHPSTORM_META\map;

class LeaveApplicationRequest extends FormRequest
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
            'leave_type_id' => 'required|integer',
            'date_from' => 'required|date:Y-m-d',
            'date_to' => 'required|date:Y-m-d',
            'country' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'patient_type' => 'nullable|string|max:255',
            'illness' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:255',
            'without_pay' => 'nullable|boolean',
            'reason' => 'nullable|string'
        ];
    }
}
