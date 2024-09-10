<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeAdjustmentLog extends Model
{
    use HasFactory;
    protected $table = 'time_adjustment_logs';

    protected $primaryKey = 'id';

    public $fillable = [
        'time_adjustment_id',
        'action_by',
        'action',
    ];

    public $timestamps = true;
}
