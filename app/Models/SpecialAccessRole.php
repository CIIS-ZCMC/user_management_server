<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialAccessRole extends Model
{
    use HasFactory;

    protected $table = 'special_access_roles';

    public $fillable = [
        'employee_profile_id',
        'system_role_id',
        'effective_at'
    ];

    public $timestamps = TRUE;

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function systemRole()
    {
        return $this->belongsTo(SystemRole::class, 'system_role_id');
    }
}
