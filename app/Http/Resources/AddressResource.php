<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $is_residential = $this->is_residential? "Residential": "Permanent";
        $telephone_no = $this->telephone_no===null?'NONE': $this->telephone_no;
        
        return [
            'id' => $this->id,
            'street' => $this->street,
            'barangay' => $this->barangay,
            'city' => $this->city,
            'province' => $this->province,
            'zip_code' => $this->zip_code,
            'country' => $this->country,
            'is_residential' => $is_residential,
            'telephone_no' => $telephone_no
        ];
    }
}
