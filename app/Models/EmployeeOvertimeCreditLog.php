<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeOvertimeCreditLog extends Model
{
    use HasFactory;

    protected $table = "employee_overtime_credit_logs";

    public $fillable = [
        'employee_ot_credit_id',
        'cto_application_id',
        'overtime_application_id',
        'action',
        'previous_overtime_hours',
        'hours',
    ];

    public $timestamps = true;

    public function OvertimeCredit()
    {
        return $this->belongsTo(EmployeeOvertimeCredit::class);
    }
}
