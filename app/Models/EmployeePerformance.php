<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePerformance extends Model
{
    use HasFactory;

    protected $table = 'employee_performance';

    public $fillable = [
        'employee_profile_id',
        'total_absences',
        'total_undertime',
        'total_working_hours',
        'date_from',
        'date_to'
    ];

    public $timestamps = true;
}
