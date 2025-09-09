<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeDutyLog extends Model
{
    use HasFactory;

    protected $table = 'exchange_duty_logs';

    protected $primaryKey = 'id';

    public $fillable = [
        'exchange_duty_id',
        'action_by',
        'action',
    ];

    public $timestamps = true;

    public function exchangeDuty()
    {
        return $this->belongsTo(ExchangeDuty::class, 'exchange_duty_id');
    }

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class, 'action_by');
    }

}
