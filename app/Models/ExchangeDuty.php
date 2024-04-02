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
        'requested_date_to_swap',
        'requested_date_to_duty',
        'requested_employee_id',
        'reliever_employee_id',
        'requested_schedule_id',
        'reliever_schedule_id',
        'approving_officer',
        'reason',
        'status',
    ];

    public $softDelete = true;

    public $timestamps = true;

    public function exchangeDuty()
    {
        return $this->hasOne(ExchangeDuty::class, 'approve_by');
    }

    public function requestedEmployee()
    {
        return $this->belongsTo(EmployeeProfile::class, 'requested_employee_id');
    }

    public function relieverEmployee()
    {
        return $this->belongsTo(EmployeeProfile::class, 'reliever_employee_id');
    }

    public function requestedSchedule()
    {
        return $this->belongsTo(Schedule::class, 'requested_schedule_id');
    }

    public function relieverSchedule()
    {
        return $this->belongsTo(Schedule::class, 'reliever_schedule_id');
    }

    public function approvingOfficer()
    {
        return $this->belongsTo(EmployeeProfile::class, 'approving_officer');
    }

    public function findScheduleDetails($schedule_id)
    {
        $employee_schedule = Schedule::where('id', $schedule_id)->first();

        return [
            'id' => $employee_schedule->id,
            'date' => $employee_schedule->date,
            'time_shift' => $employee_schedule->timeShift->timeShiftDetails(),
        ];
    }
}
