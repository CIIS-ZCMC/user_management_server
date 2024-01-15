<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeOvertimeCredit extends Model
{
    use HasFactory;
    protected $table = 'employee_overtime_credits';

    public $fillable = [
        'employee_profile_id',
        'overtime_application_id',
        'operation',
        'overtime_hours',
        'credit_value',
        'date',
    ];
    public function employeeProfile()
{
    return $this->belongsTo(EmployeeProfile::class);
}
}
