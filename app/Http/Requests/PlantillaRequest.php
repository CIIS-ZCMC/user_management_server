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
        return [
            'designation_id' => 'required|integer',
            'slot' => 'nullable|integer',
            'effective_at' => 'required|date:Y-m-d',
            'education' => 'required|string|max:255',
            'training' => 'nullable|string|max:255',
            'experience' => 'nullable|numeric',
            'eligibility' => 'nullable|string|max:255',
            'competency' => 'nullable|string|max:255',
            'plantilla_number' => 'nullable|array'
        ];
    }
}
