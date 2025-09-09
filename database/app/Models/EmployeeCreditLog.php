<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeCreditLog extends Model
{
    use HasFactory;
    protected $table = 'employee_credit_logs';
    public $fillable = [
        'employee_profile_id',
        'action',
        'action_by',
    ];
}
