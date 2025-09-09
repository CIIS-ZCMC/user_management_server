<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoluntaryWorkManyRequest extends FormRequest
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
            'voluntary_work_experiences.personal_information_id' => 'required|integer',
            'voluntary_work_experiences.name_address_organization' => 'required|string|max:255',
            'voluntary_work_experiences.inclusive_from' => 'required|date:Y-m-d',
            'voluntary_work_experiences.inclusive_to' => 'required|date:Y-m-d',
            'voluntary_work_experiences.hours' => 'required|string|max:255',
            'voluntary_work_experiences.position' => 'nullable|string|max:255',
        ];
    }
}
