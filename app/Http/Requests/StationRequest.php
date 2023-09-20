<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StationRequest extends FormRequest
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
        if($this->department_id === null){
            return [
                'code' => 'required|string|max:255',
                'name' => 'required|string|max:255'
            ];
        }

        return [
            'code' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'department_id' => 'nullable|string|size:36'
        ];
    }
}
