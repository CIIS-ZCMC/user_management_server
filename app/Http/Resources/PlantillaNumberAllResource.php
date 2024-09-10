<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlantillaNumberAllResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $plantilla_data = $this->plantilla;
        $designation_data = $plantilla_data->designation_id === null? null: $plantilla_data->designation;
        $salaryGrade = $designation_data === null? null: $designation_data->salaryGrade;

        $salary = $salaryGrade === null? null: [
            'salary_grade' => $salaryGrade['salary_grade_number'],
            'step' => 1,
            'amount' => $salaryGrade['one'],
            'created_at' => $salaryGrade['created_at'],
            'updated_at' => $salaryGrade['updated_at'],
        ];

        $designation = $designation_data === null? null: [
            'id' => $designation_data['id'],
            'name' => $designation_data['name'],
            'code' => $designation_data['code'],
            'created_at' => $salaryGrade['created_at'],
            'updated_at' => $salaryGrade['updated_at'],
        ];

        $plantilla = $plantilla_data === null?null: [
            'id' => $plantilla_data['id'],
            'slot' => $plantilla_data['slot'],
            'total_used_plantilla_no' => $plantilla_data['total_user_plantilla_no'],
            'created_at' => $salaryGrade === null? null: $salaryGrade['created_at'],
            'updated_at' => $salaryGrade === null? null: $salaryGrade['updated_at'],
        ];


        $requirement = [];

        if($this->plantilla->requirement !== null){
            $requirementsData = $this->plantilla->requirement;
            $requirement['education'] = $requirementsData['education'];
            $requirement['training'] = $requirementsData['training'];
            $requirement['experience'] = $requirementsData['experience'];
            $requirement['eligibility'] = $requirementsData['eligibility'];
            $requirement['competency'] = $requirementsData['competency'];
        }

        $area_data = $this->assigned_at === null? null: $this->assignedArea->area();

        $area = $area_data===null? []: [
            'id' => $area_data['details']->id,
            'name' => $area_data['details']->name,
            'code' => $area_data['details']->code
        ];

        return [
            'id' => $this->id,
            'number' => $this->number,
            'job_position' => $designation !==null? $designation['name']:null,
            'salary_grade' => $salaryGrade !== null? $salaryGrade['salary_grade_number']:null,
            'is_vacant' =>$this->employee_profile_id !==null? 0: $this->is_vacant,
            'assigned_at' => $this->assigned_at,
            'plantilla' => $plantilla,
            'requirement' => $requirement,
            'designation' => $designation,
            'salary' => $salary,
            'area' => $area,
            'employee' => $this->employee_profile_id !==null? $this->employeeProfile->personalInformation->name():null
        ];
    }
}
