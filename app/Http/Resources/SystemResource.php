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
        $api_key = $this->api_key === null? 'NONE': Crypt::decrypt($this->api_key);
        $key_status = $this->key_deactivated_at;
        $status = ($this->status === 1? 'Server is Active':$this->status === 2)? "Server Maintainance": "Server is not Running";

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'domain' => $this->domain,
            'api_key' => $api_key,
            'key_status' => $key_status,
            'status' => $status
        ];
    }
}
