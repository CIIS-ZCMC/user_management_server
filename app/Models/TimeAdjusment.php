<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class TimeAdjusment extends Model
{
    use HasFactory, SoftDeletes;

      
    protected $table = 'time_adjusments';

    protected $primaryKey = 'id';

    protected $fillable = [
        'employee_profile_id',
        'daily_time_record_id',
        'recommended_by',
        'approve_by',
        'approval_date',
        'first_in',
        'first_out',
        'second_in',
        'second_out',
        'remarks',
        'status'
    ];

    protected $softDelete = true;

    public $timestamps = true;

    public function dailyTimeRecord() {
        return $this->belongsTo(DailyTimeRecords::class, 'daily_time_record_id');
    }

    public function employee() {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id');
    }

    public function recommendedBy() {
        return $this->belongsTo(EmployeeProfile::class, 'recommended_by');
    }

    public function approveBy() {
        return $this->belongsTo(EmployeeProfile::class, 'approve_by');
    }

}
