<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DefaultPasswordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {   
        $status = $this->status?'Active':'Deactived';
        $end_at = $this->end_at === null?'NONE':$this->end_at;

        return [
            'password' => $this->password,
            'status' => $status,
            'effective_at' => $this->effective_at,
            'end_at' => $end_at
        ];
    }
}
