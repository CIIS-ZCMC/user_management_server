<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginTrail extends Model
{
    use HasFactory;

    protected $table = 'login_trails';

    public $fillable = [
        'signin_datetime',
        'ip_address',
        'employee_id'
    ];

    public $timestamps = TRUE;
}
