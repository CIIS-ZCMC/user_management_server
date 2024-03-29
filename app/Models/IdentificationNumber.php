<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class IdentificationNumber extends Model
{
    use HasFactory;

    protected $table = 'identification_numbers';

    public $fillable = [
        'gsis_id_no',
        'pag_ibig_id_no',
        'philhealth_id_no',
        'sss_id_no',
        'prc_id_no',
        'tin_id_no',
        'rdo_no',
        'bank_account_no',
        'personal_information_id'
    ];

    public $timestamps = TRUE;

    public function personalInformation()
    {
        return $this->belongsTo(PersonalInformation::class);
    }

    public function decryptData($toEncrypt)
    {
        $encryptedData = null;

        switch ($toEncrypt) {
            case 'gsis_id_no':
                $encryptedData = $this->gsis_id_no === null ? $this->gsis_id_no : Crypt::decrypt($this->gsis_id_no);
                break;
            case 'pag_ibig_id_no':
                $encryptedData = $this->pag_ibig_id_no === null ? $this->pag_ibig_id_no : Crypt::decrypt($this->pag_ibig_id_no);
                break;
            case 'philhealth_id_no':
                $encryptedData = $this->philhealth_id_no === null ? $this->philhealth_id_no : Crypt::decrypt($this->philhealth_id_no);
                break;
            case 'sss_id_no':
                $encryptedData = $this->sss_id_no === null ? $this->sss_id_no : Crypt::decrypt($this->sss_id_no);
                break;
            case 'prc_id_no':
                $encryptedData = $this->prc_id_no === null ? $this->prc_id_no : Crypt::decrypt($this->prc_id_no);
                break;
            case 'tin_id_no':
                $encryptedData = $this->tin_id_no === null ? $this->tin_id_no :  Crypt::decrypt($this->tin_id_no);
                break;
            case 'rdo_no':
                $encryptedData = $this->rdo_no === null ? $this->rdo_no : Crypt::decrypt($this->rdo_no);
                break;
            case 'bank_account_no':
                $encryptedData = $this->bank_account_no === null ? $this->bank_account_no : Crypt::decrypt($this->bank_account_no);
                break;
        }

        return $encryptedData;
    }
}
