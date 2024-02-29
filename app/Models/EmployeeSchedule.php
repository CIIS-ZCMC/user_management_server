<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class EmployeeSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_profile_schedule';

    protected $primaryKey = 'id';


    protected $fillable = [
        'employee_profile_id',
        'schedule_id',
        'is_on_call',
    ];

    public $timestamps = false;

    public function employee()
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }
}
