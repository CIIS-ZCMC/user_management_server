<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CtoApplicationLog extends Model
{
    use HasFactory;
    protected $table = 'cto_application_logs';
    public $fillable = [
        'action_by_id',
        'overtime_application_id',
        'action',
        'date',
        'time',
        'fields'

    ];
    public function logs()
    {
        return $this->hasMany(CtoApplicationLog::class);
    }
    public function employeeProfile() {
        return $this->belongsTo(EmployeeProfile::class);
    }
}
