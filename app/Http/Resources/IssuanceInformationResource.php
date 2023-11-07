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
        $employee_id = $this->employeeProfile->employee_id;
        $license_no = $this->license_no===null? 'NONE':$this->license_no;
        $govt_issued_id = $this->govt_issued_id===null? 'NONE':$this->govt_issued_id;
        $ctc_issued_date = $this->ctc_issued_date===null? 'NONE':$this->ctc_issued_date;
        $ctc_issued_at = $this->ctc_issued_at===null? 'NONE':$this->ctc_issued_at;
        $person_administrative_oath = $this->person_administrative_oath===null? 'NONE':$this->person_administrative_oath;

        return [
            'employee_id' => $employee_id,
            'license_no' => $license_no,
            'govt_issued_id' => $govt_issued_id,
            'ctc_issued_date' => $ctc_issued_date,
            'ctc_issued_at' => $ctc_issued_at,
            'person_administrative_oath' => $person_administrative_oath
        ];
    }
}
