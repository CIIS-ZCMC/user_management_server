<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonetizationApplication extends Model
{
    use HasFactory;
    
    protected $table = 'monetization_applications';
    protected $casts = [
        'is_qualified' => 'boolean',
        // 'is_commutation' => 'boolean',
    ];
    public $fillable = [
        'employee_profile_id',
        'leave_type_id',
        'reason',
        'credit_value',
        'is_qualified',
        'status',
        'remarks',
        'attachment',
        'attachment_size',
        'attachment_path',
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

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
    
    public function hrmoOfficer()
    {
        return $this->belongsTo(EmployeeProfile::class, 'hrmo_officer');
    }
    public function recommendingOfficer()
    {
        return $this->belongsTo(EmployeeProfile::class, 'recommending_officer');
    }

    public function approvingOfficer()
    {
        return $this->belongsTo(EmployeeProfile::class, 'approving_officer');
    }
}
