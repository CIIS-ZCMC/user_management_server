<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfficialBusinessLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'official_business'     => $this->officialBusiness ? new OfficialBusinessResource($this->officialBusiness) : null,
            'action_by'             => $this->employee ? new EmployeeProfileResource($this->employee) : null,
            'action'                => $this->action,
            'created_at'            => (string) $this->created_at,
            'updated_at'            => (string) $this->updated_at,
        ];
    }
}
