<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CTOCreditEarnLog extends Model
{
    use HasFactory;

    protected $table = 'cto_credit_earn_logs';

    public $fillable = [
        'credit',
        'employee_leave_credit_id',
        'employee_profile_id',
        'expiration'
    ];

    public $timestamps = true;

    public function employee()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function credit()
    {
        return $this->belongsTo(EmployeeLeaveCredit::class);
    }
}
