<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CtoApplicationLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employeeProfile = [
            'name'=> $this->employeeProfile->personalInformation->name(),
            'profile_url' => $this->profile_url,
            'designation' => [
                'name' => $this->employeeProfile->assignedArea->designation->name,
                'code' => $this->employeeProfile->assignedArea->designation->code,
            ],
            'area' => $this->employeeProfile->assignedArea->findDetails()['details']->name,
        ];

        return [
            'id'                    => $this->id,
            'action'                => $this->action,
            'action_by'             => $employeeProfile,
            'created_at'            => (string) $this->created_at,
            'updated_at'            => (string) $this->updated_at,
        ];
    }
}
