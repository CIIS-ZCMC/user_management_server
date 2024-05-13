<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CivilServiceEligibilityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $license_number = $this->license_number;
        $license_release_at = $this->license_release_at;

        return [
            'id'=> $this->id,
            'career_service' => $this->career_service,
            'rating' => $this->rating,
            'date_of_examination' => $this->date_of_examination,
            'place_of_examination' => $this->place_of_examination,
            'license_number' => $license_number,
            'license_release_at' => $license_release_at,
            'attachment' =>   config('app.server_domain')."/eligibilities/".$this->attachment

        ];

}
}
