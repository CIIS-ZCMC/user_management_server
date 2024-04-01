<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonetizationApplication extends Model
{
    use HasFactory;
    
    protected $table = 'monetization_applications';

    public $fillable = [
        'employee_profile_id',
        'leave_type_id',
        'reason',
        'attachment',
        'credit_value',
        'date',
        'hrmo_officer',
        'recommending_officer',
        'approving_officer'
    ];

    public function logs()
    {
        return $this->hasMany(MoneApplicationLog::class);
    }

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }
    public function hrmoOfficer()
    {
        return $this->belongsTo(EmployeeProfile::class, 'hrmo_officer');
    }
    public function recommending()
    {
        return $this->belongsTo(EmployeeProfile::class, 'recommending_officer');
    }

    public function approving()
    {
        return $this->belongsTo(EmployeeProfile::class, 'approving_officer');
    }
}
