<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceLogs extends Model
{
    use HasFactory;

    protected $table = "device_logs";
    protected $fillable = [
        'biometric_id',
        'name',
        'dtr_date',
        'date_time',
        'status',
        'is_Shifting',
        'schedule',
        'active'
    ];
}
