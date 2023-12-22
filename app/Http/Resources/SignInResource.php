<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SignInResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee_profile = $this->employeeProfile;

        $name = $employee_profile->first_name.' '.$employee_profile->last_name;
        $position = $employee_profile->employmentPosition->name;
        $department = $employee_profile->department->name;

        return [
            'id' => $this->id,
            'name' => $name,
            'department' => $department,
            'position' => $position
        ];
    }
    
    public static function collection($resource)
    {
        return parent::collection($resource)->withoutWrapping();
    }
}
