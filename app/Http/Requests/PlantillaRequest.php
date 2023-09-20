<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlantillaRequest extends FormRequest
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
        if($this->job_position_id === null){
            return [
                'planttila_no' => 'required|string|max:255',
                'tranche' => 'nullable|string|max:255',
                'date' => 'required|date:Y-m-d',
                'category' => 'nullable|integer',
            ];
        }

        return [
            'planttila_no' => 'required|string|max:255',
            'tranche' => 'nullable|string|max:255',
            'date' => 'required|date:Y-m-d',
            'category' => 'nullable|integer',
            'job_position_id' => 'required|integer',
        ];
    }
}
