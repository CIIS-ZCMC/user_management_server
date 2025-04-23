<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Biometrics extends Model
{
    use HasFactory;

    protected $table = "biometrics";
    protected $fillable = [
        'biometric_id',
        'name',
        'privilege',
        'biometric',
        'name_with_biometric'
    ];
    
    public function employeeProfile()
    {
        return $this->hasOne(EmployeeProfile::class, 'biometric_id', 'biometric_id');
    }
}
