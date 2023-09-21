<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordTrail extends Model
{
    use HasFactory;

    protected $table = 'password_trails';
    protected $primaryKey = 'uuid';
    public $incrementing = false;

    public $fillable = [
        'uuid',
        'old_password',
        'password_created_at',
        'expired_at',
        'employee_profile_id'
    ];

    public $timestamps = TRUE;

    public function personalInformation()
    {
        return $this->belongsTo(EmployeeProfile::class, 'uuid');
    }
}
