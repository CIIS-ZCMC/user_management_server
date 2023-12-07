<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvtApplicationLog extends Model
{
    use HasFactory;
    protected $table = 'ovt_application_logs';

    public $fillable = [
        'action_by_id',
        'overtime_application_id',
        'action',
        'date',
        'time',

    ];
        public function overtime_application(){
            return $this->belongsTo(OvertimeApplication::class);
        }
        public function employeeProfile() {
            return $this->belongsTo(EmployeeProfile::class, 'action_by_id');
        }
}
