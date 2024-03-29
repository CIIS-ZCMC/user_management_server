<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObApplicationLog extends Model
{
    use HasFactory;
    protected $table = 'ob_application_logs';

    public $fillable = [
        'action_by_id',
        'ob_application_id',
        'action',
        'date',
        'time',

    ];
        public function ob_application(){
            return $this->belongsTo(ObApplication::class);
        }
        public function employeeProfile() {
            return $this->belongsTo(EmployeeProfile::class, 'action_by_id');
        }
    }
