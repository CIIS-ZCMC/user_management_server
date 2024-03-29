<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvtApplicationEmployee extends Model
{
    use HasFactory;
    protected $table = 'ovt_application_employees';

    public $fillable = [
        'ovt_application_datetime_id',
        'employee_profile_id',
        'remarks',
        'date'

    ];
    public function dates()
    {
        return $this->belongsTo(OvtApplicationDatetime::class);
    }
    public function employeeProfile() {
        return $this->belongsTo(EmployeeProfile::class);
    }


}
