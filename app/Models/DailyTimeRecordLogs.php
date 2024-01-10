<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyTimeRecordLogs extends Model
{
    use HasFactory;

    protected $table = "daily_time_record_logs";
    protected $fillable = [
        'biometric_id',
        'dtr_id',
        'json_logs',
        'validated',
        'dtr_date'
    ];
}
