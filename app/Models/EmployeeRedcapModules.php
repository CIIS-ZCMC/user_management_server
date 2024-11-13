<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class EmployeeRedcapModules extends Model
{
    use HasFactory;

    protected $table = 'employee_redcap_modules';

    public $fillable = [
        'redcap_module_id',
        'employee_profile_id',
        'employee_auth_id',
        'deactivated_at'
    ];

    public $timestamp = true;
    
    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function redcapModule()
    {
        return $this->belongsTo(RedcapModules::class);
    }

    public function myAuthID()
    {
        $redCapModule = $this->redCapModule;

        if (!$redCapModule || empty($redCapModule->origin) || empty($redCapModule->path)) {
            return null;
        }

        try {
            $origin = Crypt::decryptString($redCapModule->origin);
        } catch (DecryptException $e) {
            return null; // Handle decryption error and return null or an error message
        }

        $path = $redCapModule->path;


        return $origin.$path.$this->employee_auth_id;
    }
}
