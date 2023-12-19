<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CtoApplication extends Model
{
    use HasFactory;
    protected $table = 'cto_applications';
    public $fillable = [
        'employee_profile_id',
        'reference_number',
        'status',
        'remarks',
        'purpose',

    ];

    public function dates()
    {
        return $this->hasMany(CtoApplicationDate::class);
    }
    public function logs()
    {
        return $this->hasMany(CtoApplicationLog::class);
    }
    public function employeeProfile() {
        return $this->belongsTo(EmployeeProfile::class);
    }
}
