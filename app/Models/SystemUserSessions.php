<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemUserSessions extends Model
{
    use HasFactory;

    protected $table = "system_user_sessions";

    public $fillable = [
        "user_id",
        "system_code",
        "session_id"
    ];

    public $timestamps = true;
}
