<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class daily_time_record_logs extends Model
{
    use HasFactory;

    protected $fillable = [
        'biometric_id',
        'dtr_id',
        'json_logs',
        'validated'
    ];
}
