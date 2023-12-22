<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveCredit extends Model
{
    use HasFactory;
    protected $table = 'leave_credits';
    protected $fillable = [
        'id',
        'day_value',
        'month_value'
    ];
}
