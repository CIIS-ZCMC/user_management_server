<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LegalInformationQuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $legal_iq_id = $this->legal_iq_id === null?"NONE":$this->legalIQ;

        return [
            'content_question' => $this->content_question,
            'is_sub_question' => $this->is_sub_question?true:false,
            'legal_iq_id' => $legal_iq_id
        ];
    }
}
