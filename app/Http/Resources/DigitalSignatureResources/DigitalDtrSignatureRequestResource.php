<?php

namespace App\Http\Resources\DigitalSignatureResources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DigitalDtrSignatureRequestResource extends JsonResource
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
            'employee_profile' => [
                'id' => $this->whenLoaded('employeeProfile')->id,
                'name' => $this->whenLoaded('employeeProfile')->name(),
                'area' => $this->whenLoaded('employeeProfile')->assignedArea->findDetails(),
                'designation' => $this->whenLoaded('employeeProfile')->findDesignation()
            ],
            'employee_head_profile' => [
                'id' => $this->whenLoaded('employeeHeadProfile')->id,
                'name' => $this->whenLoaded('employeeHeadProfile')->name(),
                'area' => $this->whenLoaded('employeeHeadProfile')->assignedArea->findDetails(),
                'designation' => $this->whenLoaded('employeeHeadProfile')->findDesignation()
            ],
            'dtr_date' => $this->dtr_date,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'approved_at' => $this->approved_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'digital_dtr_signature_request_file' => $this->whenLoaded('digitalDtrSignatureRequestFile'),
        ];
    }
}
