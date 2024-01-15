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
            'spouse' => $this->spouse??'NONE',
            'address' => $this->address??'NONE',
            'zip_code'=> $this->zip_code??'NONE',
            'date_of_birth' => $this->date_of_birth??'NONE',
            'occupation' => $this->occupation??'NONE',
            'employer' => $this->employer??'NONE',
            'business_address' => $this->business_address??'NONE',
            'telephone_no' => $this->telephone_no??'NONE',
            'tin_no' => $tin_no,
            'rdo_no' => $rdo_no,
            'father' => $this->fatherName(),
            'extension' => $this->father_ext_name??'NONE',
            'mother' => $this->motherName(),
            'maiden' => $this->mother_maiden_name??'NONE'
        ];
    }
}
