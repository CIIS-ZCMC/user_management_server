<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfficialBusinessResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                                => $this->id,
            'total_days'                        => $this->totalDays(),
            'date_from'                         => $this->date_from,
            'date_to'                           => $this->date_to,
            'purpose'                           => $this->purpose,
            'status'                            => $this->status,
            'personal_order_file'               => $this->personal_order_file,
            'personal_order_path'               => config('app.server_domain')."/official_business/".$this->personal_order_path,
            'personal_order_size'               => $this->personal_order_size,
            'certificate_of_appearance'         => $this->certificate_of_appearance,
            'certificate_of_appearance_path'    => config('app.server_domain')."/official_business/".$this->certificate_of_appearance_path,
            'certificate_of_appearance_size'    => $this->certificate_of_appearance_size,
            'remarks'                           => $this->remarks,
            'employee'                          => $this->employee ? new EmployeeProfileResource($this->employee) : null,
            'recommending_officer'              => $this->recommendingOfficer ? new EmployeeProfileResource($this->recommendingOfficer) : null,
            'approving_officer'                 => $this->approvingOfficer ? new EmployeeProfileResource($this->approvingOfficer) : null,
            'created_at'                        => (string) $this->created_at,
            'updated_at'                        => (string) $this->updated_at,
            'logs'                              => $this->officialBusinessLogs ? OfficialBusinessLogResource::collection($this->officialBusinessLogs) : [],
        ];
    }
}
