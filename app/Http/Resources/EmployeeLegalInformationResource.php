<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeLegalInformationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'legal_information_id' => $this->id,
            'legal_information_question' => new LegalInformationQuestionResource($this->legalInformationQuestion),
            'answer' => $this->answer,
            'details' => $this->details??"NONE",
            'created_at' => $this->created_at
        ];
    }
}
