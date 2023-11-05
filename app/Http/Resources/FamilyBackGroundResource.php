<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyBackGroundResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $spouse = $this->spouse===null?'NONE':$this->spouse;
        $address = $this->address===null?'NONE':$this->address;
        $zip_code = $this->zip_code===null?'NONE':$this->zip_code;
        $date_of_birth = $this->date_of_birth===null?'NONE':$this->date_of_birth;
        $occupation = $this->occupation===null?'NONE':$this->occupation;
        $employer = $this->employer===null?'NONE':$this->employer;
        $business_address = $this->business_address===null?'NONE':$this->business_address;
        $telephone_no = $this->telephone_no===null?'NONE':$this->telephone_no;
        $tin_no = $this->tin_no===null?'NONE':$this->decryptData('tin_no');
        $rdo_no = $this->rdo_no===null?'NONE':$this->decryptData('rdo_no');
        $personal_information = $this->personalInformation;
        $employee = $personal_information->employeeProfile;
        $employee_id = $employee['employee_id'];

        return [
            'spouse' => $spouse,
            'address' => $address,
            'zip_code'=> $zip_code,
            'date_of_birth' => $date_of_birth,
            'occupation' => $occupation,
            'employer' => $employer,
            'business_address' => $business_address,
            'telephone_no' => $telephone_no,
            'tin_no' => $tin_no,
            'rdo_no' => $rdo_no,
            'father_name' => $this->fatherName(),
            'mother_name' => $this->motherName(),
            'employee_id'=> $employee_id
        ];
    }
}
