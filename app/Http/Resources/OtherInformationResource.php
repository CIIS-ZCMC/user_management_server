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
        $recognition = $this->recognition === null? 'NONE': $this->recognition;
        $organization = $this->organization === null? 'NONE': $this->organization;

        return [
            'hobbies' => $this->hobbies,
            'recognition' => $recognition,
            'organization' => $organization
        ];
    }
}
