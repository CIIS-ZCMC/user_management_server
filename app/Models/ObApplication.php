<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObApplication extends Model
{
    use HasFactory;
    protected $table = 'ob_applications';

    public $fillable = [
        'user_id',
        'date_from',
        'date_to',
        'time_from',
        'time_to',
        'reason',
        'status',
    ];      
        public function logs()
        {
            return $this->hasMany(ObApplicationLog::class);
        }

        public function employeeProfile() {
            return $this->belongsTo(EmployeeProfile::class);
        }
}
