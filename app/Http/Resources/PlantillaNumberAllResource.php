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
        $salaryGrade = $this->plantilla->designation->salaryGrade;

        $salary = [
            'salary_grade' => $salaryGrade['salary_grade_number'],
            'step' => 1,
            'amount' => $salaryGrade['one'],
            'created_at' => $salaryGrade['created_at'],
            'updated_at' => $salaryGrade['updated_at'],
        ];

        $designationData = $this->plantilla->designation;

        $designation = [
            'id' => $designationData['id'],
            'name' => $designationData['name'],
            'code' => $designationData['code'],
            'created_at' => $salaryGrade['created_at'],
            'updated_at' => $salaryGrade['updated_at'],
        ];

        $plantillaData = $this->plantilla;
        $plantilla = [
            'id' => $plantillaData['id'],
            'slot' => $plantillaData['slot'],
            'total_used_plantilla_no' => $plantillaData['total_user_plantilla_no'],
            'created_at' => $salaryGrade['created_at'],
            'updated_at' => $salaryGrade['updated_at'],
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
            'job_position' => $designationData['name'],
            'salary_grade' => $salaryGrade['salary_grade_number'],
            'is_vacant' => $this->is_vacant,
            'assigned_at' => $this->assigned_at,
            'plantilla' => $plantilla,
            'requirement' => $requirement,
            'designation' => $designation,
            'salary' => $salary,
            'area' => $area,
            'employee' => $this->employee
        ];
    }
}
