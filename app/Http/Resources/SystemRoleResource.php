<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemRoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $system_name = $this->system_id === null? 'NONE':$this->system->name;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'system_name' => $system_name,
            'effective_at' => $this->effective_at
        ];
    }
}
