<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreedomWallMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_profile_id',
        'content',
    ];

    public function employeeProfile()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }
}
