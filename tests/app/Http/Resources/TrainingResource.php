<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingResource extends JsonResource
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
            'title' => $this->title,
            'inclusive_from' => $this->inclusive_from,
            'inclusive_to' => $this->inclusive_to,
            'hours' => $this->hours,
            'type_of_ld' => $this->type_of_ld,
            'conducted_by' =>  $this->conducted_by,
            'attachment' =>   config('app.server_domain')."/training/".$this->attachment
        ];
    }
}
