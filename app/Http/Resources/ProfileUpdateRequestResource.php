<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileUpdateRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'employee_id' => $this->employee->employee_id,
            'approved_by' => $this->approved_by === null? 'Pending': $this->approved_by->employee_id,
            'table_name' => $this->table_name,
            'data_id' => $this->data_id,
            'type_new_or_replace' => $this->type_new_or_replace
        ];
    }
}
