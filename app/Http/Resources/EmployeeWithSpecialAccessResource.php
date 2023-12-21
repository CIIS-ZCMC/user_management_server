<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeWithSpecialAccessResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $assign_area = $this->assignedArea;
        $designation = $assign_area->designation;
        $area = $assign_area->findDetails();
        $position_system_roles = $designation->positionSystemRoles;

        return [
            'id' => $this->id,
            'name' => $this->personalInformation->name(),
            'job_position' => $designation->name,
            'area' => $area['details']->name,
            'system_role' => PositionSystemRoleOnlyResource::collection($position_system_roles),
            'special_access_role' => PositionSystemRoleOnlyResource::collection($this->specialAccessRole),
            'effective_at' => $this->formatEffectiveAt($this->effective_at),
        ];
    }

    private function formatEffectiveAt($effectiveAt)
    {
        $carbonInstance = is_numeric($effectiveAt) ? Carbon::createFromTimestamp($effectiveAt) : Carbon::parse($effectiveAt);

        return $carbonInstance->toDateString();
    }
}
