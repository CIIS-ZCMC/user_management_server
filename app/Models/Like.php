<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_profile_id',
        'freedom_wall_message_id',
    ];

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function freedomWallMessage()
    {
        return $this->belongsTo(FreedomWallMessage::class);
    }
}
