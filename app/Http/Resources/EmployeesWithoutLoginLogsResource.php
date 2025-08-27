<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeesWithoutLoginLogsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $name = $this->personalInformation->fullName();
        $assign_area = $this->assignedArea;
        $area_details = $assign_area->findDetails();
        $email = $this->personalInformation->contact->email_address;
        $position = null;

        if($assign_area->plantilla_number_id !== null){
            $designation = $assign_area->plantillaNumber->plantilla->designation;
            $position = $designation->name;
        }else{
            $position = $assign_area->designation->name;
        }

        return [
            "id" => $this->id,
            "name" => $name,
            "email" => $email,
            "position" => $position,
            "area_assigned" => $area_details,
            "login_transactions" => $this->loginTrails
        ];
    }
}
