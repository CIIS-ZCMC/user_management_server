<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IdentificationNumberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $gsis = $this->gsis_id_no !== null? $this->decryptData("gsis_id_no"):null;
        $pag_ibig = $this->pag_ibig_id_no !== null? $this->decryptData("pag_ibig_id_no"):null;
        $philhealth = $this->philhealth_id_no !== null? $this->decryptData("philhealth_id_no"):null;
        $sss = $this->sss_id_no !== null? $this->decryptData("sss_id_no"):null;
        $prc = $this->prc_id_no !== null? $this->decryptData("prc_id_no"):null;
        $tin = $this->tin_id_no !== null? $this->decryptData("tin_id_no"):null;
        $rdo = $this->rdo_no !== null? $this->decryptData("rdo_no"):null;
        $bank_no = $this->bank_account_no !== null? $this->decryptData("bank_account_no"):null;

        return [
            'id' => $this->id,
            'gsis_id_no' => $gsis,
            'pag_ibig_id_no' => $pag_ibig,
            'philhealth_id_no' => $philhealth,
            'sss_id_no' => $sss,
            'prc_id_no' => $prc,
            'tin_id_no' => $tin,
            'rdo_no' => $rdo,
            'bank_account_no' => $bank_no
        ];
    }
}
