<?php

namespace App\Http\Resources\DigitalSignatureResources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DigitalSignedDtrResource extends JsonResource
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
            'employee_profile_id' => $this->employee_profile_id,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'month_year' => $this->month_year,
            'signer_type' => $this->signer_type,
            'status' => $this->status,
            'signed_at' => $this->signed_at,
        ];
    }
}
