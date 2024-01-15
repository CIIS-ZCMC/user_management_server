<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MemorandumsRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'attachment' => 'nullable|file|mimes:jpeg,png,pdf,doc,docx',
            'effective_at' => 'nullable|date:Y-m-d'
        ];
    }
}
