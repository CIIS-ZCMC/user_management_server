<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'module_path' => $this->module_path,
            'seen' => $this->seen,
            'date' => $this->created_at,
            'employee_profile_id' => $this->employee_profile_id,
        ];
    }
}
