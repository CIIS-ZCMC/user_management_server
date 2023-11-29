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
        $key_status = $this->api_key === null? 'NO KEY': $this->key_deactivated_at;

        $status_name = '';

        switch($this->status)
        {
            case 1:
                $status_name = 'Active';
                break;
            case 2:
                $status_name = 'Under Maintainance';
                break;
            default:
                $status_name = 'Server Down';
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'domain' => Crypt::decrypt($this->domain),
            'api_key' => $api_key,
            'key_status' => $key_status,
            'status' => $this->status,
            'status_name' => $status_name
        ];
    }
}
