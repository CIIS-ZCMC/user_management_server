<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $department = $this->department_id === null?"NONE":$this->department->name;

        return [
            'name' => $this->name,
            'code' => $this->code,
            'department' => $department
        ];
    }
}
