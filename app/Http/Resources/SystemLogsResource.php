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
        $status = $this->status ? 'Complete': "Failed";
        $employee_id = $this->employee->employee_id;

        return [
            'action' => $this->action,
            'module' => $this->module,
            'status' => $status,
            'remarks' => $this->remarks,
            'employee_id' => $employee_id
        ];
    }
}
