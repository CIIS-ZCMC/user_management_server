<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RedcapEmployeeListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee = $this->employeeProfile;

        return [
            'id' => $this->id,
            'name' => $employee->name(),
            'link' => $this->myAuthID(),
            'date' => $this->created_at
        ];
    }
}
