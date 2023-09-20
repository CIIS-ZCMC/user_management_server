<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $division = $this->division_id===null?'NONE':$this->division->name;

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,
            'division' => $division
        ];
    }
}
