<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotifications extends Model
{
    use HasFactory;

    protected $table = 'user_notifications';

    public $fillable = [
        'seen',
        'notification_id',
        'employee_profile_id'
    ];

    public $timestamps = TRUE;

    public function employeeProfile(){
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function notification(){
        return $this->belongsTo(Notifications::class);
    }
}
