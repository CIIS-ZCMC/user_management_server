<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InActiveEmployee extends Model
{
    use HasFactory;
    
    protected $table = 'in_active_employees';

    public $fillable = [
        'employee_id',
        'date_hired',
        'date_resigned',
        'employee_profile_id',
        'status',
        'remarks'
    ];

    public $timestamps = TRUE;

    public function employee()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }
}
