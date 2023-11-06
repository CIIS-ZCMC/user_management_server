<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LegalInformationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $legal_iq_id = $this->legal_iq_id===null?'NONE':$this->legalInformationQuestion;

        return [
            'employee_profile_id' => $this->employee_profile_id,
            'details' => $this->details,
            'answer' => $this->answer?true:false,
            'legal_iq_id' => $legal_iq_id
        ];
    }
}
