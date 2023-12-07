<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficialTimeApplication extends Model
{
    use HasFactory;
    protected $table = 'official_time_applications';

    public $fillable = [
        'employee_profile_id',
        'date_from',
        'date_to',
        'time_from',
        'time_to',
        'reason',
        'status',
    ];

        public function employeeProfile() {
            return $this->belongsTo(EmployeeProfile::class);
        }
        public function logs()
        {
            return $this->hasMany(OtApplicationLog::class);
        }

    }
