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
        'reason',
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

    public function filesize()
    {
        if ($this->attachment) {
            // Adjusting the file path to the correct directory
            $filePath = public_path('time_adjustment/' . $this->attachment);
            if (file_exists($filePath)) {
                return $this->formatSizeUnits(filesize($filePath));
            }
        }
        return '0';// B';
    }

    private function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2);// . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2);// . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2);// . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0';// bytes';
        }

        return $bytes;
    }
}
