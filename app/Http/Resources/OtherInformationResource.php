<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OtherInformationResource extends JsonResource
{   
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        if($this->skills_hobbies)
        {
            return [
                'id' => $this->id,
                'title' => $this->title,
                'description' => 'Skill/Hobbies'
            ];
        }

        if($this->recognition)
        {
            return [
                'id' => $this->id,
                'title' => $this->title,
                'description' => 'Recognation'
            ];
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => 'Organization'
        ];
    }
}
