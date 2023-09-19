<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $phone_number = $this->phone_number === null? 'NONE':$this->phone_number;
        $email = $this->email === null?'NONE':$this->email;

        return [
            'phone_number' => $phone_number,
            'email' => $email
        ];
    }
}
