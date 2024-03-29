<?php

namespace App\Http\Resources;

use App\Models\LegalInformationQuestion;
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
        if($this->has_sub_question)
        {
            $sub_question = LegalInformationQuestion::where('legal_iq_id', $this->id)->get();
    
            return [
                'id' => $this->id,
                'order_by' => $this->order_by,
                'content_question' => $this->content_question,
                'sub_question' => LegalInformationQuestionResource::collection($sub_question),
                'created_at' => $this->created_at,
                'has_detail' => $this->has_detail,
                'has_yes_no' => $this->has_yes_no,
                'has_date' => $this->has_date
            ];
        }

        return [
            'id' => $this->id,
            'order_by' => $this->order_by,
            'content_question' => $this->content_question,
            'sub_question' => [],
            'created_at' => $this->created_at,
            'has_detail' => $this->has_detail,
            'has_yes_no' => $this->has_yes_no,
            'has_date' => $this->has_date
        ];
    }
}
