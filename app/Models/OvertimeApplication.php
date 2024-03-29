<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeApplication extends Model
{

    use HasFactory;
    protected $table = 'overtime_applications';

    public $fillable = [
        'employee_profile_id',
        'reference_number',
        'status',
        'purpose',
        'overtime_letter_of_request',
        'path',
        'date',
        'time'

    ];
    public function activities()
    {
        return $this->hasMany(OvtApplicationActivity::class);
    }
    public function logs()
    {
            return $this->hasMany(OvtApplicationLog::class);
    }
    public function employeeProfile() {
        return $this->belongsTo(EmployeeProfile::class);
    }
    
    public function directDates() {
        return $this->hasMany(OvtApplicationDatetime::class);
    }




}
