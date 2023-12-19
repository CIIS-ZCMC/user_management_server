<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveApplicationRequirement extends Model
{
    use HasFactory;
    protected $table = 'leave_application_requirements';

    public $fillable = [
        'leave_application_id',
        'name',
        'file_name',
        'path',
        'size'

    ];
        public function leave_application(){
            return $this->belongsTo(LeaveApplication::class);
        }
}
