<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeaveApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "employee_profile" => [
                "employee_account" => $this->employeeProfile,
                "personal_information" => $this->employeeProfile->personalInformation
            ],
            "leave_type" => $this->leaveType,
            "date_from" => $this->date_from,
            "date_to" => $this->date_to,
            "country" => $this->country,
            "city" => $this->city,
            "patient_type" => $this->patient_type,
            "illness" => $this->illness,
            "applied_credits" => $this->applied_credits, // amount of credits to be use only for non special leave.
            "status" => $this->status, //Applied->For recommending officer approval->For approving officer approval->Approved || Declined.
            "remarks" => $this->remarks, //Reason of leave application.
            "without_pay" => $this->without_pay,
            'reason' => $this->reason,
            "hrmo_officer" => [
                "employee_id" => $this->hrmoOfficer->employee_id,
                "hrmo_full_name" => $this->hrmoOfficer->personalInformation->fullName(),
                "designation" => $this->hrmoOfficer->designation->name,
                "designation_code" => $this->hrmoOfficer->designation->code
            ],
            "recommending_officer" => [
                "employee_id" => $this->recommendingOfficer->employee_id,
                "hrmo_full_name" => $this->recommendingOfficer->personalInformation->fullName(),
                "designation" => $this->recommendingOfficer->designation->name,
                "designation_code" => $this->recommendingOfficer->designation->code
            ],
            "approving_officer" => [
                "employee_id" => $this->approvingOfficer->employee_id,
                "hrmo_full_name" => $this->approvingOfficer->personalInformation->fullName(),
                "designation" => $this->approvingOfficer->designation->name,
                "designation_code" => $this->approvingOfficer->designation->code
            ],
            'attachments' => LeaveApplicationAttachmentResource::collection($this->leaveApplicationRequirements),
            "logs" => $this->logs
        ];
    }
}