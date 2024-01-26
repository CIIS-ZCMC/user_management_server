<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

use App\Models\Schedule;

class ExchangeDuty extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'exchange_duties';

    protected $primaryKey = 'id';

    protected $fillable = [
        'reason',
        'status',
        'schedule_id',
        'requested_employee_id',
        'reliever_employee_id'
    ];
    
    protected $softDelete = true;

    public $timestamps = true;

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function requestedEmployee()
    {
        return $this->belongsTo(EmployeeProfile::class, 'requested_employee_id');
    }

    public function relieverEmployee()
    {
        return $this->belongsTo(EmployeeProfile::class, 'reliever_employee_id');
    }
    
    public function approveBy()
    {
        return $this->belongsTo(EmployeeProfile::class, 'approve_by');
    }
}
