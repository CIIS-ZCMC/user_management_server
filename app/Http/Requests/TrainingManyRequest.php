<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrainingManyRequest extends FormRequest
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
            'trainings.title' => 'required|string|max:255',
            'trainings.inclusive_date' => "required|date:Y-m-d",
            'trainings.hours' => "nullable|numeric",
            'trainings.type_of_ld' => "required|boolean",
            'trainings.conducted_by' => "nullable|string|max:255",
            'trainings.personal_information_id' => "required|integer"
        ];
    }
}
