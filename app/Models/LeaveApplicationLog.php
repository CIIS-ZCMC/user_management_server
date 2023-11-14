<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplicationLog extends Model
{
    use HasFactory;
    protected $table = 'leave_application_logs';

    public $fillable = [
        'action_by',
        'leave_application_id',
        'process_name',
        'status',
        'date',
      
    ];
        public function leave_application(){
            return $this->belongsTo(LeaveApplication::class);
        }
      
}
