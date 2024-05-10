<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class TimeAdjustment extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'time_adjustments';

    protected $primaryKey = 'id';

    protected $fillable = [
        'date',
        'first_in',
        'first_out',
        'second_in',
        'second_out',
        'remarks',
        'attachment',
        'status',
        'approval_date',
        'daily_time_record_id',
        'employee_profile_id',
        'recommending_officer',
        'approving_officer',
    ];

    protected $softDelete = true;

    public $timestamps = true;

    public function dailyTimeRecord()
    {
        return $this->belongsTo(DailyTimeRecords::class, 'daily_time_record_id');
    }

    public function employee()
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }

    public function recommendingOfficer()
    {
        return $this->belongsTo(EmployeeProfile::class, 'recommending_officer');
    }

    public function approvingOfficer()
    {
        return $this->belongsTo(EmployeeProfile::class, 'approving_officer');
    }
}
