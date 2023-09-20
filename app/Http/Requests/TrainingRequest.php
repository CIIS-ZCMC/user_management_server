<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrainingRequest extends FormRequest
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
        if($this->personal_information_id === null){
            return [
                'inclusive_date' => "required|date:Y-m-d",
                'is_lnd' => "required|boolean",
                'conducted_by' => "required|string|max:255",
                'total_hours' => "required|float"
            ];
        }

        return [
            'inclusive_date' => "required|date:Y-m-d",
            'is_lnd' => "required|boolean",
            'conducted_by' => "required|string|max:255",
            'total_hours' => "required|float",
            'personal_information_id' => "required|string|size:36"
        ];
    }
}
