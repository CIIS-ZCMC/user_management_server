<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnCallResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee = [
            'name'=> $this->employee->name(),
            'profile_url' => $this->profile_url,
            'contact' => $this->employee->personalInformation->contact->phone_number,
            'designation' => [
                'name' => $this->employee->assignedArea->designation->name,
                'code' => $this->employee->assignedArea->designation->code,
            ],
            'area' => $this->employee->assignedArea->findDetails()['details']->name,
        ];


        return [
            'id'                    => $this->id,
            'employee'              => $employee,
            'date'                  => $this->date,
            'remarks'               => $this->remarks,
            'deleted_at'            => (string) $this->deleted_at,
            'created_at'            => (string) $this->created_at,
            'updated_at'            => (string) $this->updated_at,
        ];
    }
}
