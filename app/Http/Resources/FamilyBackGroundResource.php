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
        $tin_no = $this->tin_no===null?'NONE':$this->decryptData('tin_no');
        $rdo_no = $this->rdo_no===null?'NONE':$this->decryptData('rdo_no');

        return [
            'id' => $this->id,
            'spouse' => $this->spouse,
            'address' => $this->address,
            'zip_code'=> $this->zip_code,
            'date_of_birth' => $this->date_of_birth,
            'occupation' => $this->occupation,
            'employer' => $this->employer,
            'business_address' => $this->business_address,
            'telephone_no' => $this->telephone_no,
            'tin_no' => $tin_no,
            'rdo_no' => $rdo_no,
            'father_first_name'=> $this->father_first_name,
            'father_middle_name'=> $this->father_middle_name,
            'father_last_name'=> $this->father_last_name,
            'father_ext_name'=> $this->father_ext_name,
            'mother_first_name'=> $this->mother_first_name,
            'mother_middle_name'=> $this->mother_middle_name,
            'mother_last_name'=> $this->mother_last_name,
            'father' => $this->fatherName(),
            'extension' => $this->father_ext_name,
            'mother' => $this->motherName(),
        ];
    }
}
