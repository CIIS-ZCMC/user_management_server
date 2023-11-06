<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginTrailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'signin_at' => $this->signin_datetime,
            'ip_address' => $this->ip_address,
            'device' => $this->device,
            'platform'=> $this->platform,
            'browser' => $this->browser
        ];
    }
}
