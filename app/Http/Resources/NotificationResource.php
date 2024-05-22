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
        $notification = $this->notification;

        return [
            'id' => $this->id,
            'description' => $notification->description,
            'module_path' => $notification->module_path,
            'employee_profile_id' => $this->employee_profile_id,
            'seen' => $this->seen,
            'date' => $this->created_at,
        ];
    }
}
