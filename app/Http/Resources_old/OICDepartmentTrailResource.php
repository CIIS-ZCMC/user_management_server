<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Department;

class OICDepartmentTrailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $oic_employee = $this->head;
        $officer_in_charge = $oic_employee->name;
        $department = Department::where('code', $this->sector_code)->first();
        $department_name = $department['name'];
        $department_code = $department['code'];
        $attachment = $this->attachment_url;
        $position_title = $this->position_title;

        return [
            'officer_in_charge' => $officer_in_charge,
            'position_title' => $position_title,
            'department_name' => $department_name,
            'department_code' => $department_code,
            'attachment' => $attachment,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at 
        ];
    }
}
