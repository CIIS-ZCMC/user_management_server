<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtApplicationLog extends Model
{
    use HasFactory;
    protected $table = 'ot_application_logs';

    public $fillable = [
        'action_by_id',
        'official_time_application_id',
        'action',
        'date',
        'time',
        'fields'

    ];
        public function official_time_application(){
            return $this->belongsTo(OfficialTimeApplication::class);
        }
        public function employeeProfile() {
            return $this->belongsTo(EmployeeProfile::class, 'action_by_id');
        }
}
