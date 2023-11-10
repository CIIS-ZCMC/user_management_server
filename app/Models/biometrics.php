<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class biometrics extends Model
{
    use HasFactory;
    protected $fillable = [
        'biometric_id',
        'name',
        'privilege',
        'biometric'
    ];
}
