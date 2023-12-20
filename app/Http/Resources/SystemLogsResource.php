<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemLogsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'module_id' => $this->module_id,
            'status' => $this->status ? 'Complete': "Failed",
            'ip_address' => $this->ip_address,
            'remarks' => $this->remarks,
            'employee_id' => $this->employeeProfile->employee_id
        ];
    }
}
