<?php

namespace App\Http\Resources;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $domain = Crypt::decrypt($this->domain);
        $under_maintainance = $this->server_maintainance;

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,
            'domain' => $domain,
            'under_maintainance' => $under_maintainance,
            'server_down' => $this->server_down,
            'server_active' => $this->server_active
        ];
    }
}
