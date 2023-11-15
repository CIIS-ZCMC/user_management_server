<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyTimeRecordLogs extends Model
{
    use HasFactory;

    protected $fillable = [
        'biometric_id',
        'dtr_id',
        'json_logs',
        'validated'
    ];
}
