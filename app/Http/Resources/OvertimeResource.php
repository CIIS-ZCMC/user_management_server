<?php

namespace App\Http\Resources;

use App\Models\Division;
use App\Models\EmployeeOvertimeCredit;
use App\Http\Resources\OvtApplicationLogResource;
use App\Http\Resources\OvtApplicationActivityResource;
use App\Http\Resources\OvtApplicationDateTimeResource;
use App\Models\OvtApplicationLog;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Resources\Json\JsonResource;

class OvertimeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {

        $employee_profile = $this->employeeProfile;

        $area = $this->employeeProfile->assignedArea->findDetails();
        $otCreditsRecords = EmployeeOvertimeCredit::where('employee_profile_id', $this->employeeProfile->id);
        if( $otCreditsRecords->count() >=1){
            $overtime_credits = $otCreditsRecords->first();
        }

        $oic = null;

        $omccRec = Division::where('code', 'OMCC')->where('chief_employee_profile_id', $this->employeeProfile->id);
        if($omccRec->count()>=1){
            $isMCC =  $omccRec ->first();

        }




        $hrmoRec = Section::where('code', 'HRMO');
        if($hrmoRec->count()>=1){
            $hrmo = $hrmoRec->first();
        }



        if ($this->employee_oic_id  !== null) {
            switch ($area['sector']) {
                case "Division":
                    $area_details = $employee_profile->assignedArea->division;
                    break;
                case "Department":
                    $area_details = $employee_profile->assignedArea->department;
                    break;
                case "Section":
                    $area_details = $employee_profile->assignedArea->section;
                    break;
                case "Unit":
                    $area_details = $employee_profile->assignedArea->unit;
                    break;
            }

            $oic = [
                'id' => $area_details->id,
                'name' => $area_details->name,
                'code' => $area_details->code,
                'oic' => $this->oic->personalInformation->name(),
                'position' => $this->oic->assignedArea->designation->name
            ];
        }

        return [

            "id" => $this->id,
            "employee_profile" => [
                'employee_id' => $this->employeeProfile->id,
                'name' => $this->employeeProfile->personalInformation->name(),
                'designation_name' => $this->employeeProfile->assignedArea->designation->name,
                'designation_code' => $this->employeeProfile->assignedArea->designation->code,
                'area' => $area['details']->name,
                'area_code' => $area['details']->code,
                'area_sector' => $area['sector'],
                'profile_url' => Cache::get("server_domain") . "/photo/profiles/" . $this->employeeProfile->profile_url,
            ],

            "date" => $this->date_from,
            "reference_number" => $this->date_to,
            "status" => $this->status,
            "remarks" => $this->remarks,
            "purpose" => $this->purpose,
            "overtime_letter_of_request" => $this->is_masters,
            "overtime_letter_of_request_path" => $this->is_board,
            "overtime_letter_of_request_size" => $this->is_commutation,
            "decline_reason" => $this->without_pay,
            'reason' => $this->reason,
            'credit_balance' => $overtime_credits->earned_credit_by_hour ?? 0,
            "recommending_officer" => [
                "employee_id" => $this->recommendingOfficer->id,
                "name" => $this->recommendingOfficer->personalInformation->name(),
                "designation" => $this->recommendingOfficer->assignedArea->designation->name,
                "designation_code" => $this->recommendingOfficer->assignedArea->designation->code,
                "profile_url" => Cache::get("server_domain") . "/photo/profiles/" . $this->recommendingOfficer->profile_url,
            ],
            "approving_officer" => [
                "employee_id" => $this->approvingOfficer->id,
                "name" => $this->approvingOfficer->personalInformation->name(),
                "designation" => $this->approvingOfficer->assignedArea->designation->name,
                "designation_code" => $this->approvingOfficer->assignedArea->designation->code,
                "profile_url" => Cache::get("server_domain") . "/photo/profiles/" . $this->approvingOfficer->profile_url,
            ],
            "oic" => $oic,
            'logs' => $this->logs ? OvtApplicationLogResource::collection($this->logs) : [],
            'activities' => $this->activities ? OvtApplicationActivityResource::collection($this->activities) : null,
            'dates' => !$this->activities ? OvtApplicationDateTimeResource::collection($this->dates) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
