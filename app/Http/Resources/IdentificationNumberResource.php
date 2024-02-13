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
        $gsis = $this->decryptData("gsis_id_no");
        $pag_ibig = $this->decryptData("pag_ibig_id_no");
        $philhealth = $this->decryptData("philhealth_id_no");
        $sss = $this->decryptData("sss_id_no");
        $prc = $this->decryptData("prc_id_no");
        $tin = $this->decryptData("tin_id_no");
        $rdo = $this->decryptData("rdo_no");
        $bank_no = $this->decryptData("bank_account_no");

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
