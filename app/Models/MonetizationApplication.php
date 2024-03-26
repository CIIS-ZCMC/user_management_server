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
        'recommending_officer',
        'approving_officer'
    ];

    public function logs()
    {
        return $this->hasMany(MoneApplicationLog::class);
    }

    public function owner()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function recommending()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function approving()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }
}
