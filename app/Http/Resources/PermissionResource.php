<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->active?"ACTIVE":"DEACTIVED";

        return [
            'id' => $this->id,
            'name' => $this->name,
            'action' => $this->action,
            'status' => $status
        ];
    }
}
