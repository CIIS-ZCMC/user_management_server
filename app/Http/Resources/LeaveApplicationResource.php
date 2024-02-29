<?php

namespace App\Http\Resources;

use App\Models\EmployeeLeaveCredit;
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
        $area = $this->employeeProfile->assignedArea->findDetails();
        // $leave_credits = EmployeeLeaveCredit::where('employee_profile_id', $this->employeeProfile->id)->where('leave_type_id', $this->leave_type_id)->first();

        return [
            "id" => $this->id,
            "employee_profile" => [
                'employee_id' => $this->employeeProfile->id,
                'name' => $this->employeeProfile->personalInformation->name(),
                'designation_name' => $this->employeeProfile->assignedArea->designation->name,
                'designation_code' => $this->employeeProfile->assignedArea->designation->code,
                'area' => $area['details']->name,
                'area_sector' => $area['sector'],
                'profile_url'=>env('SERVER_DOMAIN') . "/photo/profiles/" . $this->employeeProfile->profile_url,
            ],
            "leave_type" => $this->leaveType,
            "date_from" => $this->date_from,
            "date_to" => $this->date_to,
            "country" => $this->country,
            "city" => $this->city,
            "is_outpatient" => $this->is_outpatient,
            "illness" => $this->illness,
            "is_masters" => $this->is_masters,
            "is_board" => $this->is_board,
            "is_commutation" => $this->is_commutation,
            "applied_credits" => (int)$this->applied_credits, // amount of credits to be use only for non special leave.
            "status" => $this->status, //Applied->For recommending officer approval->For approving officer approval->Approved || Declined.
            "remarks" => $this->remarks, //Reason of leave application.
            "without_pay" => $this->without_pay,
            'reason' => $this->reason,
            // 'credit_balance' => $leave_credits->total_leave_credits ? $leave_credits->total_leave_credits : null,
            "hrmo_officer" => [
                "employee_id" => $this->hrmoOfficer->employee_id,
                "name" => $this->hrmoOfficer->personalInformation->name(),
                "designation" => $this->hrmoOfficer->assignedArea->designation->name,
                "designation_code" => $this->hrmoOfficer->assignedArea->designation->code,
                "profile_url" => env('SERVER_DOMAIN') . "/photo/profiles/" . $this->hrmoOfficer->profile_url,
            ],
            "recommending_officer" => [
                "employee_id" => $this->recommendingOfficer->employee_id,
                "name" => $this->recommendingOfficer->personalInformation->name(),
                "designation" => $this->recommendingOfficer->assignedArea->designation->name,
                "designation_code" => $this->recommendingOfficer->assignedArea->designation->code,
                "profile_url" => env('SERVER_DOMAIN') . "/photo/profiles/" . $this->recommendingOfficer->profile_url,
            ],
            "approving_officer" => [
                "employee_id" => $this->approvingOfficer->employee_id,
                "name" => $this->approvingOfficer->personalInformation->name(),
                "designation" => $this->approvingOfficer->assignedArea->designation->name,
                "designation_code" => $this->approvingOfficer->assignedArea->designation->code,
                "profile_url" => env('SERVER_DOMAIN') . "/photo/profiles/" . $this->approvingOfficer->profile_url,
            ],
            'attachments' =>$this->leaveApplicationRequirements === null? []: LeaveApplicationAttachmentResource::collection($this->leaveApplicationRequirements),
            'logs' => $this->logs ? LeaveApplicationLog::collection($this->logs):[],
            'created_at'=>$this->created_at,
            'updated_at'=>$this->updated_at,
            
        ];
    }
}
