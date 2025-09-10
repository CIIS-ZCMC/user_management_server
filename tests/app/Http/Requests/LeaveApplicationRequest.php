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
        // $rules = [];
        // $rules = [];

        // foreach ($this->input() as $index => $data) {
        //     $rules["$index.leave_type_id"] = 'required|integer';
        //     $rules["$index.date_from"] = 'required|date_format:Y-m-d';
        //     $rules["$index.date_to"] = 'required|date_format:Y-m-d';
        //     $rules["$index.country"] = 'nullable|string|max:255';
        //     $rules["$index.city"] = 'nullable|string|max:255';
        //     $rules["$index.patient_type"] = 'nullable|string|max:255';
        //     $rules["$index.illness"] = 'nullable|string|max:255';
        //     $rules["$index.applied_credits"] = 'nullable|numeric';
        //     $rules["$index.status"] = 'nullable|string|max:255';
        //     $rules["$index.remarks"] = 'nullable|string|max:255';
        //     $rules["$index.without_pay"] = 'nullable|boolean';
        //     $rules["$index.reason"] = 'nullable|string';
        //     $rules["$index.attachments.*.attachment"] = 'required|file|mimes:jpeg,png,jpg,pdf|max:2048';
        //     $rules["$index.attachments.*.name"] = 'required|string|max:255';
        // }
        // foreach ($this->input() as $index => $data) {
        //     $rules["$index.leave_type_id"] = 'required|integer';
        //     $rules["$index.date_from"] = 'required|date_format:Y-m-d';
        //     $rules["$index.date_to"] = 'required|date_format:Y-m-d';
        //     $rules["$index.country"] = 'nullable|string|max:255';
        //     $rules["$index.city"] = 'nullable|string|max:255';
        //     $rules["$index.patient_type"] = 'nullable|string|max:255';
        //     $rules["$index.illness"] = 'nullable|string|max:255';
        //     $rules["$index.applied_credits"] = 'nullable|numeric';
        //     $rules["$index.status"] = 'nullable|string|max:255';
        //     $rules["$index.remarks"] = 'nullable|string|max:255';
        //     $rules["$index.without_pay"] = 'nullable|boolean';
        //     $rules["$index.reason"] = 'nullable|string';
        //     $rules["$index.attachments.*.attachment"] = 'required|file|mimes:jpeg,png,jpg,pdf|max:2048';
        //     $rules["$index.attachments.*.name"] = 'required|string|max:255';
        // }

        // return $rules;

        return [
            "date_from" => "required|before_or_equal:date_to",
            "date_to" => "required|after_or_equal:date_from",
            "country" => "nullable|string|max:255",
            "illness" => "nullable|string|max:255",
            "city" => "nullable|string|max:255",
            "is_outpatient" => "nullable|boolean",
            "is_masters" => "nullable|boolean",
            "is_board" => "nullable|boolean",
            "is_commutation" => "nullable|boolean",
            "without_pay" => "nullable|boolean",
            "oic_id" => "nullable|string|max:255",
            "test_oic_id" => "nullable|string|max:255",
            "remarks" => "nullable|string",
            "attachment_name.*" => "required|string|max:255",
            "attachment_file.*" => "required|file|mimes:jpeg,png,jpg,pdf|max:10000",
            "is_printed" => "nullable|boolean",
            "created_at" => "nullable|string",
            "received_at" => "nullable|string",
        ];
    }
}
