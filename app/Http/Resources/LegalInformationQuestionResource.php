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

        if($this->has_sub_question)
        {
            $sub_question = LegalInformationQuestionResourec::collection($this->subQuestions);

            return [
                'content_question' => $this->content_question,
                'sub_question' => $sub_question,
                'legal_iq_id' => $legal_iq_id
            ];
        }

        $sub_question = null;

        return [
            'content_question' => $this->content_question,
            'has_sub_question' => $sub_question,
            'legal_iq_id' => $legal_iq_id
        ];
    }
}
