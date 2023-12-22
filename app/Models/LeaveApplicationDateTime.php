<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplicationDateTime extends Model
{
    use HasFactory;
    protected $table = 'leave_application_date_times';

    public $fillable = [
        'leave_application_id',
        'date_from',
        'date_to',
        'time_from',
        'time_to',
 
    ];
        public function leave_application(){
            return $this->belongsTo(LeaveApplication::class);
        }
}
