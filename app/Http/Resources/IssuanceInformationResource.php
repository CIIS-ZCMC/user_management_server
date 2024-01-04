<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssuanceInformationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'employee_id' => $this->employeeProfile->employee_id,
            'license_no' => $this->license_no??"NONE",
            'govt_issued_id' => $this->govt_issued_id??"NONE",
            'ctc_issued_date' => $this->ctc_issued_date??"NONE",
            'ctc_issued_at' => $this->ctc_issued_at??"NONE",
            'person_administrative_oath' => $this->person_administrative_oath??"NONE"
        ];
    }
}
