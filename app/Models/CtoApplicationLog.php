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
        'cto_application_id',
        'action',
        'date',
        'time',


    ];
    public function CtoApplication() {
        return $this->belongsTo(CtoApplication::class);
    }
    public function employeeProfile() {
        return $this->belongsTo(EmployeeProfile::class, 'action_by_id');
    }
}
